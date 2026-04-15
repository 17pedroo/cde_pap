<?php
$env_path = __DIR__ . "/../.env";
if (file_exists($env_path)) {
  $env = parse_ini_file($env_path);
  
  $DB_HOST = $env["DB_HOST"] ?? null;
  $DB_NAME = $env["DB_NAME"] ?? null;
  $DB_USER = $env["DB_USER"] ?? null;
  $DB_PASS = $env["DB_PASS"] ?? null;
} else {
  $DB_HOST = getenv("DB_HOST") ?? null;
  $DB_NAME = getenv("DB_NAME") ?? null;
  $DB_USER = getenv("DB_USER") ?? null;
  $DB_PASS = getenv("DB_PASS") ?? null;
}
  
try {
  $pdo = new PDO(
    "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
  );
} catch (Exception $e) {
  die("Erro BD: " . $e->getMessage());
}

session_start();
