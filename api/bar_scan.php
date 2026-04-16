<?php
require __DIR__ . "/../includes/config.php";
require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../includes/qr.php";

require_staff();
header("Content-Type: application/json");

$token = trim($_POST["token"] ?? "");
$product_id = (int)($_POST["product_id"] ?? 0);

if ($token === "" || $product_id < 1) {
  echo json_encode(["ok" => false, "error" => "Token ou produto inválido"]);
  exit;
}

$getSecretByUid = function(int $uid) use ($pdo) {
  $stmt = $pdo->prepare("SELECT qr_secret FROM users WHERE id=? AND is_active=1 LIMIT 1");
  $stmt->execute([$uid]);
  return $stmt->fetchColumn() ?: null;
};

$verified = verify_qr_token($token, $getSecretByUid, 60);
if ($verified === false) {
  echo json_encode(["ok" => false, "error" => "QR inválido ou expirado"]);
  exit;
}

$uid = (int)$verified["uid"];

try {
  $stmt = $pdo->prepare("SELECT id, name, price_cents FROM products WHERE id=? AND is_active=1 LIMIT 1");
  $stmt->execute([$product_id]);
  $product = $stmt->fetch();
} catch (PDOException $e) {
  echo json_encode(["ok" => false, "error" => "Erro de base de dados: " . $e->getMessage()]);
  exit;
}

if (!$product) {
  echo json_encode(["ok" => false, "error" => "Produto não encontrado"]);
  exit;
}

$stmt = $pdo->prepare("SELECT balance_cents FROM wallets WHERE user_id=? LIMIT 1");
$stmt->execute([$uid]);
$current = $stmt->fetchColumn();
$currentBalance = $current !== false ? (int)$current : 0;

if ($currentBalance < (int)$product["price_cents"]) {
  echo json_encode(["ok" => false, "error" => "Saldo insuficiente"]);
  exit;
}

try {
  if (!consume_qr_token($pdo, $verified, 60)) {
    echo json_encode(["ok" => false, "error" => "QR já utilizado. Aguarde a próxima atualização do código."]);
    exit;
  }
} catch (PDOException $e) {
  echo json_encode(["ok" => false, "error" => "Não foi possível validar o QR neste momento."]);
  exit;
}

$newBalance = $currentBalance - (int)$product["price_cents"];

if ($current !== false) {
  $stmt = $pdo->prepare("UPDATE wallets SET balance_cents=? WHERE user_id=?");
  $stmt->execute([$newBalance, $uid]);
} else {
  $stmt = $pdo->prepare("INSERT INTO wallets(user_id, balance_cents) VALUES (?, ?)");
  $stmt->execute([$uid, $newBalance]);
}

$stmt = $pdo->prepare("INSERT INTO wallet_transactions(user_id, type, amount_cents, description) VALUES (?, 'purchase', ?, ?)");
$stmt->execute([$uid, -$product["price_cents"], "Compra bar: " . $product["name"]]);

$stmt = $pdo->prepare("SELECT name, student_number FROM users WHERE id=? LIMIT 1");
$stmt->execute([$uid]);
$user = $stmt->fetch();

echo json_encode([
  "ok" => true,
  "name" => $user["name"],
  "student_number" => $user["student_number"],
  "product" => $product["name"],
  "price_cents" => (int)$product["price_cents"],
  "new_balance_cents" => $newBalance,
  "new_balance" => number_format($newBalance / 100, 2, ',', '.')
]);
