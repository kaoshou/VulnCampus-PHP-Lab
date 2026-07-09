<?php
require_once __DIR__ . '/../src/helpers.php';
// 模擬安全版的 Remember Me

$message = '';
$message_class = '';
$logged_in_user = null;
$logged_in_role = null;

// 模擬資料庫
$valid_users = [
    'student01' => ['role' => 'student', 'name' => '陳小明', 'secret' => '學生檔案夾密碼：student_pass_999'],
    'admin' => ['role' => 'admin', 'name' => '系統管理員', 'secret' => '伺服器 SSH 密鑰路徑：/root/.ssh/id_rsa']
];

// 1. 處理清除 Cookie 登出
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    // 徹底刪除記住我 Cookie
    setcookie('remember_token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    // 刪除後端關聯 Session 記錄
    if (isset($_SESSION['remember_token'])) {
        unset($_SESSION['remember_token']);
    }
    header("Location: cookie_sensitive.php");
    exit;
}

// 2. 處理模擬登入表單提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_sim'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // 檢查模擬帳密
    if (($username === 'student01' && $password === 'password123') || ($username === 'admin' && $password === 'admin')) {
        // 生成高強度隨機 Remember Token，取代明文敏感資訊 (CWE-315)
        $token = bin2hex(random_bytes(32));
        
        // 後端記錄該 Token 與用戶身分的對照關係 (此處寫入安全 Session 以作演示，實務上常存於資料庫)
        $_SESSION['remember_token'] = [
            'token' => $token,
            'username' => $username,
            'role' => $username === 'admin' ? 'admin' : 'student'
        ];

        // 寫入 Cookie 時開啟安全屬性：HttpOnly, SameSite
        setcookie('remember_token', $token, [
            'expires' => time() + 3600,
            'path' => '/',
            'httponly' => true,  // 阻止 JavaScript 存取防範 XSS 竊取
            'samesite' => 'Lax'  // 防範 CSRF 攻擊
        ]);
        
        header("Location: cookie_sensitive.php?success=1");
        exit;
    } else {
        $message = "❌ 帳號或密碼錯誤！";
        $message_class = 'alert-danger';
    }
}

// 3. 檢查安全 Token 以進行「自動登入」
$cookie_token = $_COOKIE['remember_token'] ?? '';
if ($cookie_token && isset($_SESSION['remember_token']) && $_SESSION['remember_token']['token'] === $cookie_token) {
    // 安全邏輯：僅信任後端對照表中的身分，不再依賴前端 Cookie 傳入的角色與帳號
    $logged_in_user = $_SESSION['remember_token']['username'];
    $logged_in_role = $_SESSION['remember_token']['role'];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔑 明文 Cookie 敏感資訊修補 (CWE-315) - VulnCampus</title>
    <!-- 使用 Bootstrap 5 與修正版風格對齊 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f8fafc; color: #0f172a; }
        .instructions { background-color: #e8f5e9; border-left: 5px solid #2e7d32; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🔑 Cookie 明文敏感資訊儲存修補 (CWE-315)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $message_class ?> font-weight-bold py-3"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success font-weight-bold py-3">✅ 模擬登入成功，並已寫入加密隨機 Token 於 Cookie 中！</div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：功能模擬與防禦說明 -->
        <div class="col-md-5">
            <!-- 情況 A: 未登入狀態 -->
            <?php if (!$logged_in_user): ?>
                <div class="card shadow-sm mb-4 border-0">
                    <div class="card-header bg-success text-white font-weight-bold py-3">
                        🔐 快速自動登入模擬表單 (已修補)
                    </div>
                    <div class="card-body p-4 bg-white">
                        <p class="text-muted">本安全版使用隨機 Token 來代替明文儲存敏感資訊，防止 Cookie 遭人窺探與竄改。</p>
                        
                        <form method="POST">
                            <input type="hidden" name="login_sim" value="1">
                            <div class="mb-3">
                                <label for="username" class="form-label font-weight-bold">模擬帳號：</label>
                                <select name="username" id="username" class="form-control" required>
                                    <option value="student01">student01 (學生)</option>
                                    <option value="admin">admin (管理員)</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label font-weight-bold">模擬密碼：</label>
                                <input type="password" name="password" id="password" class="form-control" placeholder="請輸入對應密碼" required>
                                <small class="text-muted">提示：student01 密碼為 <code>password123</code>，admin 密碼為 <code>admin</code></small>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="remember" checked disabled>
                                <label class="form-check-label text-success font-weight-bold" for="remember">啟用「記住我」自動登入 (寫入高強度 Token)</label>
                            </div>
                            <button type="submit" class="btn btn-success text-white w-100 font-weight-bold">登入並安全寫入 Cookie</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <!-- 情況 B: 已自動登入狀態 -->
                <div class="card shadow-sm mb-4 border-success border-0">
                    <div class="card-header bg-success text-white font-weight-bold py-3">
                        🔓 安全自動登入成功！
                    </div>
                    <div class="card-body p-4 bg-white">
                        <h5>歡迎回來，<strong><?= htmlspecialchars($logged_in_user) ?></strong></h5>
                        <p>您的當前權限角色：<span class="badge bg-success"><?= htmlspecialchars($logged_in_role) ?></span></p>
                        
                        <div class="alert alert-info small mt-3">
                            <strong>🔒 帳號保密檔案：</strong><br>
                            <?php if ($logged_in_role === 'admin'): ?>
                                <span class="text-danger font-weight-bold">🛡️ 管理員機密：伺服器 SSH 密鑰路徑為 /root/.ssh/id_rsa</span>
                            <?php else: ?>
                                <span>學生成績檔案夾密碼為 student_pass_999</span>
                            <?php endif; ?>
                        </div>

                        <a href="?action=logout" class="btn btn-outline-success w-100 font-weight-bold mt-4">清除 Cookie 並登出</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全修補對照說明</h5>
                <p class="text-muted small">
                    <strong>如何徹底防範 CWE-315 敏感 Cookie 洩漏？</strong>
                    <br><br>
                    <strong>1. 絕不在 Cookie 中儲存任何明文敏感資料</strong>：
                    不要把使用者的帳號名稱、權限角色以及明文密碼直接寫入 Cookie，否則攻擊者透過 F12 或是 XSS 就能輕鬆將其竊取。
                    <br><br>
                    <strong>2. 改用無法預測的隨機安全 Token (代標)</strong>：
                    後端只在 Cookie 中儲存一個隨機生成的 Token，並在伺服器端資料庫或 Session 中記下此 Token 的對應身分。
                    <br><br>
                    <strong>3. 啟用防禦屬性</strong>：
                    為 Cookie 配置 <code>httponly = true</code>，使 JavaScript 的 <code>document.cookie</code> 無法讀取該 Cookie，防止被 XSS 直接竊走。
                </p>
            </div>
        </div>

        <!-- 右側：Cookie 觀測與代碼修補對照 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    🔍 瀏覽器 Cookie 即時觀測器 (後端視角)
                </div>
                <div class="card-body bg-white p-4">
                    <p class="text-muted">此時瀏覽器發送的 Cookie 僅剩下無害的隨機隨機 Token 碼，攻擊者無法直接窺探個資與密碼：</p>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>Cookie 名稱</th>
                                    <th>傳遞數值 (Value)</th>
                                    <th>風險評估</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($_COOKIE) || !isset($_COOKIE['remember_token'])): ?>
                                    <tr><td colspan="3" class="text-center text-muted">目前無任何記住我 Token Cookie。</td></tr>
                                <?php else: ?>
                                    <?php foreach ($_COOKIE as $key => $val): ?>
                                        <?php if ($key === 'remember_token'): ?>
                                            <tr class="table-success">
                                                <td><code><?= htmlspecialchars($key) ?></code></td>
                                                <td class="font-weight-bold text-success" style="word-break: break-all;">
                                                    <?= htmlspecialchars($val) ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success">🛡️ 安全：高隨機防竄改 Token (已設定 HttpOnly)</span>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全編碼對比 (CWE-315 修補)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (直接將密碼與角色存入前端，極易遭竄改與洩漏)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>setcookie('remember_user', $username, time() + 3600, '/');
setcookie('remember_role', $user_info['role'], time() + 3600, '/');
setcookie('remember_pwd', $password, time() + 3600, '/');</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (僅在 Cookie 儲存隨機 Token，並啟用 httponly)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>// 生成隨機代標 Token 並綁定後端身分
$token = bin2hex(random_bytes(32));
$_SESSION['remember_token'] = ['token' => $token, 'username' => $username, 'role' => 'student'];

setcookie('remember_token', $token, [
    'expires' => time() + 3600,
    'path' => '/',
    'httponly' => true, // 阻擋 XSS 讀取
    'samesite' => 'Lax'
]);</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
