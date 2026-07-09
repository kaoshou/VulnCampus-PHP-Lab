<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$message = '';
$message_class = '';
$role = $_POST['role'] ?? '';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role && $action) {
    // 安全修補方案 (用 Match 結構防範 Fall-through)
    $allowed_actions = match ($role) {
        'visitor' => ['view'],
        'student' => ['view', 'register'],
        'admin'   => ['view', 'register', 'delete'],
        default   => [],
    };

    if (in_array($action, $allowed_actions)) {
        $message = "✅ 存取允許：身分 [{$role}] 成功執行了操作 [{$action}]。";
        $message_class = 'alert-success';
    } else {
        $message = "❌ 權限不足：身分 [{$role}] 無法執行操作 [{$action}]。";
        $message_class = 'alert-danger';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔑 Switch Case 缺失修補 (CWE-484) - VulnCampus</title>
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
        <h2 class="text-primary">🔑 Switch 語句缺失與越權漏洞修補 (CWE-484)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?= $message_class ?> font-weight-bold py-3"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與防禦說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    🛡️ 安全權限驗證測試器 (已修補)
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted">本安全版已修復 switch 缺少 break 的邏輯缺陷，學員可在此測試權限是否正常受到限縮。</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="role" class="form-label font-weight-bold">模擬角色：</label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="" disabled selected>-- 請選擇角色 --</option>
                                <option value="visitor" <?= $role === 'visitor' ? 'selected' : '' ?>>訪客 (visitor) - 僅能 view</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>學生 (student) - 能 view, register</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>管理員 (admin) - 能 view, register, delete</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="action" class="form-label font-weight-bold">欲執行操作：</label>
                            <select name="action" id="action" class="form-control" required>
                                <option value="" disabled selected>-- 請選擇操作 --</option>
                                <option value="view" <?= $action === 'view' ? 'selected' : '' ?>>瀏覽頁面 (view)</option>
                                <option value="register" <?= $action === 'register' ? 'selected' : '' ?>>報名活動 (register)</option>
                                <option value="delete" <?= $action === 'delete' ? 'selected' : '' ?>>刪除用戶 (delete)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success text-white w-100 font-weight-bold">執行安全測試</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全修補對照說明</h5>
                <p class="text-muted small">
                    本安全版將原本容易產生滑落錯誤的 <code>switch</code> 語句重構，改為使用 PHP 8+ 的 <strong><code>match</code> 表達式</strong>。
                    <br><br>
                    <strong>為什麼使用 match 更安全？</strong>
                    <br>• <strong>不支援 Fall-through</strong>：在 <code>match</code> 結構中，匹配到的分支會直接回傳，且無法像 switch 那樣滑落執行其他分支的代碼，從本質上阻斷了 CWE-484 的發生機率。
                    <br>• <strong>強型別比對 (Strict Comparison)</strong>：<code>match</code> 內部使用的是嚴格比對 (<code>===</code>)，亦可防範 PHP 弱型別弱點。
                </p>
            </div>
        </div>

        <!-- 右側：代碼審查與對比 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全權限檢查邏輯 (代碼修補對比)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (脆弱代碼 switch-case 漏掉 break)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>case 'student':
    $allowed_actions = ['view', 'register'];
    // ⚠️ 漏掉了 break，導致權限滑落覆寫為管理員</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (安全防禦代碼 match)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>$allowed_actions = match ($role) {
    'visitor' => ['view'],
    'student' => ['view', 'register'], // 自動結束，不支援 Fall-through
    'admin'   => ['view', 'register', 'delete'],
    default   => [],
};</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
