<?php
require __DIR__ . "/includes/config.php";

// Criar tabelas
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','staff','admin','guardian') NOT NULL DEFAULT 'student',
  student_number VARCHAR(20) UNIQUE,
  name VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  qr_secret VARCHAR(64) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS guardian_students (
  guardian_id INT NOT NULL,
  student_id INT NOT NULL,
  PRIMARY KEY (guardian_id, student_id),
  FOREIGN KEY (guardian_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallets (
  user_id INT PRIMARY KEY,
  balance_cents INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  type ENUM('topup','purchase','adjustment') NOT NULL,
  amount_cents INT NOT NULL,
  description VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  price_cents INT NOT NULL,
  category VARCHAR(50),
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS canteen_tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  ticket_type VARCHAR(50) NOT NULL,
  reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('active','scanned','cancelled') NOT NULL DEFAULT 'active',
  scanned_by_user_id INT NULL,
  notes VARCHAR(255),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (scanned_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS access_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action ENUM('IN','OUT') NOT NULL,
  scanned_by_user_id INT NULL,
  scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  notes VARCHAR(255),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (scanned_by_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS qr_token_uses (
  token_nonce CHAR(32) PRIMARY KEY,
  user_id INT NOT NULL,
  used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  INDEX idx_qr_token_uses_expires_at (expires_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
");

// helper: criar utilizador se não existir
function create_user(PDO $pdo, string $role, ?string $studentNumber, string $name, string $plainPass, string $qrSecret): int {
  if ($studentNumber) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE student_number=? LIMIT 1");
    $stmt->execute([$studentNumber]);
    $id = $stmt->fetchColumn();
    if ($id) return (int)$id;
  }

  $hash = password_hash($plainPass, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("INSERT INTO users(role, student_number, name, password_hash, qr_secret) VALUES(?,?,?,?,?)");
  $stmt->execute([$role, $studentNumber, $name, $hash, $qrSecret]);
  return (int)$pdo->lastInsertId();
}

// Utilizadores fictícios
$aluno1 = create_user($pdo, "student", "12345", "Aluno Demo 1", "1234", "secret-12345");
$aluno2 = create_user($pdo, "student", "23456", "Aluno Demo 2", "1234", "secret-23456");

// Staff demo - só 1 conta demo
// Como não tem student_number, se já existir um staff, não cria outro
$stmt = $pdo->query("SELECT id FROM users WHERE role IN ('staff','admin') LIMIT 1");
$existingStaff = $stmt->fetchColumn();
if ($existingStaff) {
  $staff = (int)$existingStaff;
} else {
  $staff = create_user($pdo, "staff", null, "Admin Demo", "admin123", "staff-secret");
}

// Encarregado de educação demo
$stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? AND student_number = ? LIMIT 1");
$stmt->execute(['guardian', 'parent1']);
$guardian = $stmt->fetchColumn();
if (!$guardian) {
  $guardian = create_user($pdo, "guardian", "parent1", "Encarregado Demo", "1234", "guardian-secret");
}

// Associações entre encarregados e alunos
$pdo->exec("INSERT IGNORE INTO guardian_students(guardian_id, student_id) VALUES ($guardian, $aluno1)");

// Produtos demo de bar/buffet
$stmt = $pdo->query("SELECT COUNT(*) FROM products");
if ((int)$stmt->fetchColumn() === 0) {
  $prod = $pdo->prepare("INSERT INTO products(name, price_cents, category, is_active) VALUES (?, ?, ?, ?)");
  $prod->execute(["Sandes Mista", 200, "meal", 1]);
  $prod->execute(["Bolachas Digestivas", 80, "snack", 1]);
  $prod->execute(["Água Pequena", 100, "drink", 1]);
  $prod->execute(["Sumo de Laranja", 130, "drink", 1]);
  $prod->execute(["Sumo de Maracujá", 130, "drink", 1]);
  $prod->execute(["Banana", 120, "snack", 1]);
  $prod->execute(["Maçã", 120, "snack", 1]);
  $prod->execute(["Laranja", 120, "snack", 1]);
} else {
  $updates = [
    "Bolachas" => "Bolachas Digestivas",
    "Bolachas Digestivas e Sal" => "Bolachas Digestivas",
    "Água" => "Água Pequena",
    "Fruta" => "Banana",
    "Sumo" => "Sumo de Laranja"
  ];
  $updateStmt = $pdo->prepare("UPDATE products SET name = ? WHERE name = ?");
  foreach ($updates as $oldName => $newName) {
    $updateStmt->execute([$newName, $oldName]);
  }

  $existingNames = $pdo->query("SELECT name FROM products")->fetchAll(PDO::FETCH_COLUMN);
  $newProducts = [
    ["Sumo de Maracujá", 130, "drink", 1],
    ["Banana", 120, "snack", 1],
    ["Maçã", 120, "snack", 1],
    ["Laranja", 120, "snack", 1]
  ];
  $prod = $pdo->prepare("INSERT INTO products(name, price_cents, category, is_active) VALUES (?, ?, ?, ?)");
  foreach ($newProducts as $item) {
    if (!in_array($item[0], $existingNames, true)) {
      $prod->execute($item);
    }
  }
}

// Carteiras (saldo)
$pdo->exec("INSERT IGNORE INTO wallets(user_id, balance_cents) VALUES ($aluno1, 1250), ($aluno2, 500)");

// Movimentos demo (se ainda não existirem)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE user_id=?");
$stmt->execute([$aluno1]);
if ((int)$stmt->fetchColumn() === 0) {
  $ins = $pdo->prepare("INSERT INTO wallet_transactions(user_id,type,amount_cents,description) VALUES (?,?,?,?)");
  $ins->execute([$aluno1, "topup", 2000, "Carregamento (demo)"]);
  $ins->execute([$aluno1, "purchase", -750, "Compra cantina (demo)"]);
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM wallet_transactions WHERE user_id=?");
$stmt->execute([$aluno2]);
if ((int)$stmt->fetchColumn() === 0) {
  $ins = $pdo->prepare("INSERT INTO wallet_transactions(user_id,type,amount_cents,description) VALUES (?,?,?,?)");
  $ins->execute([$aluno2, "topup", 500, "Carregamento (demo)"]);
}

echo "<h2>Setup concluído ✅</h2>";
echo "<p><b>Aluno Demo 1:</b> nº 12345 / pass 1234</p>";
echo "<p><b>Aluno Demo 2:</b> nº 23456 / pass 1234</p>";
echo "<p><b>Admin Demo:</b> acesso em staff_login.php com palavra-passe admin123</p>";
