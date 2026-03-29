<?php
function make_qr_token(int $uid, string $userSecret): string {
  $ts = time();
  $data = $uid . '|' . $ts;
  $sig = hash_hmac('sha256', $data, $userSecret);
  return base64_encode($data . '|' . $sig);
}

function verify_qr_token(string $token, callable $getSecretByUid, int $ttlSeconds = 60): array|false {
  $raw = base64_decode($token, true);
  if ($raw === false) return false;

  $parts = explode('|', $raw);
  if (count($parts) !== 3) return false;

  [$uid, $ts, $sig] = $parts;
  if (!ctype_digit($uid) || !ctype_digit($ts)) return false;

  if (abs(time() - (int)$ts) > $ttlSeconds) return false;

  $secret = $getSecretByUid((int)$uid);
  if (!$secret) return false;

  $data = $uid . '|' . $ts;
  $expected = hash_hmac('sha256', $data, $secret);

  if (!hash_equals($expected, $sig)) return false;

  return ['uid' => (int)$uid, 'ts' => (int)$ts];
}
