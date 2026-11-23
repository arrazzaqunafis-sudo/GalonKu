<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $host = getenv('GALONKU_DB_HOST') ?: '127.0.0.1';
    $port = getenv('GALONKU_DB_PORT') ?: '3306';
    $name = getenv('GALONKU_DB_NAME') ?: 'db_galonku';
    $user = getenv('GALONKU_DB_USER') ?: 'root';
    $pass = getenv('GALONKU_DB_PASS') !== false ? (string)getenv('GALONKU_DB_PASS') : '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Fallback: sertakan koneksi utama
        require_once __DIR__ . '/../../../../koneksi.php';
        global $pdo;
        return $pdo;
    }
}
