<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function jsonResponse(int $status, array $payload): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requestJson(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

function bearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }
    return null;
}

function authenticatedUser(PDO $pdo, array $allowedRoles = []): array
{
    $token = bearerToken();
    if (!$token) jsonResponse(401, ['success' => false, 'message' => 'Token diperlukan.']);

    $stmt = $pdo->prepare(
        "        SELECT u.user_id, u.nama, u.role, u.status_aktif, t.token_id
         FROM api_tokens t
         JOIN users u ON u.user_id = t.user_id
         WHERE t.token_hash = :hash AND t.expires_at > NOW()
         LIMIT 1"
    );
    $stmt->execute([':hash' => hash('sha256', $token)]);
    $user = $stmt->fetch();

    if (!$user) jsonResponse(401, ['success' => false, 'message' => 'Token tidak valid atau kedaluwarsa.']);
    if ($allowedRoles && !in_array($user['role'], $allowedRoles, true)) {
        jsonResponse(403, ['success' => false, 'message' => 'Akses tidak diizinkan.']);
    }

    $pdo->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE token_id = :id")
        ->execute([':id' => $user['token_id']]);

    return $user;
}
