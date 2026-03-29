<?php
require __DIR__ . "/../includes/config.php";
require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../includes/qr.php";

require_staff();

header("Content-Type: application/json");

$token = trim($_POST["token"] ?? "");
if ($token === "") {
  echo json_encode(["ok" => false, "error" => "Token vazio"]);
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

$stmt = $pdo->prepare("SELECT id, name, student_number, role FROM users WHERE id=? AND is_active=1 LIMIT 1");
$stmt->execute([$uid]);
$user = $stmt->fetch();

if (!$user || $user["role"] !== "student") {
  echo json_encode(["ok" => false, "error" => "Utilizador inválido"]);
  exit;
}

$stmt = $pdo->prepare("SELECT action FROM access_logs WHERE user_id=? ORDER BY scanned_at DESC LIMIT 1");
$stmt->execute([$uid]);
$last = $stmt->fetchColumn();

$action = ($last === "IN") ? "OUT" : "IN";

$scannedBy = (int)$_SESSION["user_id"];
$stmt = $pdo->prepare("INSERT INTO access_logs (user_id, action, scanned_by_user_id) VALUES (?,?,?)");
$stmt->execute([$uid, $action, $scannedBy]);

echo json_encode([
  "ok" => true,
  "name" => $user["name"],
  "student_number" => $user["student_number"],
  "action" => $action,
  "time" => date("H:i:s")
]);
