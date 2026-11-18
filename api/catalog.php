<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

try {
    $stmt = $pdo->query("
        SELECT produk_id, nama_produk, kategori, harga, stok_isi, stok_kosong, deskripsi
        FROM produk_galon
        ORDER BY produk_id
    ");
    json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => 'Katalog gagal dimuat.'], 500);
}
