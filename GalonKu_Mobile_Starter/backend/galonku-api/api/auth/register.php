<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => 'Gunakan POST.']);
}

$data = requestJson();
$nama = trim((string)($data['nama'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$noHp = trim((string)($data['no_hp'] ?? ''));
$password = (string)($data['password'] ?? '');
$role = (string)($data['role'] ?? 'masyarakat');
$alamatLengkap = trim((string)($data['alamat_lengkap'] ?? ''));
$labelAlamat = trim((string)($data['label_alamat'] ?? 'Rumah'));
$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

if ($nama === '' || $email === '' || $noHp === '' || strlen($password) < 6) {
    jsonResponse(422, ['success' => false, 'message' => 'Lengkapi data dan gunakan password minimal 6 karakter.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(422, ['success' => false, 'message' => 'Format email tidak valid.']);
}
if (!in_array($role, ['masyarakat', 'tukang_galon'], true)) {
    jsonResponse(422, ['success' => false, 'message' => 'Role pendaftaran tidak valid.']);
}
if ($role === 'masyarakat' && $alamatLengkap === '') {
    $alamatLengkap = 'Bandung, Jawa Barat';
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $insert = $pdo->prepare(
        "INSERT INTO users (nama, email, no_hp, password, role)
         VALUES (:nama, :email, :no_hp, :password, :role)"
    );
    $insert->execute([
        ':nama' => $nama,
        ':email' => $email,
        ':no_hp' => $noHp,
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':role' => $role
    ]);
    $userId = (int)$pdo->lastInsertId();

    $alamatId = null;
    if ($role === 'masyarakat') {
        $lat = is_numeric($latitude) ? (float)$latitude : -6.9175;
        $lng = is_numeric($longitude) ? (float)$longitude : 107.6191;
        $insertAddress = $pdo->prepare(
            "INSERT INTO alamat (user_id, label_alamat, alamat_lengkap, latitude, longitude, is_utama)
             VALUES (:user_id, :label, :alamat, :lat, :lng, TRUE)"
        );
        $insertAddress->execute([
            ':user_id' => $userId,
            ':label' => $labelAlamat,
            ':alamat' => $alamatLengkap,
            ':lat' => $lat,
            ':lng' => $lng
        ]);
        $alamatId = (int)$pdo->lastInsertId();
    } elseif ($role === 'tukang_galon') {
        $pdo->prepare("UPDATE users SET status_aktif = 'online', lat_kurir = -6.9175, long_kurir = 107.6191 WHERE user_id = ?")
            ->execute([$userId]);
    }

    // Ensure api_tokens table exists and generate login token
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS api_tokens (
            token_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token_hash CHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            last_used_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_api_tokens_user (user_id),
            INDEX idx_api_tokens_expiry (expires_at)
        ) ENGINE=InnoDB
    ");

    $plainToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $plainToken);
    $expiresAt = (new DateTimeImmutable('+30 days'))->format('Y-m-d H:i:s');
    $insertToken = $pdo->prepare(
        "INSERT INTO api_tokens (user_id, token_hash, expires_at, created_at)
         VALUES (:user_id, :token_hash, :expires_at, NOW())"
    );
    $insertToken->execute([
        ':user_id' => $userId,
        ':token_hash' => $tokenHash,
        ':expires_at' => $expiresAt
    ]);

    $pdo->commit();
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    jsonResponse(422, ['success' => false, 'message' => 'Email atau nomor HP sudah terdaftar.']);
}

jsonResponse(201, [
    'success' => true,
    'message' => 'Pendaftaran berhasil.',
    'token' => $plainToken,
    'expires_at' => $expiresAt,
    'user' => [
        'user_id' => $userId,
        'nama' => $nama,
        'email' => $email,
        'no_hp' => $noHp,
        'role' => $role
    ],
    'alamat_id' => $alamatId,
    'alamat_lengkap' => $alamatLengkap
]);
