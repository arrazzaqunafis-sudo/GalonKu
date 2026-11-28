# GalonKu

Aplikasi pemesanan, pengantaran, dan manajemen depot galon multi-platform berbasis **PHP Native**, **MySQL**, dan **PWA Mobile** (Pelanggan, Kurir, dan Admin Pusat).

---

## 📱 Ekosistem & Peran Pengguna

| Platform & Peran | Lingkungan | Fungsi Utama |
|---|---|---|
| **PWA Pelanggan (Masyarakat)** | Mobile Browser / Android / iOS | Pesan air isi ulang, beli galon baru, jual/tukar galon bekas (wajib foto), lacak kurir terdekat via GPS, klaim promo/poin, dan ajukan komplain. |
| **PWA Mitra Kurir (Tukang Galon)** | Mobile Browser / Android / iOS | Toggle status **Online/Offline**, radar pesanan masuk radius terdekat, navigasi Maps, validasi galon bekas, foto bukti antar, dan konfirmasi pembayaran COD. |
| **Panel Admin Pusat** | Web Browser (Desktop) | Kelola master data & stok ganda (Galon Isi vs Galon Kosong), pantau kurir aktif, verifikasi pembayaran, rekonsiliasi kas COD, dan tangani komplain. |

---

## ✨ Fitur Utama

- **Layanan Lengkap:** Beli air isi ulang, beli galon baru + wadah, serta jual/tukar galon bekas dengan unggah foto kondisi fisik.
- **Deteksi Kurir Terdekat (GPS Haversine <= 5 km):** Mencocokkan lokasi pelanggan dengan kurir berstatus `online` terdekat secara otomatis.
- **Pembayaran Fleksibel:** Mendukung **COD (Tunai)**, **QRIS**, dan **Transfer Bank** dengan sinkronisasi status pelunasan ke Admin.
- **Manajemen Stok Ganda:** Pencatatan stok **Galon Isi** dan **Galon Kosong** secara terpisah dan otomatis saat transaksi selesai.
- **Poin Loyalitas, Promo & Komplain:** Tambah poin otomatis tiap transaksi, penukaran voucher promo, serta pelaporan komplain dengan SLA **1x24 jam**.

---

## 🗄️ Entity Relationship Diagram (ERD) & Struktur Database (`db_galonku`)

Basis data relasional terdiri dari **9 entitas utama**:
- `users` — Data akun Masyarakat, Tukang Galon (`status_aktif`, `lat_kurir`, `long_kurir`), dan Admin.
- `alamat` — Daftar alamat & koordinat GPS pelanggan.
- `produk_galon` — Katalog produk beserta stok ganda (`stok_isi` dan `stok_kosong`).
- `promo` — Kode voucher potongan harga dan syarat poin loyalitas.
- `transaksi` & `detail_transaksi` — Header pesanan dan rincian item (termasuk `foto_galon_bekas`).
- `pembayaran` — Metode (`tunai`, `qris`, `transfer`) dan status pelunasan.
- `pengiriman` — Penugasan kurir, jarak tempuh (`jarak_km`), status antar, dan `foto_bukti_antar`.
- `laporan_komplain` — Keluhan air kotor/galon rusak beserta foto bukti (SLA 1x24 jam).

### Skema Relasional Tekstual (Blueprint ASCII ERD)

```text
+----------------------+       +-----------------------+       +-----------------------+
|        users         |1     N|        alamat         |       |     produk_galon      |
+----------------------+------>+-----------------------+       +-----------------------+
| PK user_id           |       | PK alamat_id          |       | PK produk_id          |
|    nama, email, no_hp|       | FK user_id            |       |    nama_produk        |
|    role, status_aktif|       |    label_alamat       |       |    kategori           |
|    poin_loyalitas    |       |    alamat_lengkap     |       |    harga              |
|    lat_kurir, long   |       |    latitude, longitude|       |    stok_isi, kosong   |
+----------------------+       |    is_utama           |       +-----------------------+
   |            |              +-----------------------+                   |
   | 1          | 1                        | 1                             | 1
   |            |                          v N                             v N
   |            |              +-----------------------+       +-----------------------+
   |            +------------->|       transaksi       |1     N|   detail_transaksi    |
   |            (pembeli_id)   +-----------------------+------>+-----------------------+
   |                           | PK transaksi_id       |       | PK detail_id          |
   |                           | FK pembeli_id         |       | FK transaksi_id       |
   |                           | FK alamat_id          |       | FK produk_id          |
   |                           | FK promo_id (Null)    |       |    jumlah, subtotal   |
   |                           |    total_harga        |       |    foto_galon_bekas   |
   |                           |    status_pesanan     |       +-----------------------+
   |                           +-----------------------+
   |                             | 1      | 1      | 1
   | 1                           |        |        |        +-----------------------+
   +-----------------------------+--------+--------+------->|         promo         |
   | (kurir_id)                  |        |        |  N    1+-----------------------+
   |                             v 1      v 1      v N      | PK promo_id           |
   |                   +---------------+ +---------------+  |    kode_promo         |
   |                   |  pembayaran   | |  pengiriman   |  |    potongan_harga     |
   |                   +---------------+ +---------------+  |    min_poin_syarat    |
   |                   | PK bayar_id   | | PK kirim_id   |  +-----------------------+
   |                   | FK trans_id   | | FK trans_id   |
   |                   |    metode     | | FK kurir_id   |
   |                   |    status     | |    jarak_km   |
   |                   +---------------+ |    foto_bukti |
   |                                     +---------------+
   | 1 (pelapor_id)                              |
   |                                             v N
   |                                   +-----------------------+
   +---------------------------------->|   laporan_komplain    |
                                       +-----------------------+
                                       | PK laporan_id         |
                                       | FK transaksi_id       |
                                       | FK pelapor_id         |
                                       |    kategori_masalah   |
                                       |    foto_bukti         |
                                       |    status_laporan     |
                                       +-----------------------+
```

### Kardinalitas & Logika Relasi Antar Entitas

- `users (1) -> (N) alamat`: Satu akun pengguna dapat menyimpan lebih dari satu titik alamat beserta koordinat GPS (misalnya Rumah, Kantor, atau Kost).
- `users (1) -> (N) transaksi & pengiriman`: Satu masyarakat dapat melakukan banyak transaksi pembelian maupun penjualan galon, sedangkan satu tukang galon dapat menangani banyak tugas pengiriman.
- `transaksi (1) -> (N) detail_transaksi (N) <- (1) produk_galon`: Relasi Many-to-Many dipecah melalui tabel `detail_transaksi` sehingga satu kali checkout dapat memuat beberapa item sekaligus (misal: beli 2 galon isi ulang sekaligus jual 1 galon kosong bekas).
- `transaksi (1) -> (1) pembayaran & pengiriman`: Setiap satu transaksi memiliki satu catatan pembayaran utama dan satu catatan tugas pengiriman kurir.
- `transaksi (1) -> (N) laporan_komplain`: Transaksi yang telah selesai dapat diajukan laporan komplain oleh pelanggan terkait dalam batas waktu SLA 1x24 jam.

---

## 🛠️ Teknologi yang Digunakan

- **Frontend Mobile (PWA):** HTML5, CSS3, Vanilla JavaScript, Service Worker, Geolocation API, Google Sign-In
- **Frontend Admin:** PHP & Web Dashboard
- **Backend & Database:** PHP Native REST API & MySQL / MariaDB (PDO)

---

## ⚡ Cara Jalankan di Komputer / Laptop Baru (Cukup 1 Menit)

1. Buka dan aktifkan **Laragon**, lalu klik **Start All** agar Apache dan MySQL menyala.
2. Pastikan folder proyek berada di dalam folder Laragon, misalnya `Laragon\www\GalonKu`.
3. Klik dua kali **`BUKA_GALONKU.bat`**.
4. Script akan membuat alias otomatis bernama `galonku-app`, menyiapkan database `db_galonku`, lalu membuka halaman Admin, Kurir, dan Pelanggan.

> Jika Laragon sudah aktif, cukup klik `BUKA_GALONKU.bat`—tidak perlu mengaktifkan server lagi secara manual.

---

## 🌐 Akses URL Aplikasi

Setelah Laragon aktif, Anda dapat membuka:

| Halaman | URL Akses |
|---|---|
| **Portal Home Laragon** | `http://localhost/` *(ada tombol cepat GalonKu)* |
| **Panel Admin Depot** | `http://localhost/galonku-app/admin.php` |
| **PWA Kurir** | `http://localhost/galonku-app/GalonKu_Mobile_Starter/pwa/courier/` |
| **PWA Pelanggan** | `http://localhost/galonku-app/GalonKu_Mobile_Starter/pwa/customer/` |

---

## 🔑 Akun Demo

- **Admin**: `admin@galonku.test`
- **Kurir**: `kurir@galonku.test`
- **Pelanggan**: `pelanggan@galonku.test`
