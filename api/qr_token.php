<?php
require __DIR__ . "/../includes/config.php";
require __DIR__ . "/../includes/auth.php";
require __DIR__ . "/../includes/qr.php";

require_login();

$uid = (int)$_SESSION["user_id"];

$stmt = $pdo->prepare("SELECT qr_secret FROM users WHERE id=? LIMIT 1");
$stmt->execute([$uid]);
$secret = $stmt->fetchColumn();

header("Content-Type: application/json");

echo json_encode([
  "token" => make_qr_token($uid, $secret)
]);
