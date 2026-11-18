<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$sales = $pdo->query("
    SELECT COUNT(*) AS jumlah_order,
           COALESCE(SUM(total_harga), 0) AS total_penjualan
    FROM transaksi
    WHERE status_pesanan = 'selesai'
")->fetch();

$cash = $pdo->query("
    SELECT COALESCE(SUM(t.total_harga), 0) AS saldo_tunai
    FROM transaksi t
    JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
    WHERE p.metode_bayar = 'tunai' AND p.status_bayar = 'lunas'
")->fetch();

$complaints = $pdo->query("
    SELECT COUNT(*) AS jumlah_komplain
    FROM laporan_komplain
    WHERE status_laporan = 'diproses'
")->fetch();

$inventory = $pdo->query("
    SELECT produk_id, nama_produk, kategori, stok_isi, stok_kosong
    FROM produk_galon
    ORDER BY nama_produk
")->fetchAll();

json_response([
    'status' => 'success',
    'data' => [
        'penjualan' => $sales,
        'saldo_tunai' => $cash,
        'komplain' => $complaints,
        'inventori' => $inventory,
    ],
]);
