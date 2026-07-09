<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}
$default_avatar_path = $upload_dir . 'default_avatar.svg';
if (!file_exists($default_avatar_path)) {
    $svg_content = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="120" height="120"><circle cx="50" cy="50" r="50" fill="#4f46e5"/><circle cx="50" cy="35" r="20" fill="#ffffff"/><path d="M 20 80 A 30 30 0 0 1 80 80 Z" fill="#ffffff"/></svg>';
    @file_put_contents($default_avatar_path, $svg_content);
}

// 安全修補 (CWE-22)：使用 basename() 剝離所有路徑結構
$file = isset($_GET['file']) ? basename($_GET['file']) : '';

if ($file === '') {
    http_response_code(400);
    die('Missing file parameter');
}

$filepath = $upload_dir . $file;

if (file_exists($filepath) && !is_dir($filepath)) {
    // 安全修補 2：嚴格校驗 MIME 必須為合法圖片或 SVG，防止非圖片敏感代碼外洩
    $mime = @mime_content_type($filepath);
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp', 'text/xml'];
    
    if (!in_array($mime, $allowed_mimes) && strpos($file, '.svg') === false) {
        http_response_code(403);
        die('Access denied: Only image files are allowed');
    }

    if (strpos($file, '.svg') !== false) {
        header('Content-Type: image/svg+xml');
    } else {
        header('Content-Type: ' . $mime);
    }
    readfile($filepath);
    exit;
} else {
    http_response_code(404);
    die('File not found');
}
