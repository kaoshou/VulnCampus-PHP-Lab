<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$error = '';
$success = '';
$step = 1; // 1: 輸入帳號, 2: 輸入新密碼
$generated_link = '';
$username = $_GET['username'] ?? '';
$token = $_GET['token'] ?? '';

// 如果網址上有 token 與 username，進入步驟 2
if ($username !== '' && $token !== '') {
    $step = 2;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request_reset') {
        // 步驟 1：申請重設密碼
        $user_input_name = $_POST['username'] ?? '';
        
        try {
            $stmt = $pdo->query("SELECT * FROM users WHERE username = '$user_input_name'");
            $user = $stmt->fetch();
            
            if ($user) {
                // 教學用弱點 1：弱金鑰/弱 Token。Token 僅為簡單的 md5(username)
                $predictable_token = md5($user['username']);
                
                // 模擬寫入資料庫
                $pdo->exec("INSERT INTO password_resets (user_id, token, expires_at) VALUES (" . $user['id'] . ", '$predictable_token', DATE_ADD(NOW(), INTERVAL 1 HOUR))");
                
                $success = '密碼重設連結已產生！';
                // 教學用弱點 2：因為本機無郵件伺服器，直接在前端畫面上暴露出重設連結供學生演練
                $generated_link = "/reset_password.php?token=" . $predictable_token . "&username=" . $user['username'];
            } else {
                $error = '找不到該帳號！';
            }
        } catch (PDOException $e) {
            $error = '資料庫錯誤：' . $e->getMessage();
        }
        
    } elseif ($action === 'reset_password') {
        // 步驟 2：執行重設密碼
        $user_name = $_POST['username'] ?? '';
        $new_pwd = $_POST['new_password'] ?? '';
        $submitted_token = $_POST['token'] ?? '';

        if ($user_name === '' || $new_pwd === '') {
            $error = '欄位不可為空。';
        } else {
            try {
                // 教學用弱點 3：流程與商業邏輯漏洞。
                // 後端在此處雖然接收了 token，但在執行 UPDATE 前，卻「忘記比對」該 token 是否存在於 password_resets 中且有效！
                // 甚至直接忽略了 Token 校驗，直接根據 username 更新密碼！
                // 攻擊者只要發送包含 username 的 POST 請求，就算 token 為空，也能重設他人密碼！
                $md5_pwd = md5($new_pwd);
                $sql = "UPDATE users SET password_hash = '$md5_pwd' WHERE username = '$user_name'";
                $pdo->exec($sql);
                
                $success = '密碼已成功重設，請使用新密碼重新登入！';
                $step = 1;
            } catch (PDOException $e) {
                $error = '密碼重設失敗：' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>重設密碼 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5 col-md-6">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🔑 密碼重設系統 (流程邏輯缺陷)</h2>
        <a href="/login.php" class="btn btn-secondary">回登入頁</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?>
            <?php if ($generated_link): ?>
                <br>
                <strong>測試重設連結：</strong> 
                <a href="<?= $generated_link ?>" class="alert-link">點擊此處前往重設 (<?= $generated_link ?>)</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white font-weight-bold">
            <?= $step === 1 ? '忘記密碼 - 產生重設連結' : '輸入新密碼' ?>
        </div>
        <div class="card-body">
            <?php if ($step === 1): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="request_reset">
                    <div class="form-group">
                        <label for="username">請輸入您的帳號名稱：</label>
                        <input type="text" name="username" id="username" class="form-control" placeholder="如 student01" required>
                    </div>
                    <button type="submit" class="btn btn-primary">送出申請</button>
                </form>
            <?php else: ?>
                <form method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="username" value="<?= sanitize($username) ?>">
                    <!-- 雖然表單帶有 token，但後端未校驗它 -->
                    <input type="hidden" name="token" value="<?= sanitize($token) ?>">
                    
                    <div class="alert alert-info">
                        正在重設帳號 <strong><?= sanitize($username) ?></strong> 的密碼
                    </div>
                    <div class="form-group">
                        <label for="new_password">請輸入您的新密碼：</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="請輸入新密碼">
                    </div>
                    <button type="submit" class="btn btn-danger">確認更新密碼</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-warning mt-4">
        💡 <strong>教學演練指引 (邏輯繞過)：</strong><br>
        1. 當您處於「輸入新密碼」步驟時，開啟 ZAP Breakpoint 或利用瀏覽器開發者工具檢視表單參數。<br>
        2. 嘗試將 hidden 欄位 <code>username</code> 的值改為 <code>admin</code>，而 <code>token</code> 保持不變（或留空）。<br>
        3. 送出表單，後端會直接將 <code>admin</code> 的密碼修改為您剛輸入的新密碼！這證明了後端完全沒有比對 Token 是否與該用戶綁定。
    </div>
</div>

</body>
</html>
