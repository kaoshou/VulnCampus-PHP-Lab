<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$error = '';
if (isset($_SESSION['timeout_error'])) {
    $error = $_SESSION['timeout_error'];
    unset($_SESSION['timeout_error']);
}

// 取出舊的驗證碼並清除（防重放攻擊）
$captcha_answer = $_SESSION['captcha_answer'] ?? null;
unset($_SESSION['captcha_answer']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_captcha = trim($_POST['captcha'] ?? '');

    if ($username === '' || $password === '') {
        $error = '請填寫帳號與密碼。';
    } elseif ($captcha_answer === null || (int)$user_captcha !== (int)$captcha_answer) {
        $error = '驗證碼輸入錯誤，請重新輸入！';
    } else {
        try {
            // 修補重點 1：使用 Prepared Statements (參數化查詢) 防止 SQL 注入
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute(['username' => $username]);
            $user = $stmt->fetch();

            if ($user) {
                $now = time();
                
                // 修補重點 2：檢查帳號是否因連續登入失敗而被鎖定
                if ($user['locked_until'] && strtotime($user['locked_until']) > $now) {
                    $error = '此帳號已被鎖定，請於 ' . $user['locked_until'] . ' 之後再試。';
                } else {
                    // 修補重點 3：使用密碼驗證器 password_verify 驗證 Bcrypt 安全雜湊
                    if (password_verify($password, $user['password_hash'])) {
                        
                        // 登入成功：重設登入失敗次數與鎖定時間
                        $reset_stmt = $pdo->prepare("UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = :id");
                        $reset_stmt->execute(['id' => $user['id']]);

                        // 修補重點 4：登入成功後，重新產生 Session ID 以防範 Session Fixation (Session 劫持)
                        session_regenerate_id(true);

                        $_SESSION['user'] = [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $user['role'],
                            'name' => $user['name']
                        ];
                        
                        // 寫入稽核日誌
                        write_audit_log($pdo, "登入成功");

                        // 跳轉 (已在 safe_redirect 內設定白名單跳轉，防止 Open Redirect)
                        $goto = !empty($_GET['redirect']) ? $_GET['redirect'] : '/index.php';
                        safe_redirect($goto);
                        
                    } else {
                        // 密碼錯誤：累加失敗次數
                        $failed_count = $user['failed_login_count'] + 1;
                        $locked_until = null;
                        
                        // 失敗達 5 次，鎖定 15 分鐘
                        if ($failed_count >= 5) {
                            $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                            $error = '登入失敗次數過多，帳號已鎖定 15 分鐘。';
                        } else {
                            // 修補重點 5：統一的模糊錯誤訊息，防止黑客猜測帳號是否存在
                            $error = '帳號或密碼錯誤。';
                        }
                        
                        $update_stmt = $pdo->prepare("UPDATE users SET failed_login_count = :count, locked_until = :locked WHERE id = :id");
                        $update_stmt->execute([
                            'count' => $failed_count,
                            'locked' => $locked_until,
                            'id' => $user['id']
                        ]);
                        
                        write_audit_log($pdo, "登入失敗 (密碼錯誤 - 使用者: $username)");
                    }
                }
            } else {
                // 帳號不存在：直接回應統一的模糊錯誤訊息，以防帳號列舉 (Account Enumeration)
                $error = '帳號或密碼錯誤。';
                write_audit_log($pdo, "登入失敗 (帳號不存在: $username)");
            }
        } catch (PDOException $e) {
            // 修補重點 6：發生例外時，不向用戶端暴露敏感的資料庫報錯
            error_log("Login error: " . $e->getMessage());
            $error = '系統發生異常，請聯絡管理員。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>帳號登入 (安全版) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f4f7f6; padding-top: 80px; }
        .form-signin { width: 100%; max-width: 400px; padding: 15px; margin: auto; }
    </style>
</head>
<body>

<div class="container">
    <main class="form-signin bg-white border rounded shadow p-4">
        <h2 class="h3 mb-3 font-weight-normal text-center text-primary">VulnCampus 安全登入</h2>
        <p class="text-muted text-center text-small">請輸入您的帳號密碼</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2"><?= h($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group mb-3">
                <label for="username" class="form-label font-weight-bold">帳號 (Username)</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="請輸入帳號" value="<?= isset($_POST['username']) ? h($_POST['username']) : '' ?>">
            </div>
            <div class="form-group mb-3">
                <label for="password" class="form-label font-weight-bold">密碼 (Password)</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="請輸入密碼">
            </div>
            <div class="form-group mb-3">
                <label for="captcha" class="form-label font-weight-bold">安全驗證碼 (Anti-Bot)</label>
                <div class="input-group">
                    <img src="/api/captcha.php" alt="CAPTCHA" id="captcha-img" class="border rounded-start" style="cursor: pointer; height: 38px;" title="點擊換一張" onclick="this.src='/api/captcha.php?'+Math.random()">
                    <input type="text" name="captcha" id="captcha" class="form-control" required placeholder="請輸入驗證碼" autocomplete="off">
                </div>
                <small class="text-muted" style="font-size: 0.8rem;">看不清楚？點擊圖片可更換一張。</small>
            </div>
            <button class="btn btn-lg btn-primary w-100 font-weight-bold" type="submit">登入</button>
        </form>

        <div class="mt-3 text-center">
            <a href="/index.php" class="text-decoration-none">回首頁</a> | 
            <a href="/reset_password.php" class="text-decoration-none text-muted">忘記密碼？</a>
        </div>
    </main>
</div>

</body>
</html>
