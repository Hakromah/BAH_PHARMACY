<?php
/**
 * Storage köprüsü — /public/storage/ üzerinden storage/ klasörüne güvenli erişim.
 *
 * Sadece images/ alt klasörüne ve izin verilen uzantılara erişim var.
 * Doğrudan /storage/ klasörüne .htaccess ile erişim kapalı;
 * bu dosya aracılığıyla görseller sunulur.
 *
 * Kullanım: /public/storage.php?f=images/product_xyz.jpg
 */

require_once dirname(__DIR__) . '/core/bootstrap.php';

$file = get('f');

// Null-byte ve path traversal koruması
$file = str_replace(['..', "\0", '//'], '', $file);
$file = ltrim($file, '/\\');

// Sadece images/ klasörüne izin ver
if (!str_starts_with($file, 'images/')) {
    http_response_code(403);
    exit('Access denied.');
}

$fullPath = STORAGE_PATH . '/' . $file;
$allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExt)) {
    http_response_code(403);
    exit('Invalid file type.');
}

if (!file_exists($fullPath) || !is_file($fullPath)) {
    http_response_code(404);
    exit('File not found.');
}

$mimeMap = [
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'webp' => 'image/webp',
    'gif' => 'image/gif',
];

header('Content-Type: ' . $mimeMap[$ext]);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: public, max-age=86400');
readfile($fullPath);
exit;
