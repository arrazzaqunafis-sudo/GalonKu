USE db_galonku;

INSERT INTO users (nama, email, no_hp, password, role, poin_loyalitas, status_aktif, lat_kurir, long_kurir) VALUES
('Admin GalonKu', 'admin@galonku.test', '081000000001', '$2y$12$fP6AQIhEn/UyzCaMhqpq..y4NiRIXL7nQrQGuZn7GqUv7oUHLmeme', 'admin', 0, 'offline', NULL, NULL),
('Pelanggan Contoh', 'pelanggan@galonku.test', '081000000002', '$2y$12$fP6AQIhEn/UyzCaMhqpq..y4NiRIXL7nQrQGuZn7GqUv7oUHLmeme', 'masyarakat', 100, 'offline', NULL, NULL),
('Kurir Bandung', 'kurir@galonku.test', '081000000003', '$2y$12$fP6AQIhEn/UyzCaMhqpq..y4NiRIXL7nQrQGuZn7GqUv7oUHLmeme', 'tukang_galon', 0, 'online', -6.914744, 107.609810);

INSERT INTO alamat (user_id, label_alamat, alamat_lengkap, latitude, longitude, is_utama)
VALUES (2, 'Rumah', 'Jl. Contoh No. 10, Bandung', -6.917500, 107.619100, TRUE);

INSERT INTO produk_galon (nama_produk, kategori, harga, stok_isi, stok_kosong, deskripsi) VALUES
('Isi Ulang 19 Liter', 'isi_ulang', 15000.00, 100, 20, 'Air galon isi ulang 19 liter'),
('Galon Baru 19 Liter', 'galon_baru', 80000.00, 50, 0, 'Galon baru siap pakai'),
('Galon Bekas 19 Liter', 'galon_bekas', 25000.00, 0, 30, 'Layanan tukar atau jual galon bekas');
