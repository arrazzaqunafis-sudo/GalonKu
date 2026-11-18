<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(405, ['success' => false, 'message' => 'Gunakan POST.']);

$pdo = db();
$user = authenticatedUser($pdo, ['tukang_galon']);
$data = requestJson();

$lat = $data['latitude'] ?? null;
$lng = $data['longitude'] ?? null;
$accuracy = $data['accuracy'] ?? null;

if (!is_numeric($lat) || !is_numeric($lng) || !is_numeric($accuracy)) {
    jsonResponse(422, ['success' => false, 'message' => 'Koordinat tidak valid.']);
}
$lat = (float)$lat; $lng = (float)$lng; $accuracy = (float)$accuracy;
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || $accuracy < 0 || $accuracy > 10000) {
    jsonResponse(422, ['success' => false, 'message' => 'Koordinat di luar rentang.']);
}
if ($user['status_aktif'] !== 'online') {
    jsonResponse(409, ['success' => false, 'message' => 'Aktifkan status online terlebih dahulu.']);
}

$stmt = $pdo->prepare(
    "UPDATE users SET lat_kurir = :lat, long_kurir = :lng
     WHERE user_id = :id AND role = 'tukang_galon' AND status_aktif = 'online'"
);
$stmt->execute([':lat' => $lat, ':lng' => $lng, ':id' => $user['user_id']]);

jsonResponse(200, ['success' => true, 'message' => 'Lokasi diperbarui.']);
