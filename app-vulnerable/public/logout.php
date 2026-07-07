<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 記錄登出日誌 (allowed_actions 中包含了 "使用者登出")
if (isset($_SESSION['user'])) {
    write_audit_log($pdo, "使用者登出");
}

// 教學用弱點：登出時未完整清除與銷毀 Session 檔案，且沒有清除瀏覽器的 Session Cookie
unset($_SESSION['user']);

header("Location: /index.php");
exit;

