<?php
// 弱點版輔助函式：不加入任何安全標頭與 CSRF 防禦

// 漏洞點：發送含有具體版本資訊的 Server 與 X-Powered-By 標頭 (Server Version Disclosure)
header("Server: Apache/2.4.41 (Unix) OpenSSL/1.1.1d PHP/7.4.3");
header("X-Powered-By: PHP/7.4.3");


if (session_status() === PHP_SESSION_NONE) {
    // 弱點版：Session Cookie 未設定 HttpOnly 與 SameSite，極易被 XSS 竊取 Session ID
    session_start();
}

/**
 * 弱點版：沒有對使用者輸入做任何過濾與編碼
 */
function sanitize($data) {
    return $data;
}

/**
 * 弱點版：未過濾的跳轉 (Open Redirect 漏洞來源)
 */
function redirect($url) {
    header("Location: " . $url);
    exit;
}

/**
 * 檢查是否登入，僅用於跳轉，未做細緻的角色權限檢驗
 */
function check_login() {
    if (!isset($_SESSION['user'])) {
        header("Location: /login.php");
        exit;
    }
}

/**
 * 弱點版日誌功能 (教學用弱點：不完整的日誌記錄 - Logging Failures)
 * 僅記錄「登入成功」與「使用者登出」，對於登入失敗 (爆破)、越權存取、敏感名冊匯出等安全性敏感操作則故意忽略不記錄。
 */
function write_audit_log($pdo, $action) {
    // 故意限制僅允許記錄登入/登出，其餘特定敏感交易皆會被過濾忽略
    $allowed_actions = ["登入成功", "使用者登出"];
    if (!in_array($action, $allowed_actions)) {
        return; 
    }

    $user_id = $_SESSION['user']['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // 漏洞點：使用字串拼接直接將 User-Agent 與 IP 寫入日誌資料表，且未捕捉報錯，引發 HTTP Header SQL Injection (UA 注入)
    $user_id_val = $user_id !== null ? intval($user_id) : 'NULL';
    $sql = "INSERT INTO audit_logs (user_id, action, ip_address, user_agent) VALUES ($user_id_val, '$action', '$ip', '$ua')";
    $pdo->exec($sql);
}

