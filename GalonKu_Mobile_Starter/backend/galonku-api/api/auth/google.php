<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/google_auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => 'Gunakan POST.']);
}

$data = requestJson();
$idToken = trim((string)($data['id_token'] ?? ''));
$role = (string)($data['role'] ?? '');
$phone = trim((string)($data['no_hp'] ?? ''));
$password = (string)($data['password'] ?? '');
$address = trim((string)($data['alamat_lengkap'] ?? ''));
$label = trim((string)($data['label_alamat'] ?? 'Rumah'));
$latitude = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);

if ($idToken === '' || !in_array($role, ['masyarakat', 'tukang_galon'], true)) {
    jsonResponse(422, ['success' => false, 'message' => 'Token Google dan peran aplikasi wajib diisi.']);
}
if ($phone !== '' && !preg_match('/^\+?[0-9][0-9 ()-]{6,18}[0-9]$/', $phone)) {
    jsonResponse(422, ['success' => false, 'message' => 'Nomor HP tidak valid.']);
}
if ($password !== '' && strlen($password) < 6) {
    jsonResponse(422, ['success' => false, 'message' => 'Password minimal 6 karakter.']);
}

$clientId = googleOAuthClientId();
try {
    $googleUser = verifyGoogleIdentityToken($idToken, $clientId);
} catch (InvalidArgumentException $error) {
    jsonResponse(401, ['success' => false, 'message' => $error->getMessage()]);
} catch (RuntimeException $error) {
    jsonResponse(503, ['success' => false, 'message' => $error->getMessage()]);
}

$email = strtolower((string)$googleUser['email']);
$pdo = db();
$findUser = $pdo->prepare(
    'SELECT user_id, nama, email, no_hp, role FROM users WHERE email = ? LIMIT 1'
);
$findUser->execute([$email]);
$user = $findUser->fetch();
$registered = false;

if ($user && $user['role'] !== $role) {
    jsonResponse(403, [
        'success' => false,
        'message' => 'Email ini sudah terdaftar untuk peran aplikasi lain.',
    ]);
}
if (!$user && $role === 'masyarakat'
    && ($address === '' || $latitude === false || $longitude === false
        || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180)) {
    jsonResponse(422, [
        'success' => false,
        'message' => 'Untuk pendaftaran pelanggan, isi alamat lengkap dan ambil lokasi GPS.',
        'requires_address' => true,
    ]);
}

try {
    $pdo->beginTransaction();
    if (!$user) {
        $temporaryPassword = $password !== '' ? $password : bin2hex(random_bytes(32));
        $insertUser = $pdo->prepare(
            'INSERT INTO users (nama, email, no_hp, password, role)
             VALUES (:nama, :email, :no_hp, :password, :role)'
        );
        $insertUser->execute([
            ':nama' => trim((string)($googleUser['name'] ?? '')) ?: $email,
            ':email' => $email,
            ':no_hp' => $phone,
            ':password' => password_hash($temporaryPassword, PASSWORD_DEFAULT),
            ':role' => $role,
        ]);
        $user = [
            'user_id' => (int)$pdo->lastInsertId(),
            'nama' => trim((string)($googleUser['name'] ?? '')) ?: $email,
            'email' => $email,
            'no_hp' => $phone,
            'role' => $role,
        ];
        $registered = true;
    } elseif ($password !== '') {
        $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $user['user_id']]);
    }

    if ($role === 'masyarakat') {
        $findAddress = $pdo->prepare(
            'SELECT alamat_id, alamat_lengkap FROM alamat
             WHERE user_id = ? ORDER BY is_utama DESC, alamat_id ASC LIMIT 1'
        );
        $findAddress->execute([$user['user_id']]);
        $savedAddress = $findAddress->fetch();
        $addressId = $savedAddress['alamat_id'] ?? null;
        $addressText = $savedAddress['alamat_lengkap'] ?? '';
        if (!$addressId && $address !== '' && $latitude !== false && $longitude !== false) {
            if ($phone !== '' && $phone !== $user['no_hp']) {
                $pdo->prepare('UPDATE users SET no_hp = ? WHERE user_id = ?')
                    ->execute([$phone, $user['user_id']]);
                $user['no_hp'] = $phone;
            }
            $insertAddress = $pdo->prepare(
                'INSERT INTO alamat (user_id, label_alamat, alamat_lengkap, latitude, longitude, is_utama)
                 VALUES (?, ?, ?, ?, ?, TRUE)'
            );
            $insertAddress->execute([$user['user_id'], $label ?: 'Rumah', $address, $latitude, $longitude]);
            $addressId = (int)$pdo->lastInsertId();
            $addressText = $address;
        }
    } else {
        $addressId = null;
        $addressText = '';
        if ($phone !== '' && $phone !== $user['no_hp']) {
            $pdo->prepare('UPDATE users SET no_hp = ? WHERE user_id = ?')
                ->execute([$phone, $user['user_id']]);
            $user['no_hp'] = $phone;
        }
    }

    $plainToken = bin2hex(random_bytes(32));
    $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
    $insertToken = $pdo->prepare(
        'INSERT INTO api_tokens (user_id, token_hash, expires_at, created_at)
         VALUES (?, ?, ?, NOW())'
    );
    $insertToken->execute([
        $user['user_id'],
        hash('sha256', $plainToken),
        $expiresAt,
    ]);
    $pdo->commit();
} catch (PDOException $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('GalonKu Google authentication database error: ' . $error->getMessage());
    jsonResponse(500, ['success' => false, 'message' => 'Akun belum dapat diproses. Silakan coba lagi.']);
}

jsonResponse(200, [
    'success' => true,
    'registered' => $registered,
    'token' => $plainToken,
    'expires_at' => $expiresAt,
    'user' => $user,
    'alamat_id' => $addressId ? (int)$addressId : null,
    'alamat_lengkap' => $addressText,
]);
