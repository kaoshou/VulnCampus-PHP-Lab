<?php
require_once __DIR__ . '/../src/helpers.php';
// 本頁面為模擬 Remember Me 登入流程，因此不需要 check_login() 阻擋未登入，改為自建登入模擬

$message = '';
$message_class = '';
$logged_in_user = null;
$logged_in_role = null;

// 1. 處理使用者點擊「清除 Cookie 登出」
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    setcookie('remember_user', '', time() - 3600, '/');
    setcookie('remember_role', '', time() - 3600, '/');
    setcookie('remember_pwd', '', time() - 3600, '/');
    header("Location: cookie_sensitive.php");
    exit;
}

// 2. 處理模擬登入表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_sim'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 簡單的帳密檢查模擬
    $valid_users = [
        'student01' => ['password' => 'password123', 'role' => 'student', 'name' => '陳小明', 'secret' => '學生檔案夾密碼：student_pass_999'],
        'admin' => ['password' => 'admin', 'role' => 'admin', 'name' => '系統管理員', 'secret' => '伺服器 SSH 密鑰路徑：/root/.ssh/id_rsa']
    ];

    if (isset($valid_users[$username]) && $valid_users[$username]['password'] === $password) {
        $user_info = $valid_users[$username];
        
        // 漏洞點 (CWE-315)：以明文形式在 Cookie 中儲存敏感的帳號、密碼與角色資訊
        setcookie('remember_user', $username, time() + 3600, '/');
        setcookie('remember_role', $user_info['role'], time() + 3600, '/');
        setcookie('remember_pwd', $password, time() + 3600, '/'); // 致命錯誤：儲存明文密碼
        
        header("Location: cookie_sensitive.php?success=1");
        exit;
    } else {
        $message = "❌ 帳號或密碼錯誤！";
        $message_class = 'alert-danger';
    }
}

// 3. 檢查 Cookie 以進行「自動登入」
$cookie_user = $_COOKIE['remember_user'] ?? '';
$cookie_role = $_COOKIE['remember_role'] ?? '';
$cookie_pwd = $_COOKIE['remember_pwd'] ?? '';

if ($cookie_user) {
    // 脆弱邏輯：後端直接信任來自 Cookie 的資料進行會話重塑
    $logged_in_user = $cookie_user;
    $logged_in_role = $cookie_role;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔑 明文 Cookie 敏感資訊 (CWE-315) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🔑 Cookie 明文敏感資訊儲存 (CWE-315) 演練</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $message_class ?> font-weight-bold py-3"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success font-weight-bold py-3">✅ 模擬登入成功，並已將記住我憑證存入 Cookie 中！</div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：功能模擬與指引 -->
        <div class="col-md-5">
            <!-- 情況 A: 未登入狀態 -->
            <?php if (!$logged_in_user): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white font-weight-bold">
                        🔐 快速自動登入模擬表單
                    </div>
                    <div class="card-body">
                        <p class="text-muted">請使用測試帳密登入，系統將會為您開啟「記住我」快速登入 Cookie。</p>
                        
                        <form method="POST">
                            <input type="hidden" name="login_sim" value="1">
                            <div class="form-group">
                                <label for="username">模擬帳號：</label>
                                <select name="username" id="username" class="form-control" required>
                                    <option value="student01">student01 (學生)</option>
                                    <option value="admin">admin (管理員)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="password">模擬密碼：</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="請輸入對應密碼" required>
                                <small class="text-muted">提示：student01 密碼為 <code>password123</code>，admin 密碼為 <code>admin</code></small>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="remember" checked disabled>
                                <label class="form-check-label text-danger font-weight-bold" for="remember">啟用「記住我」自動登入 (寫入明文 Cookie)</label>
                            </div>
                            <button type="submit" class="btn btn-danger btn-block font-weight-bold">登入並寫入 Cookie</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- 情況 B: 已自動登入狀態 -->
                <div class="card shadow-sm mb-4 border-danger">
                    <div class="card-header bg-danger text-white font-weight-bold">
                        🔓 自動登入成功！
                    </div>
                    <div class="card-body">
                        <h5>歡迎回來，<strong><?= htmlspecialchars($logged_in_user) ?></strong></h5>
                        <p>您的當前權限角色：<span class="badge badge-warning"><?= htmlspecialchars($logged_in_role) ?></span></p>
                        
                        <div class="alert alert-info small mt-3">
                            <strong>🔒 帳號保密檔案：</strong><br>
                            <?php if ($logged_in_role === 'admin'): ?>
                                <span class="text-danger font-weight-bold">🛡️ 管理員機密：伺服器 SSH 密鑰路徑為 /root/.ssh/id_rsa</span>
                            <?php else: ?>
                                <span>學生成績檔案夾密碼為 student_pass_999</span>
                            <?php endif; ?>
                        </div>

                        <a href="?action=logout" class="btn btn-outline-danger btn-block font-weight-bold mt-4">清除 Cookie 並登出</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 CWE-315 漏洞演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：後端程式在實現「記住我」或身分重組機制時，直接將<strong>敏感的帳號、密碼或權限角色以明文方式</strong>儲存於瀏覽器 Cookie 中，這極易被攻擊者竊取或竄改。</li>
                    <li class="mb-2"><strong>漏洞測試 1 (明文洩漏)</strong>：<br>
                        使用 <code>student01</code> 帳號登入後，按下 <strong>F12 ➔ Application ➔ Cookies</strong>，檢查當前的 Cookie。您會發現 <code>remember_user</code>、<code>remember_role</code> 與 <code>remember_pwd</code> (密碼) **皆以赤裸的明文形式暴露在瀏覽器中**！
                    </li>
                    <li class="mb-2"><strong>漏洞測試 2 (Cookie 竄改越權)</strong>：<br>
                        - 目前是以 <code>student01</code> 登入。<br>
                        - 請在 F12 的 Cookie 管理器中，雙擊將 <code>remember_user</code> 改為 <code>admin</code>，並將 <code>remember_role</code> 改為 <code>admin</code>。<br>
                        - 重新整理網頁，觀察您是否成功<strong>越權登入為 admin</strong>，並看到了管理員的 SSH 機密！
                    </li>
                </ol>
            </div>
        </div>

        <!-- 右側：Cookie 即時觀測器 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white font-weight-bold">
                    🔍 瀏覽器 Cookie 即時觀測器 (後端視角)
                </div>
                <div class="card-body bg-white">
                    <p class="text-muted">以下顯示後端從瀏覽器 HTTP Request 中收到的 Cookie 資料：</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Cookie 名稱</th>
                                    <th>傳遞數值 (Value)</th>
                                    <th>風險評估</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($_COOKIE)): ?>
                                    <tr><td colspan="3" class="text-center text-muted">目前無任何 Cookie 數據傳遞。</td></tr>
                                <?php else: ?>
                                    <?php foreach ($_COOKIE as $key => $val): ?>
                                        <?php 
                                        $is_sensitive = in_array($key, ['remember_user', 'remember_role', 'remember_pwd']);
                                        ?>
                                        <tr class="<?= $is_sensitive ? 'table-warning' : '' ?>">
                                            <td><code><?= htmlspecialchars($key) ?></code></td>
                                            <td class="font-weight-bold <?= $key === 'remember_pwd' ? 'text-danger' : '' ?>">
                                                <?= htmlspecialchars($val) ?>
                                            </td>
                                            <td>
                                                <?php if ($key === 'remember_pwd'): ?>
                                                    <span class="badge badge-danger">💥 高風險：明文密碼洩漏</span>
                                                <?php elseif ($is_sensitive): ?>
                                                    <span class="badge badge-warning">⚠️ 中風險：明文個資與角色，易遭竄改越權</span>
                                                <?php else: ?>
                                                    <span class="text-muted">普通會話資料</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
