<?php
require __DIR__ . "/../includes/config.php";
require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../includes/qr.php";

require_staff();
header("Content-Type: application/json");

$token = trim($_POST["token"] ?? "");
$ticket_type = trim($_POST["ticket_type"] ?? "");
$notes = trim($_POST["notes"] ?? "");

$allowed = ["almoco"];
if ($token === "" || !in_array($ticket_type, $allowed, true)) {
  echo json_encode(["ok" => false, "error" => "Token ou tipo de senha inválido"]);
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

$stmt = $pdo->prepare("SELECT id, name, student_number FROM users WHERE id=? AND role='student' AND is_active=1 LIMIT 1");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user) {
  echo json_encode(["ok" => false, "error" => "Aluno inválido"]);
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

try {
  $stmt = $pdo->prepare(
    "INSERT INTO canteen_tickets (student_id, ticket_type, scanned_by_user_id, notes) VALUES (?, ?, ?, ?)"
  );
  $stmt->execute([$uid, $ticket_type, $_SESSION["user_id"], $notes]);
} catch (PDOException $e) {
  echo json_encode(["ok" => false, "error" => "Erro de base de dados: " . $e->getMessage()]);
  exit;
}

echo json_encode([
  "ok" => true,
  "name" => $user["name"],
  "student_number" => $user["student_number"],
  "ticket_type" => $ticket_type,
  "reserved_at" => date("Y-m-d H:i:s")
]);
