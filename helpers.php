<?php
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
}

function post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
}

function upload_file(string $field, string $directory): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = mime_content_type($_FILES[$field]['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new InvalidArgumentException('Foto harus berformat JPG, PNG, atau WebP.');
    }
    if ($_FILES[$field]['size'] > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Ukuran foto maksimal 5 MB.');
    }

    if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
        throw new RuntimeException('Folder upload tidak dapat dibuat.');
    }

    $name = bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    $relative = rtrim($directory, '/') . '/' . $name;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], __DIR__ . '/' . $relative)) {
        throw new RuntimeException('Foto gagal diunggah.');
    }
    return $relative;
}
