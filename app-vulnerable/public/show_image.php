<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

// 確保 uploads 目錄與預設大頭貼存在
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}
$default_avatar_path = $upload_dir . 'default_avatar.svg';
if (!file_exists($default_avatar_path)) {
    $svg_content = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" width="120" height="120"><circle cx="50" cy="50" r="50" fill="#4f46e5"/><circle cx="50" cy="35" r="20" fill="#ffffff"/><path d="M 20 80 A 30 30 0 0 1 80 80 Z" fill="#ffffff"/></svg>';
    @file_put_contents($default_avatar_path, $svg_content);
}

$file = $_GET['file'] ?? '';

if ($file === '') {
    http_response_code(400);
    die('Missing file parameter');
}

// 漏洞點 (CWE-22: Path Traversal)
// 直接將 url 參數中的 file 與目錄拼接，不做任何過濾或範圍限制，直接以 readfile 讀取輸出
$filepath = $upload_dir . $file;

if (file_exists($filepath) && !is_dir($filepath)) {
    // 輸出該檔案的 Content-Type 標頭，並直接將二進位流回傳
    header('Content-Type: ' . mime_content_type($filepath));
    readfile($filepath);
    exit;
} else {
    http_response_code(404);
    die('File not found');
}
