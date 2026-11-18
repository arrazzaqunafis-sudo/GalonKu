<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$transaksiId = post_int('transaksi_id');
$pelaporId = post_int('pelapor_id');
$kategori = $_POST['kategori_masalah'] ?? '';
$deskripsi = trim($_POST['deskripsi'] ?? '');

try {
    if (!in_array($kategori, ['air_kotor', 'galon_rusak'], true)) {
        throw new InvalidArgumentException('Kategori komplain tidak valid.');
    }
    if ($deskripsi === '') {
        throw new InvalidArgumentException('Deskripsi komplain wajib diisi.');
    }

    $foto = upload_file('foto_bukti', 'uploads/komplain');
    if (!$foto) {
        throw new InvalidArgumentException('Foto bukti komplain wajib diunggah.');
    }

    $stmt = $pdo->prepare("
        SELECT t.pembeli_id, t.status_pesanan, g.waktu_update AS selesai_pada
        FROM transaksi t
        LEFT JOIN pengiriman g ON g.transaksi_id = t.transaksi_id
        WHERE t.transaksi_id = ?
    ");
    $stmt->execute([$transaksiId]);
    $trx = $stmt->fetch();

    if (!$trx || (int) $trx['pembeli_id'] !== $pelaporId) {
        throw new InvalidArgumentException('Transaksi tidak valid atau bukan milik pelapor.');
    }
    if ($trx['status_pesanan'] !== 'selesai') {
        throw new InvalidArgumentException('Transaksi belum selesai.');
    }
    if (!$trx['selesai_pada'] || time() - strtotime($trx['selesai_pada']) > 86400) {
        throw new InvalidArgumentException('Batas waktu komplain 1x24 jam telah terlewati.');
    }

    $pdo->prepare("
        INSERT INTO laporan_komplain
            (transaksi_id, pelapor_id, kategori_masalah, deskripsi, foto_bukti, status_laporan)
        VALUES (?, ?, ?, ?, ?, 'diproses')
    ")->execute([$transaksiId, $pelaporId, $kategori, $deskripsi, $foto]);

    json_response(['status' => 'success', 'message' => 'Komplain berhasil dikirim dan sedang diproses.']);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 422);
}
