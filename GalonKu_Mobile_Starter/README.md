# GalonKu Mobile Starter — PWA + Android Native

Starter implementasi awal untuk:
- Backend PHP Native prosedural + MySQL.
- PWA pelanggan dan kurir (kerangka).
- Android Native Kotlin dengan product flavors `customer` dan `courier`.
- Fondasi layanan lokasi kurir menggunakan foreground service.

## Status
PWA pelanggan menyediakan katalog, checkout, dan pendaftaran/login Google. PWA kurir menyediakan login Google, status online, pencarian pesanan sekitar, penerimaan tugas, pendapatan/riwayat, dan konfirmasi pengantaran dengan foto bukti. Layanan pembayaran gateway, push notification, dan peta berubin belum disediakan.

## A. Backend PHP + MySQL (XAMPP)
1. Salin folder `backend/galonku-api` ke `C:\xampp\htdocs\galonku-api`.
2. Buat database `db_galonku` dan impor `db.sql`, `seed.sql`, lalu `backend/database/galonku_mobile.sql` melalui phpMyAdmin.
3. Atur `backend/galonku-api/config/database.php`.
4. Buat akun admin/kurir/pelanggan melalui proses registrasi atau seed yang dibuat pada tahap berikutnya.
5. Endpoint:
   - `POST /api/auth/login.php`
   - `GET /api/auth/google-config.php`
   - `POST /api/auth/google.php` (mendaftar atau masuk dengan Google ID token; role berasal dari aplikasi)
   - `POST /api/courier/location.php`
   - `POST /api/courier/online.php`
   - `GET|POST /api/courier/jobs.php`
   - `POST /api/courier/complete.php` (multipart dengan foto `foto_bukti`)

Untuk tes dari HP, server harus dapat diakses melalui HTTPS. Jangan menggunakan `localhost` dari HP untuk menunjuk ke komputer.

### Login Google
1. Buat project Google Cloud, siapkan OAuth consent screen, lalu buat OAuth Client ID tipe **Web application**. Tambahkan origin HTTPS situs PWA di **Authorized JavaScript origins**. Saat pengembangan browser lokal, gunakan `http://localhost`; alamat IP LAN melalui HTTP tidak diizinkan oleh Google Identity Services.
2. Server menyediakan Client ID publik Web OAuth dari `GALONKU_GOOGLE_CLIENT_ID` jika diatur; jika tidak, konfigurasi lokal memakai ID GalonKu yang ada di `includes/google_auth.php`. Client ID ini bukan rahasia. `google-config.php` mengirimkannya ke halaman login; API memverifikasi tanda tangan, issuer, audience, masa berlaku, serta email Google yang terverifikasi sebelum membuat sesi.
3. Untuk Android, buat juga OAuth Client ID tipe **Android** untuk package `id.galonku.mobile.customer` dan `id.galonku.mobile.courier`, menggunakan SHA-1 signing certificate masing-masing. Ambil SHA-1 debug dengan `.\gradlew signingReport` dari folder `android/`. Kedua APK meminta ID token menggunakan Web Client ID yang sama.
4. Build APK dengan Web Client ID yang sama tersedia sebagai environment `GALONKU_GOOGLE_CLIENT_ID` atau Gradle property `-PgalonkuGoogleClientId=...`. Jangan memasukkan Client Secret ke APK atau repository.
5. Pendaftaran pertama dilakukan dari aplikasi peran yang sesuai. Pelanggan mengisi nomor HP, alamat lengkap, dan mengambil lokasi GPS; kurir mengisi nomor HP. Email Google yang sudah terdaftar untuk peran lain tidak dapat dipakai untuk mengganti peran. Akun serta alamat disimpan di MySQL, jadi pengguna dapat masuk lagi dengan akun Google yang sama setelah aplikasi dihapus atau dipasang ulang.

## B. Aplikasi Native Android (dua APK terpisah)
1. Buka folder `android/` melalui Android Studio.
2. Pastikan Android SDK terpasang.
3. Default APK debug diarahkan ke `http://192.168.1.9/percobaan/GalonKu_Mobile_Starter/pwa/...` untuk pengujian Wi-Fi melalui Apache Laragon di port 80. Jika memakai PHP built-in server, gunakan document root `C:\laragon\www` agar awalan `/percobaan/` dipetakan ke folder yang benar:
   ```powershell
   php -S 0.0.0.0:8080 -t C:\laragon\www
   ```
   Jangan gunakan `C:\laragon\www\percobaan` sebagai document root untuk URL tersebut; PHP akan menyajikan halaman fallback dan stylesheet aplikasi tidak termuat. Untuk penggunaan data seluler, arahkan URL PWA tiap flavor ke domain HTTPS server publik melalui `DEFAULT_APP_URL` di `app/build.gradle.kts`.
4. Sync Gradle. Untuk mengaktifkan tombol Google pada APK, build dengan `-PgalonkuGoogleClientId=ID_WEB_CLIENT.apps.googleusercontent.com` atau set environment `GALONKU_GOOGLE_CLIENT_ID` sebelum Gradle dijalankan.
5. Build variant `customerDebug` dan `courierDebug` secara terpisah. Keduanya memiliki nama dan application ID berbeda, sehingga bisa dipasang bersamaan.
6. APK debug hasil build berada di `android/app/build/outputs/apk/customer/debug/` dan `android/app/build/outputs/apk/courier/debug/`. Salin APK ke HP, buka filenya, lalu izinkan pemasangan dari sumber tersebut jika Android meminta.
7. Login aplikasi kurir, aktifkan Online, dan berikan izin lokasi. Aplikasi memakai GPS perangkat dan menampilkan notifikasi permanen ketika layanan lokasi kurir aktif.

APK merupakan aplikasi Android yang terpasang terpisah dan tidak menampilkan address bar browser. Konten pelanggan/kurir dimuat di WebView native. Lokasi server dapat diubah lewat tombol **Server**. Debug APK mengizinkan HTTP untuk jaringan lokal; APK release hanya mengizinkan HTTPS.
Tombol Google pada APK menggunakan alur sign-in native Android. PWA di browser memakai Google Identity Services dan memerlukan origin HTTPS (kecuali `localhost`).

Android 11+ mengharuskan pengguna mengaktifkan izin lokasi latar belakang dari halaman Settings aplikasi. Jelaskan fungsi lokasi kepada kurir sebelum meminta izin. Layanan lokasi hanya dimulai dari aksi eksplisit kurir ketika aplikasi terlihat, dan menampilkan notifikasi permanen.

## C. PWA (dua aplikasi terpisah)
PWA pelanggan dan kurir berada di folder, URL, manifest, ikon, dan service worker terpisah. Pasang masing-masing secara terpisah dari browser:
- `http://localhost/percobaan/GalonKu_Mobile_Starter/pwa/customer/`
- `http://localhost/percobaan/GalonKu_Mobile_Starter/pwa/courier/`

Login akun seed kurir: `kurir@galonku.test` / `password`. Dari HP, gunakan alamat IP/LAN atau domain HTTPS server, bukan `localhost`. PWA hanya memperbarui lokasi ketika browser mengizinkan lokasi dan aplikasi berjalan; gunakan APK Native untuk pelacakan latar belakang yang lebih andal.

## Keamanan dan privasi
- GPS hanya aktif saat kurir menekan Online dan berhenti saat Offline/logout.
- Tampilkan notifikasi permanen ketika pelacakan aktif.
- Jangan tampilkan lokasi kurir ke pelanggan yang bukan pemesan terkait.
- Batasi data lokasi yang disimpan dan gunakan HTTPS.
- Token API disimpan hash-nya di database; aplikasi mengirim token mentah melalui header Authorization.
- Implementasi ini belum mencakup CSRF untuk seluruh panel web, rate limiting, refresh token rotation, push notification, atau audit trail penuh.
- PWA/browser tidak dapat menjamin GPS berlanjut ketika aplikasi ditutup/layar mati; gunakan APK Native untuk kebutuhan background.

## Struktur
- `backend/galonku-api`: PHP Native procedural API.
- `backend/database`: SQL extension untuk API token.
- `android`: satu project Android dengan dua flavor APK.
- `pwa`: kerangka PWA customer dan courier.

## Pengujian wajib sebelum dipakai nyata
1. Login role customer dan courier.
2. Token invalid/expired ditolak.
3. Kurir offline tidak dapat mengirim koordinat.
4. Lokasi tidak diperbarui setelah kurir offline/logout.
5. Pelanggan hanya melihat kurir untuk pesanannya.
6. Uji izin lokasi ditolak, approximate, precise, GPS mati, jaringan putus, baterai hemat, dan restart HP.
7. Uji pada beberapa versi Android dan perangkat nyata.
