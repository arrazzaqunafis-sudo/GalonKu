<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$action = $_GET['action'] ?? '';

if ($action === 'register') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $noHp = trim($_POST['no_hp'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'masyarakat';

    if ($nama === '' || $email === '' || $noHp === '' || strlen($password) < 6) {
        json_response(['status' => 'error', 'message' => 'Data registrasi tidak lengkap atau kata sandi terlalu pendek.'], 422);
        exit;
    }
    if (!in_array($role, ['masyarakat', 'admin', 'tukang_galon'], true)) {
        json_response(['status' => 'error', 'message' => 'Role tidak valid.'], 422);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (nama, email, no_hp, password, role)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nama, $email, $noHp, password_hash($password, PASSWORD_DEFAULT), $role]);
        json_response(['status' => 'success', 'user_id' => (int) $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        json_response(['status' => 'error', 'message' => 'Email sudah terdaftar atau data tidak valid.'], 422);
    }
    exit;
}

if ($action === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        json_response(['status' => 'error', 'message' => 'Email atau kata sandi salah.'], 401);
        exit;
    }
    unset($user['password']);
    json_response(['status' => 'success', 'data' => $user]);
    exit;
}

if ($action === 'google') {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true) ?: [];
    $data = array_merge($_POST, $json);

    $idToken = trim((string)($data['id_token'] ?? ''));
    $role = (string)($data['role'] ?? 'masyarakat');
    $email = trim(strtolower((string)($data['email'] ?? '')));
    $nama = trim((string)($data['nama'] ?? ''));

    if (!in_array($role, ['masyarakat', 'tukang_galon', 'admin'], true)) {
        json_response(['status' => 'error', 'message' => 'Role tidak valid.'], 422);
        exit;
    }

    if ($idToken !== '') {
        $parts = explode('.', $idToken);
        if (count($parts) === 3) {
            $claimsJson = base64_decode(strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4), true);
            $claims = $claimsJson ? json_decode($claimsJson, true) : null;
            if (is_array($claims) && !empty($claims['email'])) {
                $email = strtolower($claims['email']);
                if ($nama === '' && !empty($claims['name'])) {
                    $nama = $claims['name'];
                }
            }
        }
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['status' => 'error', 'message' => 'Email Google tidak valid.'], 422);
        exit;
    }

    if ($nama === '') {
        $nama = explode('@', $email)[0];
    }

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['role'] !== $role) {
            json_response(['status' => 'error', 'message' => "Email ini sudah terdaftar dengan role {$user['role']}."], 403);
            exit;
        }
    } else {
        $randPass = bin2hex(random_bytes(16));
        $phone = trim((string)($data['no_hp'] ?? '-')) ?: '-';
        $insert = $pdo->prepare('INSERT INTO users (nama, email, no_hp, password, role) VALUES (?, ?, ?, ?, ?)');
        $insert->execute([$nama, $email, $phone, password_hash($randPass, PASSWORD_DEFAULT), $role]);
        $newId = (int)$pdo->lastInsertId();

        if ($role === 'masyarakat') {
            $addr = trim((string)($data['alamat_lengkap'] ?? 'Bandung, Jawa Barat')) ?: 'Bandung, Jawa Barat';
            $lat = isset($data['latitude']) && is_numeric($data['latitude']) ? (float)$data['latitude'] : -6.9175;
            $lng = isset($data['longitude']) && is_numeric($data['longitude']) ? (float)$data['longitude'] : 107.6191;
            $pdo->prepare('INSERT INTO alamat (user_id, label_alamat, alamat_lengkap, latitude, longitude, is_utama) VALUES (?, ?, ?, ?, ?, TRUE)')
                ->execute([$newId, 'Rumah', $addr, $lat, $lng]);
        }

        $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$newId]);
        $user = $stmt->fetch();
    }

    unset($user['password']);
    json_response(['status' => 'success', 'data' => $user]);
    exit;
}

json_response(['status' => 'error', 'message' => 'Aksi autentikasi tidak valid.'], 400);
