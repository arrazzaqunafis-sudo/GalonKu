<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => 'Gunakan POST.']);
}

$pdo = db();
$courier = authenticatedUser($pdo, ['tukang_galon']);
$transaksiId = filter_var($_POST['transaksi_id'] ?? null, FILTER_VALIDATE_INT);
if (!$transaksiId || $transaksiId < 1) {
    jsonResponse(422, ['success' => false, 'message' => 'ID pesanan tidak valid.']);
}
if (!isset($_FILES['foto_bukti']) || $_FILES['foto_bukti']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(422, ['success' => false, 'message' => 'Foto bukti pengantaran wajib diunggah.']);
}
if ($_FILES['foto_bukti']['size'] > 5 * 1024 * 1024) {
    jsonResponse(422, ['success' => false, 'message' => 'Ukuran foto maksimal 5 MB.']);
}

$mime = mime_content_type($_FILES['foto_bukti']['tmp_name']);
$extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
if (!isset($extensions[$mime])) {
    jsonResponse(422, ['success' => false, 'message' => 'Foto harus JPG, PNG, atau WebP.']);
}

$uploadDirectory = dirname(__DIR__, 5) . '/uploads/bukti-antar';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
    jsonResponse(500, ['success' => false, 'message' => 'Folder foto bukti tidak dapat dibuat.']);
}
$filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
if (!move_uploaded_file($_FILES['foto_bukti']['tmp_name'], $uploadDirectory . '/' . $filename)) {
    jsonResponse(500, ['success' => false, 'message' => 'Foto bukti gagal disimpan.']);
}
$relativePhotoPath = 'uploads/bukti-antar/' . $filename;

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        SELECT t.pembeli_id, t.status_pesanan, p.metode_bayar
        FROM transaksi t
        JOIN pengiriman g ON g.transaksi_id = t.transaksi_id
        JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
        WHERE t.transaksi_id = ? AND g.kurir_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$transaksiId, $courier['user_id']]);
    $order = $stmt->fetch();
    if (!$order || $order['status_pesanan'] !== 'dikirim') {
        throw new RuntimeException('Pesanan tidak ditemukan atau belum dalam proses pengantaran.');
    }

    $pdo->prepare("
        UPDATE pengiriman
        SET status_antar = 'sampai_tujuan', foto_bukti_antar = ?
        WHERE transaksi_id = ? AND kurir_id = ?
    ")->execute([$relativePhotoPath, $transaksiId, $courier['user_id']]);
    $pdo->prepare("UPDATE transaksi SET status_pesanan = 'selesai' WHERE transaksi_id = ?")
        ->execute([$transaksiId]);
    if ($order['metode_bayar'] === 'tunai') {
        $pdo->prepare("
            UPDATE pembayaran SET status_bayar = 'lunas', waktu_bayar = CURRENT_TIMESTAMP
            WHERE transaksi_id = ?
        ")->execute([$transaksiId]);
    }
    $pdo->prepare("UPDATE users SET poin_loyalitas = poin_loyalitas + 10 WHERE user_id = ?")
        ->execute([$order['pembeli_id']]);
    $pdo->commit();
    jsonResponse(200, ['success' => true, 'message' => 'Pengantaran selesai dan bukti tersimpan.']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (is_file($uploadDirectory . '/' . $filename)) {
        unlink($uploadDirectory . '/' . $filename);
    }
    jsonResponse(409, ['success' => false, 'message' => $e->getMessage()]);
}
