<?php
// 修補重點：
// 1. 設定 Session Cookie 安全參數 (HttpOnly, SameSite=Lax, Secure則視環境)
// 2. 設定安全回應標頭 (CSP, X-Frame-Options, X-Content-Type-Options)
// 3. 實作防禦 XSS 的 htmlspecialchars 封裝
// 4. 實作 CSRF Token 的生成與校驗
// 5. 實作 Open Redirect 白名單跳轉防護
// 6. 個資遮罩與 IP 驗證防護

if (session_status() === PHP_SESSION_NONE) {
    // 修補 Session Cookie 安全設定
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']), // 若是 HTTPS 則開啟 Secure 屬性
        'httponly' => true,                 // 限制 JS 讀取 Session Cookie，防範 XSS 竊取 Session
        'samesite' => 'Lax'                 // 防禦 CSRF 跨站請求偽造
    ]);
    session_start();
}

// 🟢 安全防護：會話閒置逾時 (Session Idle Timeout) 防範 Session 登入時間過長
// 限制閒置時間為 10 分鐘 (600 秒)
if (isset($_SESSION['user'])) {
    $now = time();
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > 600)) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['timeout_error'] = '您的會話已因閒置超過 10 分鐘而逾時，請重新登入！';
        header("Location: /login.php");
        exit;
    }
    $_SESSION['last_activity'] = $now; // 更新最後活動時間
}

// 輸出安全回應標頭 (Security Headers)
header_remove("X-Powered-By");                                          // 移除 X-Powered-By 版本標頭
header("Server: WebServer");                                            // 混淆 Server 版本資訊，防止版本洩漏
header("X-Frame-Options: DENY");                                        // 防範點擊劫持 (Clickjacking)
header("X-Content-Type-Options: nosniff");                              // 防範 MIME 類型嗅探
header("Referrer-Policy: strict-origin-when-cross-origin");             // 限制 Referrer 洩漏
header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload"); // 強制 HTTPS 連線 (HSTS)
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://code.jquery.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data:; frame-ancestors 'none';"); // 安全 CSP 防範 XSS 與 Frame 嵌入

/**
 * XSS 安全輸出編碼 (修補重點)
 */
function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * 產生 CSRF Token
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 校驗 CSRF Token
 */
function verify_csrf_token($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * 個資遮罩 (例如手機號碼、身分證字號)
 */
function mask_data($type, $value) {
    if (empty($value)) return '';
    if ($type === 'national_id') {
        // A123456789 -> A123***789
        return substr($value, 0, 4) . '***' . substr($value, 7);
    }
    if ($type === 'phone') {
        // 0912-345-678 -> 0912-***-678
        return substr($value, 0, 5) . '***' . substr($value, 8);
    }
    if ($type === 'email') {
        // admin@vulncampus.local -> a***n@vulncampus.local
        $parts = explode('@', $value);
        if (count($parts) < 2) return $value;
        $name = $parts[0];
        $domain = $parts[1];
        if (strlen($name) <= 2) {
            return '*@' . $domain;
        }
        return substr($name, 0, 1) . '***' . substr($name, -1) . '@' . $domain;
    }
    return $value;
}

/**
 * 安全的跳轉 (防範 Open Redirect)
 */
function safe_redirect($url) {
    // 白名單跳轉路徑：若不以 / 開頭且非本機允許網域，則強制回首頁
    if (preg_match('/^(https?:)?\/\//i', $url)) {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== 'localhost' && $host !== $_SERVER['HTTP_HOST']) {
            $url = '/index.php';
        }
    }
    header("Location: " . $url);
    exit;
}

/**
 * 權限檢查 (包含登入與角色)
 */
function check_auth($allowed_roles = []) {
    // 安全防禦：對所有需要登入認證的敏感頁面，強制輸出停用瀏覽器快取 (Disable Caching) 標頭
    // no-store 確保敏感個資絕對不會被寫入本機磁碟快取，防範共用電腦下的敏感資訊洩漏
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    if (!isset($_SESSION['user'])) {
        header("Location: /login.php");
        exit;
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['user']['role'], $allowed_roles)) {
        http_response_code(403);
        echo '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">';
        echo '  <h2>權限不足 (403 Forbidden)</h2>';
        echo '  <p>您無權存取此頁面。如有疑問，請聯絡系統管理員。</p>';
        echo '  <p><a href="/index.php">回首頁</a></p>';
        echo '</div>';
        exit;
    }
}

/**
 * 簡易 Rate Limit (API 限制)
 */
function check_rate_limit($key, $max_requests = 10, $period = 60) {
    $now = time();
    $session_key = "rate_limit_" . $key;
    if (!isset($_SESSION[$session_key])) {
        $_SESSION[$session_key] = [];
    }
    
    // 過濾掉超過時效的紀錄
    $_SESSION[$session_key] = array_filter($_SESSION[$session_key], function($timestamp) use ($now, $period) {
        return $timestamp > ($now - $period);
    });
    
    if (count($_SESSION[$session_key]) >= $max_requests) {
        return false; // 超出限制
    }
    
    $_SESSION[$session_key][] = $now;
    return true;
}

/**
 * 稽核日誌寫入
 */
function write_audit_log($pdo, $action) {
    $user_id = $_SESSION['user']['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $ip, $ua]);
    } catch (PDOException $e) {
        error_log("Failed to write audit log: " . $e->getMessage());
    }
}

/**
 * 根據使用者角色獲取權限 (CWE-484 安全修補：補上 break 語句防止越權)
 */
function get_user_permissions($role) {
    $permissions = [];
    switch ($role) {
        case 'student':
            $permissions[] = 'view_courses';
            break; // 安全修補
        case 'teacher':
            $permissions[] = 'view_registrations';
            break; // 安全修補
        case 'admin':
            $permissions[] = 'admin_access';
            break;
        default:
            $permissions[] = 'guest_access';
    }
    return $permissions;
}
