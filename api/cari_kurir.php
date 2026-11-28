<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$latUser = isset($_GET['lat']) ? (float) $_GET['lat'] : -6.9175;
$lngUser = isset($_GET['lng']) ? (float) $_GET['lng'] : 107.6191;

$sql = "
    SELECT user_id, nama, no_hp, lat_kurir, long_kurir,
        (6371 * acos(LEAST(1, GREATEST(-1,
            cos(radians(:lat1)) * cos(radians(lat_kurir))
            * cos(radians(long_kurir) - radians(:lng1))
            + sin(radians(:lat2)) * sin(radians(lat_kurir))
        )))) AS jarak_km
    FROM users
    WHERE role = 'tukang_galon'
      AND status_aktif = 'online'
      AND lat_kurir IS NOT NULL
      AND long_kurir IS NOT NULL
    HAVING jarak_km <= 5
    ORDER BY jarak_km ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':lat1' => $latUser, ':lng1' => $lngUser, ':lat2' => $latUser]);
json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
