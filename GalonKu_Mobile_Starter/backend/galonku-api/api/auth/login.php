<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => 'Gunakan POST.']);
}

$data = requestJson();
$identity = trim((string)($data['identity'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($identity === '' || $password === '') {
    jsonResponse(422, ['success' => false, 'message' => 'Identitas dan password wajib diisi.']);
}

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT user_id, nama, email, no_hp, password, role, status_aktif
     FROM users WHERE email = ? OR no_hp = ? LIMIT 1"
);
$stmt->execute([$identity, $identity]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(401, ['success' => false, 'message' => 'Login tidak berhasil.']);
}

$plainToken = bin2hex(random_bytes(32));
$tokenHash = hash('sha256', $plainToken);
$expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');

$insert = $pdo->prepare(
    "INSERT INTO api_tokens (user_id, token_hash, expires_at, created_at)
     VALUES (:user_id, :token_hash, :expires_at, NOW())"
);
$insert->execute([
    ':user_id' => $user['user_id'],
    ':token_hash' => $tokenHash,
    ':expires_at' => $expiresAt
]);

unset($user['password'], $user['status_aktif']);

jsonResponse(200, [
    'success' => true,
    'message' => 'Login berhasil.',
    'token' => $plainToken,
    'expires_at' => $expiresAt,
    'user' => $user
]);
