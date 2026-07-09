<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 權限檢查
check_auth();

$error = '';
$success = '';

// 處理留言送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 引入安全防禦：CSRF 驗證
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = '無效的 CSRF Token，請求被拒絕！';
    } else {
        $name = $_POST['name'] ?? '';
        $content = $_POST['content'] ?? '';

        if (empty($name) || empty($content)) {
            $error = '姓名或留言內容不能為空！';
        } else {
            try {
                // 安全防禦 1：使用 Prepared Statement 參數化寫入，完全消除 SQL 注入
                $stmt = $pdo->prepare("INSERT INTO stored_messages (name, content) VALUES (:name, :content)");
                $stmt->execute([
                    ':name' => $name,
                    ':content' => $content
                ]);
                $success = '留言發表成功！';
            } catch (PDOException $e) {
                error_log("Database error during stored XSS insert: " . $e->getMessage());
                $error = '伺服器目前無法儲存留言。';
            }
        }
    }
}

// 讀取所有留言
try {
    $stmt = $pdo->query("SELECT * FROM stored_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = '無法讀取留言。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>💬 預存型 XSS 安全防禦 - VulnCampus</title>
    <!-- 使用 Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f4f7f6; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">💬 預存型 XSS 安全防禦頁面</h2>
        <div>
            <span class="me-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 留言板表單 -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white font-weight-bold">
                    發表訪客留言
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <!-- 安全防禦 2：CSRF 隱藏 Token -->
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

                        <div class="mb-3">
                            <label for="name" class="form-label font-weight-bold">您的姓名 (Name)：</label>
                            <input type="text" name="name" id="name" class="form-control" required value="<?= h($_SESSION['user']['name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label font-weight-bold">留言內容 (Message)：</label>
                            <textarea name="content" id="content" class="form-control" rows="4" placeholder="歡迎留下您的足跡..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">📢 送出留言</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-success mt-3 bg-white">
                <div class="card-header bg-success text-white font-weight-bold">
                    🛡️ XSS Cookie 竊取防禦對照
                </div>
                <div class="card-body">
                    <p class="small text-muted">本安全版因為在 <code>helpers.php</code> 中對 Session Cookie 配置了 <code>httponly: true</code> 屬性。</p>
                    <p class="small text-muted">即使網頁遭受 XSS 注入攻擊，JavaScript 也完全無法讀取到 Session Cookie，將只會傳送空字串給收集箱。</p>
                    <a href="/cookie_stealer.php" target="_blank" class="btn btn-sm btn-outline-success w-100 font-weight-bold">📋 開啟 Cookie 收集箱 (對照組) ➔</a>
                </div>
            </div>
        </div>

        <!-- 歷史留言列表 -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📜 訪客留言列表 (已防禦 XSS)
                </div>
                <div class="card-body">
                    <?php if (empty($messages)): ?>
                        <p class="text-center text-muted">目前尚無留言</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="border-bottom pb-2 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <!-- 安全防禦 3：使用 h() 或 htmlspecialchars 編碼姓名與內容，防堵儲存型 XSS -->
                                    <span class="font-weight-bold text-primary">👤 <?= h($msg['name']) ?></span>
                                    <small class="text-muted"><?= h($msg['created_at']) ?></small>
                                </div>
                                <p class="mb-0 bg-light p-2 rounded"><?= h($msg['content']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
