<?php
$host = '127.0.0.1';
$username = 'root';
$password = '';
$dbname = 'db_galonku';

function initDatabaseIfNeeded(string $host, string $username, string $password, string $dbname): PDO
{
    // Coba koneksi langsung ke db_galonku
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Periksa apakah tabel users sudah ada
        $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
        if ($check) {
            return $pdo;
        }
    } catch (PDOException $e) {
        // Database belum ada atau koneksi gagal, coba inisialisasi otomatis
    }

    try {
        // Hubungkan ke server MySQL tanpa memilih database
        $serverPdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Buat database jika belum ada
        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $serverPdo->exec("USE `$dbname`");

        // Jalankan db.sql
        $dbSqlPath = __DIR__ . '/db.sql';
        if (file_exists($dbSqlPath)) {
            $sqlContent = file_get_contents($dbSqlPath);
            // Pisahkan query berdasarkan titik koma
            $serverPdo->exec($sqlContent);
        }

        // Jalankan seed.sql jika ada
        $seedSqlPath = __DIR__ . '/seed.sql';
        if (file_exists($seedSqlPath)) {
            $seedContent = file_get_contents($seedSqlPath);
            $serverPdo->exec($seedContent);
        }

        // Koneksikan kembali dengan konfigurasi lengkap
        return new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $ex) {
        http_response_code(500);
        header('Content-Type: application/json');
        die(json_encode([
            'status' => 'error',
            'message' => 'Koneksi database gagal: ' . $ex->getMessage() . '. Pastikan server MySQL di Laragon sudah dinyalakan (Start All).'
        ]));
    }
}

$pdo = initDatabaseIfNeeded($host, $username, $password, $dbname);
