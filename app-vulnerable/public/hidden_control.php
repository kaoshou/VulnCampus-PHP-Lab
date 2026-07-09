<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$message = '';
$message_class = '';
$current_role = $_SESSION['user']['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'elevate_privilege') {
        // 漏洞點 (CWE-602)：後端完全沒有進行伺服器端的權限與身分檢查，直接信任請求並執行敏感動作
        $_SESSION['user']['role'] = 'admin';
        $current_role = 'admin';
        $message = "⚠️ 越權成功！後端未進行任何角色身分校驗，您已成功透過點擊隱藏按鈕，將當前 Session 帳號角色提升為 [admin]！";
        $message_class = 'alert-danger';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🎯 客戶端安全控制缺失 (CWE-602) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
        .admin-box { background-color: #fff3cd; border: 2px dashed #ffc107; padding: 20px; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🎯 客戶端安全控制缺失 (CWE-602) 演練</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $message_class ?> font-weight-bold py-3"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    ⚙️ 課程與學員個人控制台
                </div>
                <div class="card-body">
                    <h5>目前登入帳號：<strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></h5>
                    <p>您的當前角色：<span class="badge badge-primary"><?= htmlspecialchars($current_role) ?></span></p>
                    
                    <div class="border-top pt-3 mt-3">
                        <h6>📖 已報名課程列表：</h6>
                        <ul class="text-muted small">
                            <li>OWASP ZAP 安全檢測基礎實務</li>
                            <li>Web 安全漏洞防禦 Secure Coding (PHP)</li>
                        </ul>
                    </div>

                    <!-- 漏洞點 (CWE-602)：進階的管理按鈕，僅在前端以 CSS style="display: none;" 進行隱藏 -->
                    <div class="admin-box mt-4" style="display: none;" id="hidden-admin-panel">
                        <h6 class="text-warning font-weight-bold">⚠️ 隱藏的管理功能區 (僅限 admin 可見)</h6>
                        <p class="small text-muted">本區塊設定了 <code>display: none;</code> 以對普通學員隱藏。</p>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="elevate_privilege">
                            <button type="submit" class="btn btn-warning btn-block font-weight-bold text-dark">⚡ 一鍵將自己角色提權為管理員 (admin) ⚡</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 CWE-602 漏洞演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：後端程式僅依賴前端 HTML/CSS（如 <code>style="display:none;"</code>）來隱藏敏感功能按鈕，但<strong>在伺服器端收到 POST/GET 請求時，完全沒有對發起者的角色進行身分驗證</strong>。</li>
                    <li class="mb-2"><strong>漏洞驗證 (突破客戶端限制)</strong>：<br>
                        - 普通學生登入後，畫面上看不見任何提權的管理區按鈕。<br>
                        - 請按下 <strong>F12 審查元素</strong>，在 HTML 中尋找帶有 <code>id="hidden-admin-panel"</code> 的 <code>div</code> 標籤。<br>
                        - 將該標籤的 <code>style="display: none;"</code> 改為 <code>display: block;</code>，隱藏的黃色管理區塊與按鈕將會立刻顯現！
                    </li>
                    <li class="mb-2">點選該按鈕送出表單，觀察後端是否直接接受了提權命令，並將您的角色提升為 <code>admin</code>。</li>
                </ol>
            </div>
        </div>

        <!-- 右側：代碼審查 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📝 伺服器端漏洞代碼分析
                </div>
                <div class="card-body bg-light">
                    <p class="text-muted">請審查下列後端程式，找出為什麼即使前端被隱藏，攻擊者仍能完成操作的原因：</p>
                    <pre style="background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px;" class="small"><code>// 後端僅檢查是否有 action 變數，完全沒檢查 $_SESSION 中用戶的角色！
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'elevate_privilege') {
        // ❌ 致命邏輯：直接執行提權動作
        $_SESSION['user']['role'] = 'admin'; 
    }
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
