<?php
declare(strict_types=1);

require_once __DIR__ . '/../koneksi.php';
require_once __DIR__ . '/../helpers.php';

global $pdo;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        /* ================= DASHBOARD ================= */
        case 'dashboard':
            // Total order selesai & total penjualan
            $sales = $pdo->query("
                SELECT COUNT(*) AS jumlah_order,
                       COALESCE(SUM(total_harga), 0) AS total_penjualan
                FROM transaksi
                WHERE status_pesanan = 'selesai'
            ")->fetch();

            // Saldo tunai lunas
            $cash = $pdo->query("
                SELECT COALESCE(SUM(t.total_harga), 0) AS saldo_tunai
                FROM transaksi t
                JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
                WHERE p.metode_bayar = 'tunai' AND p.status_bayar = 'lunas'
            ")->fetch();

            // Komplain aktif
            $complaints = $pdo->query("
                SELECT COUNT(*) AS jumlah_komplain
                FROM laporan_komplain
                WHERE status_laporan = 'diproses'
            ")->fetch();

            // Pesanan aktif (diproses / dikirim / menunggu)
            $activeOrders = $pdo->query("
                SELECT COUNT(*) AS total_aktif
                FROM transaksi
                WHERE status_pesanan IN ('menunggu_bayar', 'diproses', 'dikirim')
            ")->fetch();

            // Inventori
            $inventory = $pdo->query("
                SELECT produk_id, nama_produk, kategori, harga, stok_isi, stok_kosong
                FROM produk_galon
                ORDER BY produk_id ASC
            ")->fetchAll();

            // 5 Transaksi terbaru
            $recentOrders = $pdo->query("
                SELECT t.transaksi_id, t.total_harga, t.status_pesanan, t.created_at,
                       u.nama AS nama_pelanggan,
                       p.metode_bayar, p.status_bayar
                FROM transaksi t
                JOIN users u ON u.user_id = t.pembeli_id
                LEFT JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
                ORDER BY t.transaksi_id DESC
                LIMIT 5
            ")->fetchAll();

            // Penjualan bulanan (12 bulan terakhir atau bulan-bulan di tahun ini)
            $monthlySales = $pdo->query("
                SELECT 
                    MONTH(created_at) AS bulan,
                    COALESCE(SUM(total_harga), 0) AS total
                FROM transaksi
                WHERE status_pesanan = 'selesai' AND YEAR(created_at) = YEAR(CURRENT_DATE)
                GROUP BY MONTH(created_at)
            ")->fetchAll(PDO::FETCH_KEY_PAIR);

            // Susun 12 bulan
            $monthlyData = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthlyData[$m] = (float)($monthlySales[$m] ?? 0);
            }

            json_response([
                'status' => 'success',
                'data' => [
                    'penjualan' => $sales,
                    'saldo_tunai' => $cash,
                    'komplain' => $complaints,
                    'pesanan_aktif' => $activeOrders,
                    'inventori' => $inventory,
                    'pesanan_terbaru' => $recentOrders,
                    'monthly_sales' => $monthlyData,
                ],
            ]);
            break;

        /* ================= PRODUK ================= */
        case 'products':
            $stmt = $pdo->query("
                SELECT produk_id, nama_produk, kategori, harga, stok_isi, stok_kosong, deskripsi
                FROM produk_galon
                ORDER BY produk_id DESC
            ");
            json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'save_product':
            $id = post_int('produk_id', 0);
            $nama = trim($_POST['nama_produk'] ?? '');
            $kategori = $_POST['kategori'] ?? 'isi_ulang';
            $harga = (float)($_POST['harga'] ?? 0);
            $stokIsi = post_int('stok_isi', 0);
            $stokKosong = post_int('stok_kosong', 0);
            $deskripsi = trim($_POST['deskripsi'] ?? '');

            if ($nama === '' || $harga < 0) {
                json_response(['status' => 'error', 'message' => 'Nama produk dan harga wajib diisi dengan benar.'], 422);
                exit;
            }
            if (!in_array($kategori, ['isi_ulang', 'galon_baru', 'galon_bekas'], true)) {
                json_response(['status' => 'error', 'message' => 'Kategori produk tidak valid.'], 422);
                exit;
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE produk_galon
                    SET nama_produk = ?, kategori = ?, harga = ?, stok_isi = ?, stok_kosong = ?, deskripsi = ?
                    WHERE produk_id = ?
                ");
                $stmt->execute([$nama, $kategori, $harga, $stokIsi, $stokKosong, $deskripsi, $id]);
                json_response(['status' => 'success', 'message' => 'Produk berhasil diperbarui.']);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO produk_galon (nama_produk, kategori, harga, stok_isi, stok_kosong, deskripsi)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nama, $kategori, $harga, $stokIsi, $stokKosong, $deskripsi]);
                json_response(['status' => 'success', 'message' => 'Produk baru berhasil ditambahkan.']);
            }
            break;

        case 'delete_product':
            $id = post_int('produk_id', 0);
            if ($id <= 0) {
                json_response(['status' => 'error', 'message' => 'ID produk tidak valid.'], 422);
                exit;
            }
            // Check if product is in transactions
            $inTrx = $pdo->prepare("SELECT COUNT(*) FROM detail_transaksi WHERE produk_id = ?");
            $inTrx->execute([$id]);
            if ($inTrx->fetchColumn() > 0) {
                json_response(['status' => 'error', 'message' => 'Produk tidak dapat dihapus karena sudah ada transaksi terkait.'], 422);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM produk_galon WHERE produk_id = ?");
            $stmt->execute([$id]);
            json_response(['status' => 'success', 'message' => 'Produk berhasil dihapus.']);
            break;

        case 'adjust_stock':
            $id = post_int('produk_id', 0);
            $type = $_POST['type'] ?? 'isi'; // 'isi' or 'kosong'
            $delta = (int)($_POST['delta'] ?? 0);

            if ($id <= 0 || $delta === 0) {
                json_response(['status' => 'error', 'message' => 'Parameter tidak valid.'], 422);
                exit;
            }

            $column = ($type === 'kosong') ? 'stok_kosong' : 'stok_isi';
            $stmt = $pdo->prepare("UPDATE produk_galon SET $column = GREATEST(0, $column + ?) WHERE produk_id = ?");
            $stmt->execute([$delta, $id]);
            json_response(['status' => 'success', 'message' => 'Stok berhasil disesuaikan.']);
            break;

        /* ================= PESANAN ================= */
        case 'orders':
            $statusFilter = trim($_GET['status'] ?? '');
            $sql = "
                SELECT t.transaksi_id, t.total_harga, t.status_pesanan, t.created_at,
                       u.nama AS nama_pelanggan, u.no_hp AS no_hp_pelanggan,
                       a.alamat_lengkap, a.label_alamat,
                       p.metode_bayar, p.status_bayar,
                       g.status_antar, g.kurir_id, k.nama AS nama_kurir,
                       pr.kode_promo, pr.potongan_harga
                FROM transaksi t
                JOIN users u ON u.user_id = t.pembeli_id
                LEFT JOIN alamat a ON a.alamat_id = t.alamat_id
                LEFT JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
                LEFT JOIN pengiriman g ON g.transaksi_id = t.transaksi_id
                LEFT JOIN users k ON k.user_id = g.kurir_id
                LEFT JOIN promo pr ON pr.promo_id = t.promo_id
            ";
            $params = [];
            if ($statusFilter !== '' && $statusFilter !== 'semua') {
                $sql .= " WHERE t.status_pesanan = ?";
                $params[] = $statusFilter;
            }
            $sql .= " ORDER BY t.transaksi_id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();

            json_response(['status' => 'success', 'data' => $orders]);
            break;

        case 'order_detail':
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) {
                json_response(['status' => 'error', 'message' => 'ID pesanan tidak valid.'], 422);
                exit;
            }

            $stmt = $pdo->prepare("
                SELECT t.*,
                       u.nama AS nama_pelanggan, u.email AS email_pelanggan, u.no_hp AS no_hp_pelanggan,
                       a.label_alamat, a.alamat_lengkap, a.latitude, a.longitude,
                       p.metode_bayar, p.status_bayar, p.waktu_bayar,
                       g.status_antar, g.kurir_id, g.jarak_km, g.foto_bukti_antar,
                       k.nama AS nama_kurir, k.no_hp AS no_hp_kurir,
                       pr.kode_promo, pr.potongan_harga
                FROM transaksi t
                JOIN users u ON u.user_id = t.pembeli_id
                LEFT JOIN alamat a ON a.alamat_id = t.alamat_id
                LEFT JOIN pembayaran p ON p.transaksi_id = t.transaksi_id
                LEFT JOIN pengiriman g ON g.transaksi_id = t.transaksi_id
                LEFT JOIN users k ON k.user_id = g.kurir_id
                LEFT JOIN promo pr ON pr.promo_id = t.promo_id
                WHERE t.transaksi_id = ?
            ");
            $stmt->execute([$id]);
            $order = $stmt->fetch();

            if (!$order) {
                json_response(['status' => 'error', 'message' => 'Pesanan tidak ditemukan.'], 404);
                exit;
            }

            $itemsStmt = $pdo->prepare("
                SELECT d.*, pr.nama_produk, pr.kategori, pr.harga AS harga_satuan
                FROM detail_transaksi d
                JOIN produk_galon pr ON pr.produk_id = d.produk_id
                WHERE d.transaksi_id = ?
            ");
            $itemsStmt->execute([$id]);
            $order['items'] = $itemsStmt->fetchAll();

            json_response(['status' => 'success', 'data' => $order]);
            break;

        case 'update_order_status':
            $transaksiId = post_int('transaksi_id', 0);
            $newStatus = trim($_POST['status_pesanan'] ?? '');
            $kurirId = post_int('kurir_id', 0);
            $statusBayar = trim($_POST['status_bayar'] ?? '');

            if ($transaksiId <= 0) {
                json_response(['status' => 'error', 'message' => 'ID pesanan tidak valid.'], 422);
                exit;
            }

            $validStatus = ['menunggu_bayar', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
            if (!in_array($newStatus, $validStatus, true)) {
                json_response(['status' => 'error', 'message' => 'Status pesanan tidak valid.'], 422);
                exit;
            }

            $pdo->beginTransaction();

            // Update status pesanan
            $pdo->prepare("UPDATE transaksi SET status_pesanan = ? WHERE transaksi_id = ?")
                ->execute([$newStatus, $transaksiId]);

            // If kurir is specified or delivery needs update
            if ($kurirId > 0) {
                $statusAntar = ($newStatus === 'selesai') ? 'sampai_tujuan' : 'menuju_lokasi';
                $pdo->prepare("
                    UPDATE pengiriman 
                    SET kurir_id = ?, status_antar = ?
                    WHERE transaksi_id = ?
                ")->execute([$kurirId, $statusAntar, $transaksiId]);
            }

            // Status bayar update if specified
            if ($statusBayar !== '' && in_array($statusBayar, ['pending', 'lunas', 'gagal'], true)) {
                $waktu = ($statusBayar === 'lunas') ? date('Y-m-d H:i:s') : null;
                $pdo->prepare("
                    UPDATE pembayaran 
                    SET status_bayar = ?, waktu_bayar = COALESCE(?, waktu_bayar)
                    WHERE transaksi_id = ?
                ")->execute([$statusBayar, $waktu, $transaksiId]);
            }

            // If finished, ensure payment lunas and add loyalty points
            if ($newStatus === 'selesai') {
                $pdo->prepare("UPDATE pembayaran SET status_bayar = 'lunas', waktu_bayar = COALESCE(waktu_bayar, CURRENT_TIMESTAMP) WHERE transaksi_id = ?")
                    ->execute([$transaksiId]);
                
                $orderUser = $pdo->prepare("SELECT pembeli_id FROM transaksi WHERE transaksi_id = ?");
                $orderUser->execute([$transaksiId]);
                $pembeliId = $orderUser->fetchColumn();
                if ($pembeliId) {
                    $pdo->prepare("UPDATE users SET poin_loyalitas = poin_loyalitas + 10 WHERE user_id = ?")
                        ->execute([$pembeliId]);
                }
            }

            $pdo->commit();
            json_response(['status' => 'success', 'message' => 'Status pesanan berhasil diperbarui.']);
            break;

        /* ================= PELANGGAN & PENGGUNA ================= */
        case 'users':
            $roleFilter = trim($_GET['role'] ?? '');
            $sql = "
                SELECT u.user_id, u.nama, u.email, u.no_hp, u.role, u.poin_loyalitas, u.status_aktif, u.created_at,
                       a.alamat_lengkap
                FROM users u
                LEFT JOIN alamat a ON a.user_id = u.user_id AND a.is_utama = 1
            ";
            $params = [];
            if ($roleFilter !== '' && $roleFilter !== 'semua') {
                $sql .= " WHERE u.role = ?";
                $params[] = $roleFilter;
            }
            $sql .= " ORDER BY u.user_id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'save_user':
            $id = post_int('user_id', 0);
            $nama = trim($_POST['nama'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $noHp = trim($_POST['no_hp'] ?? '');
            $role = $_POST['role'] ?? 'masyarakat';
            $password = $_POST['password'] ?? '';
            $alamat = trim($_POST['alamat'] ?? '');
            $poin = post_int('poin_loyalitas', 0);

            if ($nama === '' || $email === '' || $noHp === '') {
                json_response(['status' => 'error', 'message' => 'Nama, email, dan no handphone wajib diisi.'], 422);
                exit;
            }

            if ($id > 0) {
                // Update
                if ($password !== '') {
                    $stmt = $pdo->prepare("
                        UPDATE users SET nama = ?, email = ?, no_hp = ?, role = ?, poin_loyalitas = ?, password = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$nama, $email, $noHp, $role, $poin, password_hash($password, PASSWORD_DEFAULT), $id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users SET nama = ?, email = ?, no_hp = ?, role = ?, poin_loyalitas = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$nama, $email, $noHp, $role, $poin, $id]);
                }

                if ($alamat !== '') {
                    // Update or insert alamat
                    $chkAddr = $pdo->prepare("SELECT alamat_id FROM alamat WHERE user_id = ? AND is_utama = 1");
                    $chkAddr->execute([$id]);
                    $addrId = $chkAddr->fetchColumn();
                    if ($addrId) {
                        $pdo->prepare("UPDATE alamat SET alamat_lengkap = ? WHERE alamat_id = ?")
                            ->execute([$alamat, $addrId]);
                    } else {
                        $pdo->prepare("INSERT INTO alamat (user_id, label_alamat, alamat_lengkap, latitude, longitude, is_utama) VALUES (?, 'Utama', ?, -6.9175, 107.6191, 1)")
                            ->execute([$id, $alamat]);
                    }
                }

                json_response(['status' => 'success', 'message' => 'Pengguna berhasil diperbarui.']);
            } else {
                // Create
                if (strlen($password) < 6) {
                    json_response(['status' => 'error', 'message' => 'Kata sandi minimal 6 karakter untuk pengguna baru.'], 422);
                    exit;
                }

                $stmt = $pdo->prepare("
                    INSERT INTO users (nama, email, no_hp, role, poin_loyalitas, password)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nama, $email, $noHp, $role, $poin, password_hash($password, PASSWORD_DEFAULT)]);
                $newId = (int)$pdo->lastInsertId();

                if ($alamat !== '') {
                    $pdo->prepare("INSERT INTO alamat (user_id, label_alamat, alamat_lengkap, latitude, longitude, is_utama) VALUES (?, 'Utama', ?, -6.9175, 107.6191, 1)")
                        ->execute([$newId, $alamat]);
                }

                json_response(['status' => 'success', 'message' => 'Pengguna baru berhasil ditambahkan.']);
            }
            break;

        case 'delete_user':
            $id = post_int('user_id', 0);
            if ($id <= 0) {
                json_response(['status' => 'error', 'message' => 'ID pengguna tidak valid.'], 422);
                exit;
            }
            // Protect against deleting user_id 1
            if ($id === 1) {
                json_response(['status' => 'error', 'message' => 'Akun Admin utama tidak boleh dihapus.'], 422);
                exit;
            }
            $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
            json_response(['status' => 'success', 'message' => 'Pengguna berhasil dihapus.']);
            break;

        /* ================= KOMPLAIN ================= */
        case 'complaints':
            $statusFilter = trim($_GET['status'] ?? '');
            $sql = "
                SELECT l.*,
                       u.nama AS nama_pelapor, u.no_hp AS no_hp_pelapor,
                       t.total_harga, t.status_pesanan
                FROM laporan_komplain l
                JOIN users u ON u.user_id = l.pelapor_id
                JOIN transaksi t ON t.transaksi_id = l.transaksi_id
            ";
            $params = [];
            if ($statusFilter !== '' && $statusFilter !== 'semua') {
                $sql .= " WHERE l.status_laporan = ?";
                $params[] = $statusFilter;
            }
            $sql .= " ORDER BY l.laporan_id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'update_complaint':
            $laporanId = post_int('laporan_id', 0);
            $statusLaporan = trim($_POST['status_laporan'] ?? '');

            if ($laporanId <= 0 || !in_array($statusLaporan, ['diproses', 'diretur', 'selesai'], true)) {
                json_response(['status' => 'error', 'message' => 'Data status komplain tidak valid.'], 422);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE laporan_komplain SET status_laporan = ? WHERE laporan_id = ?");
            $stmt->execute([$statusLaporan, $laporanId]);
            json_response(['status' => 'success', 'message' => 'Status komplain berhasil diperbarui.']);
            break;

        /* ================= PENGATURAN / PROMO ================= */
        case 'promos':
            $stmt = $pdo->query("SELECT * FROM promo ORDER BY promo_id DESC");
            json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        case 'save_promo':
            $id = post_int('promo_id', 0);
            $kode = strtoupper(trim($_POST['kode_promo'] ?? ''));
            $nama = trim($_POST['nama_promo'] ?? '');
            $potongan = (float)($_POST['potongan_harga'] ?? 0);
            $minPoin = post_int('min_poin_syarat', 0);
            $tglBerakhir = trim($_POST['tanggal_berakhir'] ?? '');

            if ($kode === '' || $nama === '' || $potongan <= 0 || $tglBerakhir === '') {
                json_response(['status' => 'error', 'message' => 'Semua kolom promo wajib diisi dengan benar.'], 422);
                exit;
            }

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE promo
                    SET kode_promo = ?, nama_promo = ?, potongan_harga = ?, min_poin_syarat = ?, tanggal_berakhir = ?
                    WHERE promo_id = ?
                ");
                $stmt->execute([$kode, $nama, $potongan, $minPoin, $tglBerakhir, $id]);
                json_response(['status' => 'success', 'message' => 'Promo berhasil diperbarui.']);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO promo (kode_promo, nama_promo, potongan_harga, min_poin_syarat, tanggal_berakhir)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$kode, $nama, $potongan, $minPoin, $tglBerakhir]);
                json_response(['status' => 'success', 'message' => 'Promo baru berhasil dibuat.']);
            }
            break;

        case 'delete_promo':
            $id = post_int('promo_id', 0);
            if ($id <= 0) {
                json_response(['status' => 'error', 'message' => 'ID promo tidak valid.'], 422);
                exit;
            }
            $pdo->prepare("DELETE FROM promo WHERE promo_id = ?")->execute([$id]);
            json_response(['status' => 'success', 'message' => 'Promo berhasil dihapus.']);
            break;

        /* ================= KURIR LIST (FOR ASSIGNMENT) ================= */
        case 'couriers':
            $stmt = $pdo->query("
                SELECT user_id, nama, no_hp, status_aktif, lat_kurir, long_kurir
                FROM users
                WHERE role = 'tukang_galon'
                ORDER BY nama ASC
            ");
            json_response(['status' => 'success', 'data' => $stmt->fetchAll()]);
            break;

        default:
            json_response(['status' => 'error', 'message' => 'Aksi admin tidak dikenal.'], 400);
            break;
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['status' => 'error', 'message' => 'Terjadi kesalahan server: ' . $e->getMessage()], 500);
}
