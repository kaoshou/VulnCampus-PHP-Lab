<?php
// 修補重點：使用 try-catch 包覆資料庫連線，防止在連線失敗時外洩敏感帳密或堆疊追蹤資訊

$host = 'db';
$db   = 'fixed_db';
$user = 'root';
$pass = 'rootpassword';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    // 關閉模擬預處理以增加 SQLi 的防範強度
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // 將詳細錯誤寫入伺服器內部的 error log
    error_log("Database connection failed: " . $e->getMessage());
    
    // 向前端用戶顯示安全、無害的錯誤訊息
    http_response_code(500);
    echo '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">';
    echo '  <h2>系統維護中</h2>';
    echo '  <p>目前無法與資料庫建立連線，請稍後再試。若問題持續，請聯絡系統管理員。</p>';
    echo '</div>';
    exit;
}
