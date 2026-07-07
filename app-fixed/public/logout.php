<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 寫入登出日誌
if (isset($_SESSION['user'])) {
    write_audit_log($pdo, "使用者登出");
}

// 修補重點：徹底銷毀 Session 資料，並清除瀏覽器端的 Session Cookie
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: /index.php");
exit;
