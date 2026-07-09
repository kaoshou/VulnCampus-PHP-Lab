<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // 教學用弱點 1：登入邏輯使用字串拼接，且密碼直接以不安全的明文比對，造成 SQL 注入與明文密碼漏洞
    // 您可以輸入 admin' -- 繞過密碼驗證
    
    try {
        // 教學用弱點 2：直接拼接 SQL 語句 (同時拼接帳號與密碼，這是最經典的登入 SQLi 繞過情境)
        // 這容許學員在 Username 中輸入 admin' -- 或是 ' or 1=1 -- 直接繞過登入
        $sql = "SELECT * FROM users WHERE username = '$username' AND password_hash = '$password'";
        $stmt = $pdo->query($sql);
        $user = $stmt->fetch();
 
        if ($user) {
            // 登入成功
            // 教學用弱點 4：登入成功後未呼叫 session_regenerate_id()，導致 Session Fixation 漏洞
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'name' => $user['name']
            ];
            
            // 記錄登入成功日誌 (CWE-532：寫入明文密碼等敏感資訊)
            write_audit_log($pdo, "登入成功 (Username: $username, Password: $password)");
 
            // 跳轉 (结合 redirect.php 的 Open Redirect，若有 url 參數直接轉向)
            $goto = !empty($_GET['redirect']) ? $_GET['redirect'] : '/index.php';
            header("Location: " . $goto);
            exit;
        } else {
            // 教學用弱點 3：詳細的登入錯誤訊息 (外洩帳號是否存在資訊)
            // 為了向學員展示資訊洩漏，我們在登入失敗時單獨檢索帳號是否存在
            $check_sql = "SELECT * FROM users WHERE username = '$username'";
            $check_stmt = $pdo->query($check_sql);
            $check_user = $check_stmt->fetch();
            if ($check_user) {
                $error = '密碼輸入錯誤。'; // 洩漏密碼錯誤資訊
            } else {
                $error = '此帳號不存在。'; // 洩漏帳號不存在資訊
            }
        }
    } catch (PDOException $e) {
        // 教學用弱點 5：錯誤處理不當，直接將 SQL 報錯輸出在前端網頁上
        $error = '資料庫查詢出錯：' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>帳號登入 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f5f5f5; padding-top: 80px; }
        .form-signin { width: 100%; max-width: 400px; padding: 15px; margin: auto; }
    </style>
</head>
<body>

<div class="container">
    <main class="form-signin bg-white border rounded shadow-sm p-4">
        <h2 class="h3 mb-3 font-weight-normal text-center">VulnCampus 登入</h2>
        <p class="text-muted text-center text-small">請輸入您的測試帳密</p>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">帳號 (Username)</label>
                <input type="text" name="username" id="username" class="form-group form-control" required placeholder="如 student01" value="<?= isset($_POST['username']) ? sanitize($_POST['username']) : '' ?>">
            </div>
            <div class="form-group">
                <label for="password">密碼 (Password)</label>
                <input type="password" name="password" id="password" class="form-group form-control" required placeholder="如 password123">
            </div>
            <button class="btn btn-lg btn-primary btn-block" type="submit">登入</button>
        </form>

        <div class="mt-3 text-center">
            <a href="/index.php">回首頁</a> | 
            <a href="/reset_password.php">忘記密碼？</a>
        </div>
    </main>
</div>

</body>
</html>
