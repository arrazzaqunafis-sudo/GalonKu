<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$transaksiId = post_int('transaksi_id');
$kurirId = post_int('kurir_id');

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT user_id, lat_kurir, long_kurir
        FROM users
        WHERE user_id = ? AND role = 'tukang_galon' AND status_aktif = 'online'
    ");
    $stmt->execute([$kurirId]);
    $kurir = $stmt->fetch();
    if (!$kurir) {
        throw new InvalidArgumentException('Kurir tidak aktif atau tidak valid.');
    }

    $stmt = $pdo->prepare('SELECT transaksi_id FROM pengiriman WHERE transaksi_id = ? FOR UPDATE');
    $stmt->execute([$transaksiId]);
    if (!$stmt->fetch()) {
        throw new InvalidArgumentException('Pengiriman tidak ditemukan.');
    }

    $pdo->prepare("
        UPDATE pengiriman
        SET kurir_id = ?, status_antar = 'menuju_lokasi'
        WHERE transaksi_id = ?
    ")->execute([$kurirId, $transaksiId]);
    $pdo->prepare("
        UPDATE transaksi SET status_pesanan = 'dikirim' WHERE transaksi_id = ?
    ")->execute([$transaksiId]);
    $pdo->commit();
    json_response(['status' => 'success', 'message' => 'Kurir berhasil ditugaskan.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['status' => 'error', 'message' => $e->getMessage()], 422);
}
