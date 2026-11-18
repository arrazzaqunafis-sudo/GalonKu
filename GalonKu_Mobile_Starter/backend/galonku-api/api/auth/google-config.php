<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/api.php';
require_once __DIR__ . '/../../includes/google_auth.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, ['success' => false, 'message' => 'Gunakan GET.']);
}

jsonResponse(200, [
    'success' => true,
    'client_id' => googleOAuthClientId(),
]);
