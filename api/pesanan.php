<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$pembeliId = post_int('pembeli_id');
$metode = $_GET['metode'] ?? 'single';

if ($metode === 'detail') {
    $transaksiId = (int) ($_GET['transaksi_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT t.*, p.status_bayar, p.metode_bayar,
               g.status_antar, g.kurir_id, g.jarak_km, g.foto_bukti_antar
        FROM transaksi t
        LEFT JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
        LEFT JOIN pengiriman g ON g.transaksi_id = t.transaksi_id
        WHERE t.transaksi_id = ? AND t.pembeli_id = ?
    ");
    $stmt->execute([$transaksiId, $pembeliId]);
    $trx = $stmt->fetch();
    if (!$trx) {
        json_response(['status' => 'error', 'message' => 'Pesanan tidak ditemukan.'], 404);
        exit;
    }
    $detail = $pdo->prepare("
        SELECT d.*, pr.nama_produk, pr.kategori
        FROM detail_transaksi d
        JOIN produk_galon pr ON pr.produk_id = d.produk_id
        WHERE d.transaksi_id = ?
    ");
    $detail->execute([$transaksiId]);
    $trx['items'] = $detail->fetchAll();
    json_response(['status' => 'success', 'data' => $trx]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT t.transaksi_id, t.total_harga, t.status_pesanan, t.created_at,
           p.status_bayar, p.metode_bayar,
           g.status_antar, g.kurir_id
    FROM transaksi t
    LEFT JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
    LEFT JOIN pengiriman g ON g.transaksi_id = t.transaksi_id
    WHERE t.pembeli_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$pembeliId]);
json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
