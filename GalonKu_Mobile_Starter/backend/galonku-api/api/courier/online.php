<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(405, ['success' => false, 'message' => 'Gunakan POST.']);

$pdo = db();
$user = authenticatedUser($pdo, ['tukang_galon']);
$data = requestJson();
$online = filter_var($data['online'] ?? null, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

if ($online === null) jsonResponse(422, ['success' => false, 'message' => 'Nilai online harus boolean.']);

$stmt = $pdo->prepare(
    "UPDATE users
     SET status_aktif = :status,
         lat_kurir = CASE WHEN :online = 0 THEN NULL ELSE COALESCE(lat_kurir, -6.9175) END,
         long_kurir = CASE WHEN :online2 = 0 THEN NULL ELSE COALESCE(long_kurir, 107.6191) END
     WHERE user_id = :id AND role = 'tukang_galon'"
);
$stmt->execute([
    ':status' => $online ? 'online' : 'offline',
    ':online' => $online ? 1 : 0,
    ':online2' => $online ? 1 : 0,
    ':id' => $user['user_id']
]);

jsonResponse(200, ['success' => true, 'online' => $online]);
