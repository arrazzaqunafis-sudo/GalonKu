# GalonKu

Aplikasi backend dan dashboard demo GalonKu menggunakan PHP Native dan MySQL.

## Instalasi

1. Letakkan folder project di Laragon `www`.
2. Jalankan `mysql -u root < db.sql` lalu `mysql -u root < seed.sql`.
3. Buka `http://localhost/percobaan`; alamat utama akan langsung membuka dashboard admin.
   Untuk PWA pelanggan, buka `http://localhost/GalonKu/GalonKu_Mobile_Starter/pwa/customer/`.
   Untuk aplikasi kurir, buka `http://localhost/percobaan/GalonKu_Mobile_Starter/pwa/courier/`.
4. Pastikan folder `uploads/` dapat ditulis oleh PHP.

## Endpoint API

- `api/auth.php?action=register|login`
- `api/cari_kurir.php?lat=<latitude>&lng=<longitude>`
- `api/checkout.php`
- `api/upload_galon_bekas.php`
- `api/assign_kurir.php`
- `api/konfirmasi_selesai.php`
- `api/buat_komplain.php`
- `api/dashboard_admin.php`

Checkout mengirim data formulir `pembeli_id`, `alamat_id`, `metode_bayar`, `kode_promo`, dan JSON `items`.
