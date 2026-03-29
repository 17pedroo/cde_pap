<?php
require __DIR__ . "/includes/config.php";

// Criar tabelas
$pdo->exec("
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('student','staff','admin') NOT NULL DEFAULT 'student',
  student_number VARCHAR(20) UNIQUE,
  name VARCHAR(120) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  qr_secret VARCHAR(64) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
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

// Staff (portaria) - só 1 conta demo
// Como não tem student_number, se já existir um staff, não cria outro
$stmt = $pdo->query("SELECT id FROM users WHERE role IN ('staff','admin') LIMIT 1");
$existingStaff = $stmt->fetchColumn();
if ($existingStaff) {
  $staff = (int)$existingStaff;
} else {
  $staff = create_user($pdo, "staff", null, "Portaria Demo", "admin123", "staff-secret");
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
echo "<p><b>Staff (Portaria):</b> login em staff_login.php com pass admin123</p>";
