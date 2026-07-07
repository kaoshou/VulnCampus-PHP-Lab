<?php
// 教學用弱點：這裡在發生連線錯誤時未做任何 Catch 處理，會直接將資料庫帳密和 System Trace 暴露在網頁畫面上

$host = 'db';
$db   = 'vuln_db';
$user = 'root';
$pass = 'rootpassword';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    // 這裡我們預設啟用模擬預處理，更易於在某些特定情境下展示 SQLi 的極限
    PDO::ATTR_EMULATE_PREPARES   => true,
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// 直接建立連線，不進行 try-catch 處理
$pdo = new PDO($dsn, $user, $pass, $options);
