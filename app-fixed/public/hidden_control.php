<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$message = '';
$message_class = '';
$current_role = $_SESSION['user']['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if ($action === 'elevate_privilege') {
        // 安全修補防護 (CWE-602)：後端進行嚴格的伺服器端身分驗證，絕不信任客戶端控制
        if ($current_role !== 'admin') {
            $message = "❌ 存取拒絕：伺服器端檢測到權限越權請求！您的帳號角色並非管理員，拒絕執行此操作。";
            $message_class = 'alert-danger';
        } else {
            $message = "✅ 執行成功：管理員已成功執行敏感提權操作。";
            $message_class = 'alert-success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🎯 客戶端安全控制缺失修補 (CWE-602) - VulnCampus</title>
    <!-- 使用 Bootstrap 5 與修正版風格對齊 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f8fafc; color: #0f172a; }
        .instructions { background-color: #e8f5e9; border-left: 5px solid #2e7d32; }
        .admin-box { background-color: #fef3c7; border: 2px dashed #f59e0b; padding: 20px; border-radius: 8px; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🎯 客戶端安全控制缺失安全修補 (CWE-602)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $message_class ?> font-weight-bold py-3 mb-4"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與安全防禦說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    ⚙️ 課程與學員個人控制台 (已安全防禦)
                </div>
                <div class="card-body p-4 bg-white">
                    <h5>目前登入帳號：<strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong></h5>
                    <p>您的當前角色：<span class="badge bg-primary"><?= htmlspecialchars($current_role) ?></span></p>
                    
                    <div class="border-top pt-3 mt-3">
                        <h6>📖 已報名課程列表：</h6>
                        <ul class="text-muted small">
                            <li>OWASP ZAP 安全檢測基礎實務</li>
                            <li>Web 安全漏洞防禦 Secure Coding (PHP)</li>
                        </ul>
                    </div>

                    <!-- 安全修補 (CWE-602)：如果不是 admin 角色，前端徹底不輸出該 HTML 元件，防止 HTML 洩漏 -->
                    <?php if ($current_role === 'admin'): ?>
                        <div class="admin-box mt-4" id="hidden-admin-panel">
                            <h6 class="text-warning font-weight-bold">⚠️ 管理功能區 (僅限 admin 顯示)</h6>
                            <form method="POST">
                                <input type="hidden" name="action" value="elevate_privilege">
                                <button type="submit" class="btn btn-warning w-100 font-weight-bold text-dark">⚡ 一鍵執行管理員功能 ⚡</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-secondary small mt-4 border-0 bg-light text-muted">
                            ℹ️ 提示：非管理員角色，前端不渲染管理功能按鈕，且後端設有防禦。
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全修補對照說明</h5>
                <p class="text-muted small">
                    <strong>如何防範客戶端安全控制繞過 (CWE-602)？</strong>
                    <br><br>
                    <strong>1. 伺服器端身分驗證 (Server-side Enforcement)</strong>：
                    絕不信任任何來自前端的請求。當後端控制器收到任何進階或敏感操作的 POST/GET 請求時，必須在後端代碼中執行權限檢查（如比對 Session 中的角色）。
                    <br><br>
                    <strong>2. 徹底不渲染 (Conditional Rendering)</strong>：
                    若使用者沒有特定權限，前端應該使用伺服器端語言 (PHP 邏輯判定) 徹底不渲染該按鈕的 HTML 原始碼。不應該僅用 CSS (如 <code>display: none;</code>) 隱藏，否則攻擊者透過 F12 檢視原始碼就能輕鬆找出並繞過。
                </p>
            </div>
        </div>

        <!-- 右側：代碼修補對照 -->
        <div class="col-md-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全權限檢查邏輯 (代碼修補對比)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (直接拼接/信任請求)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>if ($action === 'elevate_privilege') {
    $_SESSION['user']['role'] = 'admin'; // ❌ 伺服器端完全無驗證身分
}</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (伺服器端強制校驗 Session 角色)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>if ($action === 'elevate_privilege') {
    // 🛡️ 伺服器端安全二次校驗
    if ($_SESSION['user']['role'] !== 'admin') {
        $message = "❌ 存取拒絕！";
    } else {
        // 執行功能
    }
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
