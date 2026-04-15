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

  if (!$DB_HOST) {
    $DB_HOST = getenv("MYSQL_HOST") ?: null;
  }
  if (!$DB_PORT) {
    $DB_PORT = getenv("MYSQL_PORT") ?: null;
  }
  if (!$DB_NAME) {
    $DB_NAME = getenv("MYSQL_DATABASE") ?: getenv("MYSQL_DB") ?: null;
  }
  if (!$DB_USER) {
    $DB_USER = getenv("MYSQL_USER") ?: null;
  }
  if (!$DB_PASS) {
    $DB_PASS = getenv("MYSQL_PASSWORD") ?: getenv("MYSQL_PASS") ?: null;
  }

  $mysqlUrl = getenv("MYSQL_URL") ?: getenv("MYSQL_PUBLIC_URL") ?: getenv("DATABASE_URL");
  if ($mysqlUrl) {
    $parsed = parse_url($mysqlUrl);
    if ($parsed !== false) {
      $DB_HOST = $DB_HOST ?: ($parsed["host"] ?? null);
      $DB_PORT = $DB_PORT ?: ($parsed["port"] ?? null);
      $DB_USER = $DB_USER ?: ($parsed["user"] ?? null);
      $DB_PASS = $DB_PASS ?: ($parsed["pass"] ?? null);
      if (!$DB_NAME && !empty($parsed["path"])) {
        $DB_NAME = ltrim($parsed["path"], "/");
      }
    }
  }
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
