CREATE DATABASE IF NOT EXISTS db_galonku CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
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
