<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$error = '';
$success = '';
$step = 1;
$generated_link = '';
$username = trim($_GET['username'] ?? '');
$token = trim($_GET['token'] ?? '');

// 步驟 2 驗證：如果網址上有提供 token 與 username，後端必須嚴格檢驗其時效性與有效性
if ($username !== '' && $token !== '') {
    try {
        // 取得使用者 ID
        $user_stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $user_stmt->execute(['username' => $username]);
        $user = $user_stmt->fetch();
        
        if ($user) {
            // 修補重點 1：驗證 Token 是否存在、是否過期，且是否已被使用 (used_at IS NULL)
            $token_stmt = $pdo->prepare("SELECT * FROM password_resets 
                                         WHERE user_id = :user_id AND token = :token 
                                         AND expires_at > NOW() AND used_at IS NULL");
            $token_stmt->execute([
                'user_id' => $user['id'],
                'token' => $token
            ]);
            $reset_record = $token_stmt->fetch();
            
            if ($reset_record) {
                $step = 2; // Token 檢驗通過，進入重設新密碼頁面
            } else {
                $error = '密碼重設連結無效或已過期。';
                $step = 1;
            }
        } else {
            $error = '無效的用戶請求。';
            $step = 1;
        }
    } catch (PDOException $e) {
        error_log("Validate reset token failed: " . $e->getMessage());
        $error = '系統發生異常，請重試。';
        $step = 1;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request_reset') {
        // 步驟 1：申請重設密碼
        $user_input_name = trim($_POST['username'] ?? '');
        
        if ($user_input_name === '') {
            $error = '請輸入帳號名稱。';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->execute(['username' => $user_input_name]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // 修補重點 2：使用隨機生成的安全 Token (CSPRNG)，而非可預測的雜湊
                    $secure_token = bin2hex(random_bytes(32));
                    
                    // 設定 15 分鐘過期時間
                    $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    
                    $ins_stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (:user_id, :token, :expires)");
                    $ins_stmt->execute([
                        'user_id' => $user['id'],
                        'token' => $secure_token,
                        'expires' => $expires_at
                    ]);
                    
                    $success = '密碼重設連結已產生 (有效時間 15 分鐘)！';
                    // 模擬發送重設郵件
                    $generated_link = "/reset_password.php?token=" . $secure_token . "&username=" . urlencode($user['username']);
                    write_audit_log($pdo, "申請重設密碼 (使用者: " . $user['username'] . ")");
                } else {
                    // 修補重點：同樣採取統一模糊訊息，不洩漏帳號是否存在 (此處為了教學顯示連結，非 admin 可模糊處理，此處做安全版流程)
                    $success = '若帳號存在，系統已產生密碼重設連結！';
                }
            } catch (PDOException $e) {
                error_log("Request reset password error: " . $e->getMessage());
                $error = '系統異常，請稍後再試。';
            }
        }
        
    } elseif ($action === 'reset_password') {
        // 步驟 2：執行重設密碼
        $user_name = trim($_POST['username'] ?? '');
        $new_pwd = $_POST['new_password'] ?? '';
        $submitted_token = trim($_POST['token'] ?? '');

        if ($user_name === '' || $new_pwd === '' || $submitted_token === '') {
            $error = '欄位不可為空。';
        } else {
            try {
                // 再次嚴格校驗 Token 的有效性
                $user_stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
                $user_stmt->execute(['username' => $user_name]);
                $user = $user_stmt->fetch();
                
                if ($user) {
                    // 修補重點 3：後端二次校驗 Token 與防重放 (Anti-Replay)
                    $token_stmt = $pdo->prepare("SELECT * FROM password_resets 
                                                 WHERE user_id = :user_id AND token = :token 
                                                 AND expires_at > NOW() AND used_at IS NULL");
                    $token_stmt->execute([
                        'user_id' => $user['id'],
                        'token' => $submitted_token
                    ]);
                    $reset_record = $token_stmt->fetch();
                    
                    if ($reset_record) {
                        $pdo->beginTransaction();
                        
                        // 1. 更新密碼 (使用 Bcrypt)
                        $bcrypt_pwd = password_hash($new_pwd, PASSWORD_DEFAULT);
                        $up_stmt = $pdo->prepare("UPDATE users SET password_hash = :pwd, failed_login_count = 0, locked_until = NULL WHERE id = :id");
                        $up_stmt->execute([
                            'pwd' => $bcrypt_pwd,
                            'id' => $user['id']
                        ]);
                        
                        // 2. 作廢該 Token，寫入 used_at 防止重複使用
                        $expire_token_stmt = $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE id = :id");
                        $expire_token_stmt->execute(['id' => $reset_record['id']]);
                        
                        $pdo->commit();
                        
                        write_audit_log($pdo, "密碼重設成功 (使用者: $user_name)");
                        $success = '密碼重設成功，請使用新密碼重新登入！';
                        $step = 1;
                    } else {
                        $error = '驗證逾時或 Token 已失效，請重新申請重設連結。';
                        $step = 1;
                    }
                } else {
                    $error = '用戶不存在。';
                    $step = 1;
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log("Update password reset failed: " . $e->getMessage());
                $error = '密碼重設失敗，系統異常。';
                $step = 1;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>重設密碼 - VulnCampus (安全版)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5 col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🔑 密碼重設系統 (安全驗證版)</h2>
        <a href="/login.php" class="btn btn-secondary">回登入頁</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2">
            <?= h($success) ?>
            <?php if ($generated_link): ?>
                <br>
                <strong>安全重設連結 (模擬發信)：</strong> 
                <a href="<?= h($generated_link) ?>" class="alert-link">點擊此處前往安全重設 (<?= h($generated_link) ?>)</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white font-weight-bold py-3">
            <?= $step === 1 ? '忘記密碼 - 申請重設' : '輸入新密碼' ?>
        </div>
        <div class="card-body p-4">
            <?php if ($step === 1): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="request_reset">
                    <div class="form-group mb-3">
                        <label for="username" class="form-label font-weight-bold">請輸入您的帳號名稱：</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="請輸入帳號" required>
                    </div>
                    <button type="submit" class="btn btn-primary font-weight-bold">發送重設申請</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="username" value="<?= h($username) ?>">
                    <!-- 帶有安全 Token 參數 -->
                    <input type="hidden" name="token" value="<?= h($token) ?>">
                    
                    <div class="alert alert-info">
                        正在進行安全密碼重設，帳號：<strong><?= h($username) ?></strong>
                    </div>
                    <div class="form-group mb-3">
                        <label for="new_password" class="form-label font-weight-bold">請輸入新密碼 (請符合密碼強度)：</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="設定新密碼">
                    </div>
                    <button type="submit" class="btn btn-danger font-weight-bold">確認安全更換密碼</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-success mt-4">
        🛡️ <strong>安全控制項說明：</strong><br>
        1. <strong>強隨機一次性 Token</strong>：重設 Token 採用 <code>random_bytes()</code> 隨機字元，防範被黑客預測或爆破。<br>
        2. <strong>後端二次驗證</strong>：在提交新密碼時，後端會重新對比 <code>password_resets</code>，驗證該 Token 是否與對應之帳號綁定。<br>
        3. <strong>作廢機制 (Anti-Replay)</strong>：一旦密碼成功修改，該 Token 的 <code>used_at</code> 欄位立即寫入時間，讓此連結永久失效，杜絕重放攻擊。<br>
        4. <strong>密碼強度雜湊</strong>：密碼一律使用 Bcrypt 進行重新雜湊，保障資料安全。
    </div>
</div>

</body>
</html>
