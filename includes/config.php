<?php
if (file_exists('.env')) {
  $env = parse_ini_file('.env');
  
  $DB_HOST = $env["DB_HOST"] ?? null;
  $DB_NAME = $env["DB_NAME"] ?? null;
  $DB_USER = $env["DB_USER"] ?? null;
  $DB_PASS = $env["DB_PASS"] ?? null;
} else {
  $DB_HOST = get_env("DB_HOST") ?? null;
  $DB_NAME = get_env("DB_NAME") ?? null;
  $DB_USER = get_env("DB_USER") ?? null;
  $DB_PASS = get_env("DB_PASS") ?? null;
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
