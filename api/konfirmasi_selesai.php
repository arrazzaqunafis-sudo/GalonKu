<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$transaksiId = post_int('transaksi_id');
$kurirId = post_int('kurir_id');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT t.pembeli_id, t.total_harga, t.status_pesanan, p.metode_bayar, p.status_bayar
        FROM transaksi t
        JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
        JOIN pengiriman g ON g.transaksi_id = t.transaksi_id
        WHERE t.transaksi_id = ? AND g.kurir_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$transaksiId, $kurirId]);
    $trx = $stmt->fetch();
    if (!$trx) {
        throw new InvalidArgumentException('Pesanan atau kurir tidak valid.');
    }
    if ($trx['status_pesanan'] === 'selesai') {
        throw new InvalidArgumentException('Pesanan sudah diselesaikan sebelumnya.');
    }

    $foto = null;
    if (isset($_FILES['foto_bukti'])) {
        $foto = upload_file('foto_bukti', 'uploads/bukti-antar');
    }

    $pdo->prepare("
        UPDATE pengiriman
        SET status_antar = 'sampai_tujuan', foto_bukti_antar = COALESCE(?, foto_bukti_antar)
        WHERE transaksi_id = ? AND kurir_id = ?
    ")->execute([$foto, $transaksiId, $kurirId]);

    $pdo->prepare("
        UPDATE transaksi SET status_pesanan = 'selesai' WHERE transaksi_id = ?
    ")->execute([$transaksiId]);

    if ($trx['metode_bayar'] === 'tunai') {
        $pdo->prepare("
            UPDATE pembayaran
            SET status_bayar = 'lunas', waktu_bayar = CURRENT_TIMESTAMP
            WHERE transaksi_id = ?
        ")->execute([$transaksiId]);
    }

    $pdo->prepare("
        UPDATE users SET poin_loyalitas = poin_loyalitas + 10
        WHERE user_id = ?
    ")->execute([$trx['pembeli_id']]);

    $pdo->commit();
    json_response(['status' => 'success', 'message' => 'Pesanan selesai dan pembayaran direkonsiliasi.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['status' => 'error', 'message' => $e->getMessage()], 422);
}
