# GalonKu

Aplikasi pemesanan, manajemen depot galon, dan kurir terpadu berbasis PHP Native & MySQL.

---

# DOKUMEN SPESIFIKASI & PERANCANGAN PERANGKAT LUNAK TERPADU — APLIKASI GALONKU

Perancangan Sistem Layanan Pesan-Antar & Jual-Beli Galon Multi-Platform (Aplikasi Mobile Pelanggan, Aplikasi Mobile Tukang Galon, dan Website Admin Pusat) Menggunakan Metode SDLC Waterfall — Dilengkapi Diagram Visual ERD, Skema Relasional ASCII, Kamus Data Lengkap 9 Tabel, Skrip SQL DDL, Kueri Haversine GPS, Pemodelan OOP, UI/UX, dan Skenario Pengujian.

| Parameter Dokumen | Keterangan Spesifikasi |
|---|---|
| **Nama Sistem** | Aplikasi GalonKu (Ekosistem Layanan Air Minum & Galon Multi-Platform) |
| **Metodologi SDLC** | Waterfall Model (6 Tahapan: Analysis, Design, Implementation, Testing, Deployment, Maintenance) |
| **Target Platform** | Mobile App Android/iOS (Masyarakat & Tukang Galon) + Website Dashboard (Admin Pusat) |
| **Basis Data Pusat** | MySQL / PostgreSQL (9 Entitas Relasional Terintegrasi Real-Time) |
| **Fitur Inti** | Beli Isi Ulang & Galon Baru, Jual Galon Bekas (Wajib Foto), Deteksi Kurir Terdekat (GPS Haversine <= 5 km), Pembayaran COD/QRIS/Transfer, Manajemen Stok Ganda (Isi vs Kosong), Poin Loyalitas & Promo, Komplain SLA 1x24 Jam |

Dokumen master ini menyatukan seluruh spesifikasi teknis dan perancangan sistem Aplikasi GalonKu ke dalam satu referensi tunggal yang utuh. Seluruh kebutuhan fungsional, pembagian hak akses antar-platform, struktur basis data relasional, pemodelan berorientasi objek (OOP), rancangan antarmuka (UI/UX), kode implementasi SQL, hingga rencana pengujian sistem disusun secara berurutan mengikuti siklus hidup pengembangan perangkat lunak (SDLC) Metode Waterfall.

---

## TAHAP 1 | ANALYSIS (ANALISIS KEBUTUHAN & ARSITEKTUR MULTI-PLATFORM)

### 1.1 Pembagian Platform, Peran Pengguna & Aliran Data

Aplikasi GalonKu beroperasi dalam ekosistem multi-platform di mana Aplikasi Mobile (untuk Masyarakat dan Tukang Galon) serta Website (untuk Admin Pusat) saling terhubung secara real-time melalui satu basis data terpusat:

| Platform & Role Pengguna | Lingkungan | Hak Akses, Input & Pengelolaan Data |
|---|---|---|
| **Aplikasi Mobile — Masyarakat (Pelanggan)** | Android / iOS | Mengelola profil & saldo poin loyalitas, menyimpan banyak alamat beserta titik koordinat GPS rumah, melihat katalog stok galon isi, melakukan pembelian (isi ulang/galon baru) maupun penjualan/tukar galon bekas (wajib unggah foto kondisi), mengklaim kode promo, memantau posisi GPS kurir secara langsung, dan mengirim laporan komplain (foto bukti + deskripsi). |
| **Aplikasi Mobile — Tukang Galon (Kurir)** | Android / iOS | Mengatur saklar status kesiapan kerja (Online/Offline), mengirimkan pembaruan titik koordinat GPS kurir secara real-time, menerima daftar tugas pengiriman dalam radius terdekat, navigasi rute ke koordinat rumah pelanggan, memvalidasi fisik galon bekas di lapangan, mengunggah foto bukti serah terima, serta mengonfirmasi pelunasan pembayaran tunai (COD). |
| **Website — Admin Pusat** | Web Browser (Desktop) | Kontrol penuh seluruh data master pengguna, memantau peta sebaran kurir aktif (Online) secara real-time, mengelola inventaris stok ganda secara terpisah (stok galon isi vs stok galon kosong), memverifikasi pembayaran digital & rekonsiliasi kas tunai kurir, menangani laporan komplain (retur/selesai), serta mengatur program promo pelanggan setia. |

### 1.2 Spesifikasi Kebutuhan Fungsional Sistem

1. **Layanan Beli & Jual Galon:** Mendukung pembelian air isi ulang, pembelian galon baru beserta wadahnya, serta fitur jual/tukar galon bekas dengan kewajiban mengunggah foto kondisi fisik galon untuk divalidasi oleh tukang galon.
2. **Deteksi Lokasi GPS & Kurir Terdekat (Radius-Based):** Saat pelanggan mengaktifkan GPS pada ponsel, aplikasi menangkap koordinat Latitude dan Longitude lalu server menghitung jarak terhadap seluruh kurir berstatus Online menggunakan rumus Haversine (radius maksimal 5 km) untuk menampilkan dan menugaskan kurir terdekat.
3. **Pembayaran Fleksibel & Sinkronisasi Kas:** Mendukung pembayaran Tunai (COD), QRIS, dan Transfer Bank. Khusus pembayaran tunai, kurir wajib menekan tombol konfirmasi pelunasan pada aplikasi mobile saat serah terima barang agar laporan kas di Website Admin terbarui otomatis.
4. **Sistem Komplain Pelanggan (SLA 1x24 Jam):** Pelanggan dapat melaporkan kendala air kotor atau galon rusak dengan batas waktu maksimal (Service Level Agreement / SLA) 1x24 jam setelah transaksi berstatus selesai, wajib menyertakan foto bukti segel/kondisi air untuk diproses retur oleh Admin.
5. **Poin Loyalitas, Promo & Manajemen Stok Ganda:** Setiap transaksi yang selesai otomatis menambah poin loyalitas pelanggan yang dapat ditukarkan dengan voucher potongan harga. Di sisi gudang, sistem memisahkan pencatatan stok Galon Isi dan Galon Kosong secara terpisah.

---

## TAHAP 2 | DESIGN (ERD VISUAL & RELASIONAL, KAMUS DATA, OOP & UI/UX)

### 2.1 Visualisasi Entity Relationship Diagram (ERD)

Diagram relasional berikut memetakan seluruh 9 entitas utama basis data GalonKu lengkap dengan tipe data kolom, penanda Primary Key (PK), Foreign Key wajib (FK), Foreign Key opsional (FK?), serta garis kardinalitas hubungan antar tabel.

#### Skema Relasional Tekstual (Blueprint ASCII ERD)

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

### 2.2 Kardinalitas & Logika Relasi Antar Entitas

- `users (1) -> (N) alamat`: Satu akun pengguna dapat menyimpan lebih dari satu titik alamat beserta koordinat GPS (misalnya Rumah, Kantor, atau Kost).
- `users (1) -> (N) transaksi & pengiriman`: Satu masyarakat dapat melakukan banyak transaksi pembelian maupun penjualan galon, sedangkan satu tukang galon dapat menangani banyak tugas pengiriman.
- `transaksi (1) -> (N) detail_transaksi (N) <- (1) produk_galon`: Relasi Many-to-Many dipecah melalui tabel `detail_transaksi` sehingga satu kali checkout dapat memuat beberapa item sekaligus (misal: beli 2 galon isi ulang sekaligus jual 1 galon kosong bekas).
- `transaksi (1) -> (1) pembayaran & pengiriman`: Setiap satu transaksi memiliki satu catatan pembayaran utama dan satu catatan tugas pengiriman kurir.
- `transaksi (1) -> (N) laporan_komplain`: Transaksi yang telah selesai dapat diajukan laporan komplain oleh pelanggan terkait dalam batas waktu SLA 1x24 jam.

### 2.3 Kamus Data Lengkap (9 Tabel Utama Database)

Rincian struktur atribut, tipe data, aturan kunci (Key/Constraint), dan fungsi masing-masing tabel dalam basis data terpusat GalonKu:

#### Tabel 1: `users` (Data Akun Masyarakat, Admin & Tukang Galon)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `user_id` | `INT (Auto Increment)` | `PK` | ID unik untuk setiap pengguna di sistem. |
| `nama` | `VARCHAR(100)` | `NOT NULL` | Nama lengkap masyarakat, admin, atau tukang galon. |
| `email` | `VARCHAR(100)` | `UNIQUE, NOT NULL` | Alamat email aktif untuk autentikasi login. |
| `no_hp` | `VARCHAR(20)` | `NOT NULL` | Nomor telepon/WhatsApp aktif untuk komunikasi pengantaran. |
| `password` | `VARCHAR(255)` | `NOT NULL` | Kata sandi akun yang telah di-hash (enkripsi). |
| `role` | `ENUM('masyarakat','admin','tukang_galon')` | `NOT NULL` | Pembeda hak akses platform (Mobile User, Mobile Kurir, Web Admin). |
| `poin_loyalitas` | `INT` | `DEFAULT 0` | Akumulasi poin pelanggan setia dari setiap transaksi selesai. |
| `status_aktif` | `ENUM('online','offline')` | `DEFAULT 'offline'` | Status kesiapan tukang galon menerima pesanan di lapangan. |
| `lat_kurir` | `DECIMAL(10,8)` | `NULLABLE` | Koordinat Latitude GPS posisi kurir secara real-time. |
| `long_kurir` | `DECIMAL(11,8)` | `NULLABLE` | Koordinat Longitude GPS posisi kurir secara real-time. |
| `created_at` | `TIMESTAMP` | `DEFAULT NOW()` | Waktu pendaftaran akun dibuat. |

#### Tabel 2: `alamat` (Data Alamat & Titik GPS Pelanggan)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `alamat_id` | `INT (Auto Increment)` | `PK` | ID unik untuk setiap alamat tersimpan. |
| `user_id` | `INT` | `FK -> users` | Menghubungkan alamat dengan pemilik akunnya. |
| `label_alamat` | `VARCHAR(50)` | `NOT NULL` | Label lokasi seperti `'Rumah'`, `'Kantor'`, atau `'Kost'`. |
| `alamat_lengkap` | `TEXT` | `NOT NULL` | Detail nama jalan, nomor rumah, RT/RW, dan patokan lokasi. |
| `latitude` | `DECIMAL(10,8)` | `NOT NULL` | Koordinat Latitude GPS rumah warga untuk deteksi kurir terdekat. |
| `longitude` | `DECIMAL(11,8)` | `NOT NULL` | Koordinat Longitude GPS rumah warga untuk navigasi kurir. |
| `is_utama` | `BOOLEAN` | `DEFAULT FALSE` | Menandai apakah alamat ini menjadi alamat utama saat checkout. |

#### Tabel 3: `produk_galon` (Data Produk & Manajemen Stok Ganda)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `produk_id` | `INT (Auto Increment)` | `PK` | ID unik untuk setiap item produk/layanan galon. |
| `nama_produk` | `VARCHAR(100)` | `NOT NULL` | Nama produk (contoh: `'Isi Ulang Air Mineral 19L'`, `'Galon Baru + Isi'`). |
| `kategori` | `ENUM('isi_ulang','galon_baru','galon_bekas')` | `NOT NULL` | Membedakan transaksi beli air isi ulang, beli galon baru, atau jual galon bekas. |
| `harga` | `DECIMAL(10,2)` | `NOT NULL` | Harga satuan produk atau harga beli kembali galon bekas. |
| `stok_isi` | `INT` | `DEFAULT 0` | Jumlah fisik galon berisi air yang siap dijual di pangkalan. |
| `stok_kosong` | `INT` | `DEFAULT 0` | Jumlah fisik wadah galon kosong yang tersedia di gudang. |
| `deskripsi` | `TEXT` | `NULLABLE` | Keterangan tambahan mengenai merek atau spesifikasi produk. |

#### Tabel 4: `promo` (Data Voucher & Reward Pelanggan Setia)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `promo_id` | `INT (Auto Increment)` | `PK` | ID unik promo. |
| `kode_promo` | `VARCHAR(30)` | `UNIQUE, NOT NULL` | Kode voucher yang diklaim pelanggan saat transaksi. |
| `nama_promo` | `VARCHAR(100)` | `NOT NULL` | Judul promo (misal: `'Diskon Pelanggan Setia Rp5.000'`). |
| `potongan_harga` | `DECIMAL(10,2)` | `NOT NULL` | Nominal potongan harga yang mengurangi total transaksi. |
| `min_poin_syarat` | `INT` | `DEFAULT 0` | Syarat minimum poin loyalitas untuk menukarkan promo ini. |
| `tanggal_berakhir` | `DATE` | `NOT NULL` | Batas tanggal masa berlaku promo. |

#### Tabel 5: `transaksi` (Data Header Pesanan)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `transaksi_id` | `INT (Auto Increment)` | `PK` | ID unik setiap transaksi. |
| `pembeli_id` | `INT` | `FK -> users` | ID masyarakat yang membuat pesanan. |
| `alamat_id` | `INT` | `FK -> alamat` | ID alamat tujuan pengantaran atau penjemputan galon. |
| `promo_id` | `INT` | `FK -> promo (NULL)` | ID promo yang dipakai (kosong/NULL jika tidak memakai promo). |
| `total_harga` | `DECIMAL(12,2)` | `NOT NULL` | Total biaya akhir setelah dikurangi potongan promo. |
| `status_pesanan` | `ENUM('menunggu_bayar','diproses','dikirim','selesai','dibatalkan')` | `DEFAULT 'menunggu_bayar'` | Status alur pesanan secara keseluruhan. |
| `created_at` | `TIMESTAMP` | `DEFAULT NOW()` | Waktu pesanan dibuat oleh pelanggan. |

#### Tabel 6: `detail_transaksi` (Data Rincian Item Pesanan)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `detail_id` | `INT (Auto Increment)` | `PK` | ID unik baris item pesanan. |
| `transaksi_id` | `INT` | `FK -> transaksi` | Menghubungkan item dengan transaksi induknya. |
| `produk_id` | `INT` | `FK -> produk_galon` | Jenis produk galon yang dipilih. |
| `jumlah` | `INT` | `NOT NULL` | Kuantitas galon yang dipesan atau dijual. |
| `subtotal` | `DECIMAL(12,2)` | `NOT NULL` | Hasil perkalian harga satuan dengan jumlah. |
| `foto_galon_bekas` | `VARCHAR(255)` | `NULLABLE` | URL/path foto kondisi galon jika warga memilih kategori jual galon bekas. |

#### Tabel 7: `pembayaran` (Data Pembayaran & Verifikasi Kas)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `pembayaran_id` | `INT (Auto Increment)` | `PK` | ID unik catatan pembayaran. |
| `transaksi_id` | `INT` | `FK -> transaksi (UNIQUE)` | Relasi One-to-One ke transaksi terkait. |
| `metode_bayar` | `ENUM('tunai','qris','transfer')` | `NOT NULL` | Metode pembayaran yang dipilih pelanggan (COD Tunai atau Digital). |
| `status_bayar` | `ENUM('pending','lunas','gagal')` | `DEFAULT 'pending'` | Status pelunasan (dikonfirmasi kurir saat COD atau otomatis via gateway). |
| `waktu_bayar` | `TIMESTAMP` | `NULLABLE` | Waktu saat dana dikonfirmasi lunas. |

#### Tabel 8: `pengiriman` (Data Penugasan Kurir & Pelacakan)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `pengiriman_id` | `INT (Auto Increment)` | `PK` | ID unik pengiriman. |
| `transaksi_id` | `INT` | `FK -> transaksi (UNIQUE)` | Relasi One-to-One ke transaksi yang diantar. |
| `kurir_id` | `INT` | `FK -> users (NULL)` | ID tukang galon terdekat yang menerima tugas. |
| `status_antar` | `ENUM('mencari_kurir','menuju_lokasi','sampai_tujuan')` | `DEFAULT 'mencari_kurir'` | Status pergerakan kurir di lapangan. |
| `jarak_km` | `DECIMAL(5,2)` | `DEFAULT 0.00` | Jarak pengantaran dalam kilometer hasil perhitungan GPS Haversine. |
| `foto_bukti_antar` | `VARCHAR(255)` | `NULLABLE` | Foto bukti serah terima galon di lokasi pelanggan. |
| `waktu_update` | `TIMESTAMP` | `DEFAULT NOW()` | Waktu pembaruan status terakhir oleh kurir. |

#### Tabel 9: `laporan_komplain` (Data Keluhan & Retur SLA 1x24 Jam)

| Nama Kolom | Tipe Data | Kunci / Aturan | Keterangan Fungsi |
|---|---|---|---|
| `laporan_id` | `INT (Auto Increment)` | `PK` | ID unik laporan komplain. |
| `transaksi_id` | `INT` | `FK -> transaksi` | Transaksi asal barang yang dikeluhkan. |
| `pelapor_id` | `INT` | `FK -> users` | ID masyarakat yang mengajukan komplain. |
| `kategori_masalah` | `ENUM('air_kotor','galon_rusak')` | `NOT NULL` | Jenis keluhan kualitas air atau kebocoran/kerusakan wadah. |
| `deskripsi` | `TEXT` | `NOT NULL` | Penjelasan detail masalah yang dialami pelanggan. |
| `foto_bukti` | `VARCHAR(255)` | `NOT NULL` | Foto bukti segel galon/kondisi air yang wajib dilampirkan. |
| `status_laporan` | `ENUM('diproses','diretur','selesai')` | `DEFAULT 'diproses'` | Status tindak lanjut oleh Admin di Website. |
| `created_at` | `TIMESTAMP` | `DEFAULT NOW()` | Waktu komplain dikirim (divalidasi maksimal 24 jam dari selesai transaksi). |

### 2.4 Perancangan Berorientasi Objek (OOP)

Pemodelan perangkat lunak menggunakan paradigma Object-Oriented Programming (OOP) dengan menerapkan empat pilar utama: Inheritance (pewarisan superclass `User`), Encapsulation (hak akses `private`/`protected`), Polymorphism, dan Abstraction:

| Nama Class | Atribut (Properties) | Method Utama (Behavior) |
|---|---|---|
| `User` (Superclass) | `protected userId, nama, email, noHp; private password` | `login()`, `logout()`, `updateProfil()` |
| `Masyarakat` (extends `User`) | `private poinLoyalitas, daftarAlamat` | `buatPesanan()`, `jualGalonBekas()`, `klaimPromo()`, `buatKomplain()` |
| `Admin` (extends `User`) | `private kodeAdmin` | `kelolaStokGanda()`, `verifikasiPembayaran()`, `prosesKomplain()`, `aturPromo()` |
| `TukangGalon` (extends `User`) | `private statusAktif, latKurir, longKurir` | `terimaTugas()`, `updateLokasiGPS()`, `konfirmasiBayarTunai()`, `unggahBuktiAntar()` |
| `ProdukGalon` | `private produkId, namaProduk, kategori, harga, stokIsi, stokKosong` | `prosesTransaksiStok(int jumlah)`, `validasiKondisiBekas(String foto)` |
| `Pengiriman` | `private pengirimanId, transaksi, kurir, statusAntar, fotoBuktiAntar, jarakKm` | `assignKurirTerdekat()`, `hitungHaversine()`, `selesaikanPengantaran()` |
| `LaporanKomplain` | `private laporanId, transaksi, pelapor, kategoriMasalah, fotoBukti, statusLaporan` | `validasiSLA24Jam()`, `kirimLaporan()`, `updatePenangananAdmin()` |

### 2.5 Perancangan Antarmuka Pengguna (UI/UX Design)

| Antarmuka Platform | Konsep Desain & Komponen Utama UI/UX |
|---|---|
| **Aplikasi Mobile — Masyarakat** | Mengusung gaya Clean Minimalist dengan dominasi warna biru air (`#0077C0` / `#0284C7`) dan putih bersih. Halaman beranda menampilkan kartu saldo poin loyalitas, tombol aksi cepat (Beli Isi Ulang, Beli Galon Baru, Jual Galon Bekas), serta peta mini GPS otomatis untuk mendeteksi tukang galon terdekat beserta estimasi jarak (km). |
| **Aplikasi Mobile — Tukang Galon** | Antarmuka ergonomis berfokus pada kecepatan operasional lapangan: saklar besar status kesiapan (Online/Offline), radar notifikasi pesanan masuk lengkap dengan jarak tempuh, integrasi navigasi peta menuju koordinat rumah warga, fitur kamera langsung untuk unggah bukti serah terima, dan tombol konfirmasi pelunasan tunai (COD). |
| **Website — Dashboard Admin** | Tata letak analitik modern berbasis desktop dengan navigasi sidebar, kartu ringkasan penjualan harian, grafik tren transaksi bulanan, peta interaktif sebaran kurir Online secara real-time, tabel pemantauan stok ganda (Galon Isi vs Galon Kosong), serta panel verifikasi komplain dan retur. |

---

## TAHAP 3 | IMPLEMENTATION (ARSITEKTUR TEKNOLOGI, SKRIP SQL & LOGIKA GPS)

### 3.1 Tech Stack & Integrasi API Eksternal

| Lapisan Arsitektur | Teknologi & Spesifikasi Integrasi |
|---|---|
| **Aplikasi Mobile (Pelanggan & Kurir)** | PWA / Flutter / React Native (Android & iOS) dilengkapi paket Geolocator untuk pelacakan koordinat GPS real-time dan akses kamera perangkat. |
| **Website Admin Pusat** | Web Dashboard (PHP / React.js / Vue.js / Laravel Blade) untuk membangun antarmuka Dashboard Admin yang responsif dan interaktif. |
| **Backend & Database Pusat** | RESTful API (PHP Native / Node.js / Laravel) dengan database relasional MySQL / PostgreSQL sebagai pusat sinkronisasi data. |
| **Integrasi Layanan Eksternal** | Google Maps / Mapbox API (peta interaktif & Reverse Geocoding), Payment Gateway Midtrans / Xendit (QRIS & Transfer Bank), serta Firebase Cloud Messaging (notifikasi real-time pesanan masuk). |

### 3.2 Skrip SQL Pembuatan Database (`DDL CREATE TABLE`)

Berikut adalah skrip lengkap Data Definition Language (DDL) siap eksekusi untuk membangun seluruh struktur tabel, tipe data, nilai default, dan relasi Foreign Key pada RDBMS MySQL / MariaDB:

```sql
CREATE DATABASE IF NOT EXISTS db_galonku;
USE db_galonku;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('masyarakat', 'admin', 'tukang_galon') NOT NULL,
    poin_loyalitas INT DEFAULT 0,
    status_aktif ENUM('online', 'offline') DEFAULT 'offline',
    lat_kurir DECIMAL(10, 8) NULL,
    long_kurir DECIMAL(11, 8) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE alamat (
    alamat_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    label_alamat VARCHAR(50) NOT NULL,
    alamat_lengkap TEXT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    is_utama BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE produk_galon (
    produk_id INT AUTO_INCREMENT PRIMARY KEY,
    nama_produk VARCHAR(100) NOT NULL,
    kategori ENUM('isi_ulang', 'galon_baru', 'galon_bekas') NOT NULL,
    harga DECIMAL(10, 2) NOT NULL,
    stok_isi INT DEFAULT 0,
    stok_kosong INT DEFAULT 0,
    deskripsi TEXT NULL
);

CREATE TABLE promo (
    promo_id INT AUTO_INCREMENT PRIMARY KEY,
    kode_promo VARCHAR(30) UNIQUE NOT NULL,
    nama_promo VARCHAR(100) NOT NULL,
    potongan_harga DECIMAL(10, 2) NOT NULL,
    min_poin_syarat INT DEFAULT 0,
    tanggal_berakhir DATE NOT NULL
);

CREATE TABLE transaksi (
    transaksi_id INT AUTO_INCREMENT PRIMARY KEY,
    pembeli_id INT NOT NULL,
    alamat_id INT NOT NULL,
    promo_id INT NULL,
    total_harga DECIMAL(12, 2) NOT NULL,
    status_pesanan ENUM('menunggu_bayar', 'diproses', 'dikirim', 'selesai', 'dibatalkan') DEFAULT 'menunggu_bayar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pembeli_id) REFERENCES users(user_id),
    FOREIGN KEY (alamat_id) REFERENCES alamat(alamat_id),
    FOREIGN KEY (promo_id) REFERENCES promo(promo_id)
);

CREATE TABLE detail_transaksi (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    produk_id INT NOT NULL,
    jumlah INT NOT NULL,
    subtotal DECIMAL(12, 2) NOT NULL,
    foto_galon_bekas VARCHAR(255) NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(transaksi_id) ON DELETE CASCADE,
    FOREIGN KEY (produk_id) REFERENCES produk_galon(produk_id)
);

CREATE TABLE pembayaran (
    pembayaran_id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT UNIQUE NOT NULL,
    metode_bayar ENUM('tunai', 'qris', 'transfer') NOT NULL,
    status_bayar ENUM('pending', 'lunas', 'gagal') DEFAULT 'pending',
    waktu_bayar TIMESTAMP NULL,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(transaksi_id) ON DELETE CASCADE
);

CREATE TABLE pengiriman (
    pengiriman_id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT UNIQUE NOT NULL,
    kurir_id INT NULL,
    status_antar ENUM('mencari_kurir', 'menuju_lokasi', 'sampai_tujuan') DEFAULT 'mencari_kurir',
    jarak_km DECIMAL(5, 2) DEFAULT 0.00,
    foto_bukti_antar VARCHAR(255) NULL,
    waktu_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(transaksi_id) ON DELETE CASCADE,
    FOREIGN KEY (kurir_id) REFERENCES users(user_id)
);

CREATE TABLE laporan_komplain (
    laporan_id INT AUTO_INCREMENT PRIMARY KEY,
    transaksi_id INT NOT NULL,
    pelapor_id INT NOT NULL,
    kategori_masalah ENUM('air_kotor', 'galon_rusak') NOT NULL,
    deskripsi TEXT NOT NULL,
    foto_bukti VARCHAR(255) NOT NULL,
    status_laporan ENUM('diproses', 'diretur', 'selesai') DEFAULT 'diproses',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaksi_id) REFERENCES transaksi(transaksi_id),
    FOREIGN KEY (pelapor_id) REFERENCES users(user_id)
);
```

### 3.3 Kueri Deteksi Tukang Galon Terdekat (Formula Haversine)

Kueri SQL berikut menghitung jarak permukaan bumi (dalam kilometer) antara koordinat GPS pelanggan (`:lat_user`, `:long_user`) dengan seluruh mitra tukang galon yang berstatus `'online'` dalam radius maksimal 5 km, diurutkan dari jarak terdekat:

```sql
SELECT
    user_id,
    nama,
    no_hp,
    (6371 * acos(
        cos(radians(:lat_user)) * cos(radians(lat_kurir)) * cos(radians(long_kurir) - radians(:long_user)) +
        sin(radians(:lat_user)) * sin(radians(lat_kurir))
    )) AS jarak_km
FROM users
WHERE role = 'tukang_galon'
  AND status_aktif = 'online'
HAVING jarak_km <= 5
ORDER BY jarak_km ASC;
```

---

## TAHAP 4 | TESTING (RENCANA PENGUJIAN SISTEM / BLACK-BOX TESTING)

Pengujian fungsionalitas dilakukan untuk memastikan seluruh alur bisnis lintas platform berjalan akurat sesuai spesifikasi kebutuhan:

| Kode Uji | Skenario Pengujian | Input / Aksi | Hasil yang Diharapkan | Status |
|---|---|---|---|---|
| **TC-01** | Deteksi Kurir Terdekat | Pelanggan menyalakan GPS di HP dan membuka halaman pesan galon. | Sistem menampilkan daftar tukang galon online dalam radius <= 5 km terurut dari yang terdekat. | Valid |
| **TC-02** | Sinkronisasi Stok Ganda | Transaksi isi ulang 2 galon berstatus `'Selesai'`. | Stok Galon Isi berkurang 2 dan Stok Galon Kosong bertambah 2 secara otomatis di Website Admin. | Valid |
| **TC-03** | Jual Galon Bekas | Warga mengajukan jual galon kosong tanpa mengunggah foto kondisi. | Sistem menolak pengajuan dan mewajibkan unggah foto kondisi galon terlebih dahulu. | Valid |
| **TC-04** | Pembayaran Tunai (COD) | Kurir menekan tombol `'Lunas Tunai'` dan unggah foto bukti antar. | Status pembayaran berubah menjadi Lunas dan saldo kas kurir bertambah di Website Admin. | Valid |
| **TC-05** | Validasi SLA Komplain | Warga mencoba melapor air kotor > 24 jam setelah pesanan selesai. | Sistem otomatis menonaktifkan tombol komplain karena melewati batas waktu SLA 1x24 jam. | Valid |

---

## TAHAP 5 | DEPLOYMENT (PENERAPAN & PELUNCURAN MULTI-PLATFORM)

1. **Infrastruktur Server & Keamanan:** Konfigurasi server produksi berbasis Cloud VPS, instalasi database terpusat MySQL/PostgreSQL, serta pengamanan seluruh jalur komunikasi RESTful API menggunakan sertifikat SSL/HTTPS.
2. **Distribusi Multi-Platform:** Publikasi paket Aplikasi Mobile (Pelanggan & Tukang Galon) ke Google Play Store dan Apple App Store, serta peluncuran Website Dashboard Admin pada domain resmi pangkalan.
3. **Pelatihan Operasional (Onboarding):** Pelatihan singkat kepada admin pangkalan dan mitra tukang galon terkait operasional saklar mode Online GPS, prosedur pengambilan foto bukti antar, dan rekonsiliasi setoran kas harian.

---

## TAHAP 6 | MAINTENANCE (PEMELIHARAAN & PENGEMBANGAN BERKELANJUTAN)

1. **Pemeliharaan Rutin (Preventive Maintenance):** Pemantauan kinerja server secara berkala, optimasi kecepatan respon API lokasi GPS, serta pencadangan (backup) otomatis basis data transaksi setiap hari.
2. **Perbaikan Sistem (Corrective Maintenance):** Penanganan cepat (bug fixing) apabila ditemukan kendala sinkronisasi sinyal GPS di lapangan atau ketidaksesuaian tampilan pada variasi ukuran layar perangkat pengguna.
3. **Pengembangan Lanjutan (Adaptive Maintenance):** Pengembangan fitur lanjutan di masa mendatang seperti jadwal langganan pengantaran galon otomatis mingguan/bulanan serta integrasi kanal dompet digital tambahan.

---

## ⚡ Cara Jalankan di Komputer / Laptop Baru (Cukup 1 Menit)

1. Buka dan aktifkan Laragon, lalu klik **Start All** agar Apache dan MySQL menyala.
2. Pastikan folder proyek berada di dalam folder Laragon, misalnya `Laragon\www\GalonKu`.
3. Klik dua kali **`BUKA_GALONKU.bat`**.
4. Script akan membuat alias otomatis bernama `galonku-app`, menyiapkan database `db_galonku`, lalu membuka halaman Admin, Kurir, dan Pelanggan.

Jika Laragon sudah aktif, cukup klik `BUKA_GALONKU.bat`—tidak perlu mengaktifkan server lagi secara manual.

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
