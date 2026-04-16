<?php
function make_qr_token(int $uid, string $userSecret): string {
  $ts = time();
  $nonce = bin2hex(random_bytes(16));
  $data = $uid . '|' . $ts . '|' . $nonce;
  $sig = hash_hmac('sha256', $data, $userSecret);
  return base64_encode($data . '|' . $sig);
}

function verify_qr_token(string $token, callable $getSecretByUid, int $ttlSeconds = 60): array|false {
  $raw = base64_decode($token, true);
  if ($raw === false) return false;

  $parts = explode('|', $raw);
  if (count($parts) !== 4) return false;

  [$uid, $ts, $nonce, $sig] = $parts;
  if (!ctype_digit($uid) || !ctype_digit($ts)) return false;
  if (!preg_match('/^[a-f0-9]{32}$/', $nonce)) return false;

  if (abs(time() - (int)$ts) > $ttlSeconds) return false;

  $secret = $getSecretByUid((int)$uid);
  if (!$secret) return false;

  $data = $uid . '|' . $ts . '|' . $nonce;
  $expected = hash_hmac('sha256', $data, $secret);

  if (!hash_equals($expected, $sig)) return false;

  return ['uid' => (int)$uid, 'ts' => (int)$ts, 'nonce' => $nonce];
}

function ensure_qr_token_usage_table(PDO $pdo): void {
  static $initialized = false;

  if ($initialized) {
    return;
  }

  $pdo->exec(
    "CREATE TABLE IF NOT EXISTS qr_token_uses (
      token_nonce CHAR(32) PRIMARY KEY,
      user_id INT NOT NULL,
      used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      expires_at DATETIME NOT NULL,
      INDEX idx_qr_token_uses_expires_at (expires_at),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
  );

  $initialized = true;
}

function consume_qr_token(PDO $pdo, array $verifiedToken, int $ttlSeconds = 60): bool {
  $nonce = $verifiedToken['nonce'] ?? '';
  if ($nonce === '') {
    return false;
  }

  ensure_qr_token_usage_table($pdo);

  static $cleaned_up = false;
  if (!$cleaned_up) {
    $cleanup = $pdo->prepare("DELETE FROM qr_token_uses WHERE expires_at < ?");
    $cleanup->execute([date('Y-m-d H:i:s')]);
    $cleaned_up = true;
  }

  $stmt = $pdo->prepare(
    "INSERT INTO qr_token_uses (token_nonce, user_id, expires_at) VALUES (?, ?, ?)"
  );

  try {
    $stmt->execute([
      $nonce,
      (int)$verifiedToken['uid'],
      date('Y-m-d H:i:s', ((int)$verifiedToken['ts']) + $ttlSeconds),
    ]);

    return true;
  } catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
      return false;
    }

    throw $e;
  }
}
