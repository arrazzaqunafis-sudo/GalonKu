<?php
require_once __DIR__ . '/../helpers.php';

try {
    $path = upload_file('foto_galon_bekas', 'uploads/galon-bekas');
    if (!$path) {
        json_response(['status' => 'error', 'message' => 'Foto galon bekas wajib diunggah.'], 422);
        exit;
    }
    json_response(['status' => 'success', 'path' => $path]);
} catch (Throwable $e) {
    json_response(['status' => 'error', 'message' => $e->getMessage()], 422);
}
