<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';

$pdo = db();
$courier = authenticatedUser($pdo, ['tukang_galon']);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $summaryStmt = $pdo->prepare("
        SELECT COUNT(*) AS completed_today,
               COALESCE(SUM(g.jarak_km), 0) AS distance_today,
               COALESCE(SUM(CASE WHEN p.metode_bayar = 'tunai' AND p.status_bayar = 'lunas'
                                  THEN t.total_harga ELSE 0 END), 0) AS cash_today
        FROM pengiriman g
        JOIN transaksi t ON t.transaksi_id = g.transaksi_id
        JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
        WHERE g.kurir_id = ? AND t.status_pesanan = 'selesai'
          AND DATE(g.waktu_update) = CURDATE()
    ");
    $summaryStmt->execute([$courier['user_id']]);
    $summary = $summaryStmt->fetch();
    $historyStmt = $pdo->prepare("
        SELECT t.transaksi_id, t.total_harga, t.created_at, a.alamat_lengkap,
               p.metode_bayar, g.jarak_km, g.waktu_update
        FROM pengiriman g
        JOIN transaksi t ON t.transaksi_id = g.transaksi_id
        JOIN alamat a ON a.alamat_id = t.alamat_id
        JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
        WHERE g.kurir_id = ? AND t.status_pesanan = 'selesai'
        ORDER BY g.waktu_update DESC
        LIMIT 30
    ");
    $historyStmt->execute([$courier['user_id']]);
    $history = $historyStmt->fetchAll();

    if ($courier['status_aktif'] !== 'online') {
        jsonResponse(200, [
            'success' => true,
            'available' => [],
            'assigned' => [],
            'history' => $history,
            'summary' => $summary,
            'online' => false,
        ]);
    }

    $sql = "
        SELECT t.transaksi_id, t.total_harga, t.created_at, a.alamat_lengkap,
               a.latitude, a.longitude, u.nama AS nama_pelanggan, u.no_hp,
               p.metode_bayar, p.status_bayar, g.kurir_id,
               (6371 * ACOS(
                   COS(RADIANS(u2.lat_kurir)) * COS(RADIANS(a.latitude))
                   * COS(RADIANS(a.longitude) - RADIANS(u2.long_kurir))
                   + SIN(RADIANS(u2.lat_kurir)) * SIN(RADIANS(a.latitude))
               )) AS jarak_km,
               (SELECT GROUP_CONCAT(CONCAT(d.jumlah, 'x ', pr.nama_produk) SEPARATOR ', ')
                FROM detail_transaksi d
                JOIN produk_galon pr ON pr.produk_id = d.produk_id
                WHERE d.transaksi_id = t.transaksi_id) AS ringkasan_item
        FROM pengiriman g
        JOIN transaksi t ON t.transaksi_id = g.transaksi_id
        JOIN alamat a ON a.alamat_id = t.alamat_id
        JOIN users u ON u.user_id = t.pembeli_id
        JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
        JOIN users u2 ON u2.user_id = :courier_id
        WHERE t.status_pesanan IN ('diproses', 'dikirim')
          AND g.status_antar != 'sampai_tujuan'
          AND (g.kurir_id IS NULL OR g.kurir_id = :assigned_courier_id)
        HAVING (g.kurir_id IS NOT NULL AND g.kurir_id = :my_id) OR jarak_km <= 5
        ORDER BY (g.kurir_id = :sort_courier_id) DESC, jarak_km ASC, t.created_at ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':courier_id' => $courier['user_id'],
        ':assigned_courier_id' => $courier['user_id'],
        ':my_id' => $courier['user_id'],
        ':sort_courier_id' => $courier['user_id'],
    ]);
    $jobs = $stmt->fetchAll();
    $available = array_values(array_filter($jobs, static fn(array $job): bool => $job['kurir_id'] === null));
    $assigned = array_values(array_filter($jobs, static fn(array $job): bool => $job['kurir_id'] !== null));
    jsonResponse(200, [
        'success' => true,
        'available' => $available,
        'assigned' => $assigned,
        'history' => $history,
        'summary' => $summary,
        'online' => true,
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => 'Gunakan GET atau POST.']);
}

if ($courier['status_aktif'] !== 'online') {
    jsonResponse(409, ['success' => false, 'message' => 'Aktifkan status online sebelum menerima pesanan.']);
}

$data = requestJson();
$transaksiId = filter_var($data['transaksi_id'] ?? null, FILTER_VALIDATE_INT);
if (!$transaksiId || $transaksiId < 1) {
    jsonResponse(422, ['success' => false, 'message' => 'ID pesanan tidak valid.']);
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT g.kurir_id, g.status_antar, a.latitude, a.longitude,
               u.lat_kurir, u.long_kurir
        FROM pengiriman g
        JOIN transaksi t ON t.transaksi_id = g.transaksi_id
        JOIN alamat a ON a.alamat_id = t.alamat_id
        JOIN users u ON u.user_id = :courier_id AND u.status_aktif = 'online'
        WHERE g.transaksi_id = :transaksi_id AND t.status_pesanan = 'diproses'
        FOR UPDATE
    ");
    $stmt->execute([
        ':courier_id' => $courier['user_id'],
        ':transaksi_id' => $transaksiId,
    ]);
    $job = $stmt->fetch();
    if (!$job || $job['kurir_id'] !== null || $job['lat_kurir'] === null || $job['long_kurir'] === null) {
        throw new RuntimeException('Pesanan sudah diambil atau lokasi kurir belum tersedia.');
    }

    $lat1 = deg2rad((float) $job['lat_kurir']);
    $lat2 = deg2rad((float) $job['latitude']);
    $deltaLat = $lat2 - $lat1;
    $deltaLng = deg2rad((float) $job['longitude'] - (float) $job['long_kurir']);
    $a = sin($deltaLat / 2) ** 2 + cos($lat1) * cos($lat2) * sin($deltaLng / 2) ** 2;
    $distance = 6371 * 2 * asin(min(1, sqrt($a)));
    if ($distance > 5) {
        throw new RuntimeException('Pesanan berada di luar radius layanan 5 km.');
    }

    $update = $pdo->prepare("
        UPDATE pengiriman
        SET kurir_id = ?, status_antar = 'menuju_lokasi', jarak_km = ?
        WHERE transaksi_id = ? AND kurir_id IS NULL AND status_antar = 'mencari_kurir'
    ");
    $update->execute([$courier['user_id'], round($distance, 2), $transaksiId]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Pesanan sudah diterima kurir lain.');
    }
    $pdo->prepare("UPDATE transaksi SET status_pesanan = 'dikirim' WHERE transaksi_id = ?")
        ->execute([$transaksiId]);
    $pdo->commit();
    jsonResponse(200, ['success' => true, 'message' => 'Pesanan berhasil diterima.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(409, ['success' => false, 'message' => $e->getMessage()]);
}
