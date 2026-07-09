<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$message = '';
$message_class = '';
$role = $_POST['role'] ?? '';
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role && $action) {
    $allowed_actions = [];
    
    // 漏洞點 (CWE-484)：switch 語句中缺少 break 導致 Fall-through 越權
    switch ($role) {
        case 'visitor':
            $allowed_actions = ['view'];
            break;
        case 'student':
            $allowed_actions = ['view', 'register'];
            // ⚠️ 程式設計師忘記寫 break; 導致直接滑落執行 admin 區塊
        case 'admin':
            $allowed_actions = ['view', 'register', 'delete'];
            break;
        default:
            $allowed_actions = [];
            break;
    }

    if (in_array($action, $allowed_actions)) {
        if ($role === 'student' && $action === 'delete') {
            $message = "⚠️ 越權成功！因為 switch 語句中缺少 break 導致 Fall-through，學生 [student] 成功獲得管理員權限並執行了操作 [{$action}]！";
            $message_class = 'alert-danger';
        } else {
            $message = "✅ 存取允許：身分 [{$role}] 成功執行了操作 [{$action}]。";
            $message_class = 'alert-success';
        }
    } else {
        $message = "❌ 權限不足：身分 [{$role}] 無法執行操作 [{$action}]。";
        $message_class = 'alert-warning';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔑 Switch Case 缺失 (CWE-484) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🔑 Switch 語句缺失與越權漏洞 (CWE-484) 演練</h2>
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
                    🛡️ 模擬權限驗證測試器
                </div>
                <div class="card-body">
                    <p class="text-muted">選擇您想模擬的用戶角色與操作，點擊執行後系統將模擬後端的權限檢查流程。</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="role">模擬角色：</label>
                            <select name="role" id="role" class="form-control" required>
                                <option value="" disabled selected>-- 請選擇角色 --</option>
                                <option value="visitor" <?= $role === 'visitor' ? 'selected' : '' ?>>訪客 (visitor) - 僅能 view</option>
                                <option value="student" <?= $role === 'student' ? 'selected' : '' ?>>學生 (student) - 能 view, register</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>管理員 (admin) - 能 view, register, delete</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="action">欲執行操作：</label>
                            <select name="action" id="action" class="form-control" required>
                                <option value="" disabled selected>-- 請選擇操作 --</option>
                                <option value="view" <?= $action === 'view' ? 'selected' : '' ?>>瀏覽頁面 (view)</option>
                                <option value="register" <?= $action === 'register' ? 'selected' : '' ?>>報名活動 (register)</option>
                                <option value="delete" <?= $action === 'delete' ? 'selected' : '' ?>>刪除用戶 (delete)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block font-weight-bold">執行權限測試</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 CWE-484 漏洞演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：後端程式在使用 <code>switch-case</code> 進行權限分配時，在 <code>student</code> 角色結尾處遺漏了 <code>break;</code> 語句，導致代碼在匹配 student 時無條件滑落（Fall-through）至下方的 <code>admin</code> 區塊，使得學生的權限被覆寫為管理員的權限。</li>
                    <li class="mb-2"><strong>正常測試</strong>：<br>
                        - 選擇角色 <strong>訪客 (visitor)</strong> 執行 <strong>瀏覽頁面 (view)</strong>，觀察是否正常允許。<br>
                        - 選擇角色 <strong>訪客 (visitor)</strong> 執行 <strong>刪除用戶 (delete)</strong>，確認是否被正常拒絕。
                    </li>
                    <li class="mb-2"><strong>漏洞測試 (垂直越權)</strong>：<br>
                        - 選擇角色 <strong>學生 (student)</strong> 執行 <strong>刪除用戶 (delete)</strong>。<br>
                        觀察此時程式是否沒有拒絕，反而印出越權成功的紅色警報，說明學生因為程式碼邏輯缺陷而獲得了管理員權限！
                    </li>
                </ol>
            </div>
        </div>

        <!-- 右側：權限與代碼分析 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📝 後端權限檢查邏輯 (代碼審查)
                </div>
                <div class="card-body bg-light">
                    <p class="text-muted">請審查下列後端程式片段，找出導致越權發生的邏輯盲點：</p>
                    <pre style="background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px;" class="small"><code>switch ($role) {
    case 'visitor':
        $allowed_actions = ['view'];
        break;
    case 'student':
        $allowed_actions = ['view', 'register'];
        // ⚠️ 程式設計師忘記寫 break;
    case 'admin':
        $allowed_actions = ['view', 'register', 'delete'];
        break;
    default:
        $allowed_actions = [];
        break;
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
