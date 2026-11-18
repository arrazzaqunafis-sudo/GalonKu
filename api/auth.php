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

json_response(['status' => 'error', 'message' => 'Aksi autentikasi tidak valid.'], 400);
