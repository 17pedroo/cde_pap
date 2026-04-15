<?php
$env_path = __DIR__ . "/../.env";
if (file_exists($env_path)) {
  $env = parse_ini_file($env_path);
  
  $DB_HOST = $env["DB_HOST"] ?? null;
  $DB_PORT = $env["DB_PORT"] ?? null;
  $DB_NAME = $env["DB_NAME"] ?? null;
  $DB_USER = $env["DB_USER"] ?? null;
  $DB_PASS = $env["DB_PASS"] ?? null;
} else {
  $DB_HOST = getenv("DB_HOST") ?: null;
  $DB_PORT = getenv("DB_PORT") ?: null;
  $DB_NAME = getenv("DB_NAME") ?: null;
  $DB_USER = getenv("DB_USER") ?: null;
  $DB_PASS = getenv("DB_PASS") ?: null;
}

if ($DB_HOST && str_contains($DB_HOST, ":") && !$DB_PORT) {
  [$host, $port] = explode(":", $DB_HOST, 2);
  $DB_HOST = $host;
  $DB_PORT = $port;
}

$dsn = "mysql:host=$DB_HOST";
if ($DB_PORT) {
  $dsn .= ";port=$DB_PORT";
}
$dsn .= ";dbname=$DB_NAME;charset=utf8mb4";
  
try {
  $pdo = new PDO(
    $dsn,
    $DB_USER,
    $DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
  );
} catch (Exception $e) {
  $missing = [];
  if (!$DB_HOST) {
    $missing[] = "DB_HOST";
  }
  if (!$DB_NAME) {
    $missing[] = "DB_NAME";
  }
  if (!$DB_USER) {
    $missing[] = "DB_USER";
  }

  if ($missing) {
    die("Erro BD: variáveis de ambiente incompletas (" . implode(", ", $missing) . ").");
  }

  die("Erro BD: " . $e->getMessage());
}

session_start();
