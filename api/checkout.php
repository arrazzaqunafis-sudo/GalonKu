<?php
require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

$pembeliId = post_int('pembeli_id');
$alamatId = post_int('alamat_id');
$metodeBayar = $_POST['metode_bayar'] ?? 'tunai';
$promoCode = trim($_POST['kode_promo'] ?? '');
$items = json_decode($_POST['items'] ?? '[]', true);

if (!in_array($metodeBayar, ['tunai', 'qris', 'transfer'], true)) {
    json_response(['status' => 'error', 'message' => 'Metode pembayaran tidak valid.'], 422);
    exit;
}
if (!is_array($items) || count($items) === 0) {
    json_response(['status' => 'error', 'message' => 'Daftar item tidak boleh kosong.'], 422);
    exit;
}

try {
    $pdo->beginTransaction();

    $address = $pdo->prepare('SELECT alamat_id FROM alamat WHERE alamat_id = ? AND user_id = ?');
    $address->execute([$alamatId, $pembeliId]);
    if (!$address->fetch()) {
        throw new InvalidArgumentException('Alamat pelanggan tidak valid.');
    }

    $total = 0.0;
    $preparedItems = [];
    foreach ($items as $item) {
        $produkId = (int) ($item['produk_id'] ?? 0);
        $jumlah = (int) ($item['jumlah'] ?? 0);
        if ($produkId <= 0 || $jumlah <= 0) {
            throw new InvalidArgumentException('Produk atau jumlah tidak valid.');
        }

        $stmt = $pdo->prepare('SELECT * FROM produk_galon WHERE produk_id = ? FOR UPDATE');
        $stmt->execute([$produkId]);
        $produk = $stmt->fetch();
        if (!$produk) {
            throw new InvalidArgumentException('Produk tidak ditemukan.');
        }

        if (in_array($produk['kategori'], ['isi_ulang', 'galon_baru'], true)
            && (int) $produk['stok_isi'] < $jumlah) {
            throw new RuntimeException('Stok isi produk tidak mencukupi.');
        }
        if ($produk['kategori'] === 'galon_bekas') {
            if (empty($item['foto_galon_bekas'])) {
                throw new InvalidArgumentException('Foto kondisi galon bekas wajib diunggah.');
            }
            $photo = (string) $item['foto_galon_bekas'];
            if (!str_starts_with($photo, 'uploads/galon-bekas/')
                || !is_file(__DIR__ . '/../' . $photo)) {
                throw new InvalidArgumentException('Foto galon bekas tidak valid.');
            }
        }

        $subtotal = (float) $produk['harga'] * $jumlah;
        $total += $subtotal;
        $preparedItems[] = [$produk, $jumlah, $subtotal, $item['foto_galon_bekas'] ?? null];
    }

    $promoId = null;
    $discount = 0.0;
    if ($promoCode !== '') {
        $stmt = $pdo->prepare("
            SELECT promo_id, potongan_harga, min_poin_syarat, tanggal_berakhir
            FROM promo WHERE kode_promo = ?
        ");
        $stmt->execute([$promoCode]);
        $promo = $stmt->fetch();
        $user = $pdo->prepare('SELECT poin_loyalitas FROM users WHERE user_id = ?');
        $user->execute([$pembeliId]);
        $points = (int) ($user->fetchColumn() ?: 0);

        if (!$promo || $promo['tanggal_berakhir'] < date('Y-m-d')) {
            throw new InvalidArgumentException('Kode promo tidak valid atau sudah kedaluwarsa.');
        }
        if ($points < (int) $promo['min_poin_syarat']) {
            throw new InvalidArgumentException('Poin loyalitas tidak mencukupi.');
        }
        $promoId = (int) $promo['promo_id'];
        $discount = (float) $promo['potongan_harga'];
    }
    $total = max(0, $total - $discount);

    $stmt = $pdo->prepare("
        INSERT INTO transaksi (pembeli_id, alamat_id, promo_id, total_harga, status_pesanan)
        VALUES (?, ?, ?, ?, 'diproses')
    ");
    $stmt->execute([$pembeliId, $alamatId, $promoId, $total]);
    $transaksiId = (int) $pdo->lastInsertId();

    foreach ($preparedItems as [$produk, $jumlah, $subtotal, $photo]) {
        $pdo->prepare("
            INSERT INTO detail_transaksi
                (transaksi_id, produk_id, jumlah, subtotal, foto_galon_bekas)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([$transaksiId, $produk['produk_id'], $jumlah, $subtotal, $photo]);

        if (in_array($produk['kategori'], ['isi_ulang', 'galon_baru'], true)) {
            $pdo->prepare("
                UPDATE produk_galon
                SET stok_isi = stok_isi - ?, stok_kosong = stok_kosong + ?
                WHERE produk_id = ?
            ")->execute([$jumlah, $jumlah, $produk['produk_id']]);
        } else {
            $pdo->prepare("
                UPDATE produk_galon
                SET stok_kosong = stok_kosong + ?
                WHERE produk_id = ?
            ")->execute([$jumlah, $produk['produk_id']]);
        }
    }

    $pdo->prepare("
        INSERT INTO pembayaran (transaksi_id, metode_bayar, status_bayar)
        VALUES (?, ?, 'pending')
    ")->execute([$transaksiId, $metodeBayar]);

    $pdo->prepare("
        INSERT INTO pengiriman (transaksi_id, status_antar)
        VALUES (?, 'mencari_kurir')
    ")->execute([$transaksiId]);

    $pdo->commit();
    json_response(['status' => 'success', 'transaksi_id' => $transaksiId, 'total_harga' => $total]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $status = $e instanceof InvalidArgumentException ? 422 : 500;
    json_response(['status' => 'error', 'message' => $e->getMessage()], $status);
}
