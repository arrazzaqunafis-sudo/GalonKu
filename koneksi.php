<<<<<<< HEAD
<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'db_galonku';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']));
}
=======
<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'db_galonku';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']));
}
>>>>>>> 9cf1af783b510455f23b66014c601d7e56ac70da
