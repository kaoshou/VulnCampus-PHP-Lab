<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$error = '';
$success = '';

// 處理新增留言 (弱點版：無 CSRF 防禦，可被 CSRF 攻擊新增惡意留言)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_display = $_POST['username_display'] ?? '匿名';
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    
    // 已登入就關聯 user_id，未登入為 0
    $user_id = isset($_SESSION['user']) ? $_SESSION['user']['id'] : 0;

    if ($title === '' || $content === '') {
        $error = '標題與內容為必填欄位。';
    } else {
        try {
            // 教學用弱點 1：SQL 注入。此處直接在 INSERT 語句中使用拼接
            // 雖然大多時候是 XSS 演示，但拼接依然是 SQLi 隱患
            $sql = "INSERT INTO messages (user_id, username_display, title, content) VALUES ($user_id, '$username_display', '$title', '$content')";
            $pdo->exec($sql);
            $success = '留言新增成功！';
        } catch (PDOException $e) {
            $error = '資料庫錯誤：' . $e->getMessage();
        }
    }
}

// 撈取所有留言
try {
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY id DESC");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $messages = [];
    $error = '撈取留言失敗：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>校園留言板 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>💬 校園留言板</h2>
        <div>
            <?php if (isset($_SESSION['user'])): ?>
                <span class="mr-2">目前登入：<?= $_SESSION['user']['username'] ?></span>
            <?php endif; ?>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- 新增留言區 -->
    <div class="card mb-4">
        <div class="card-header font-weight-bold">撰寫新留言 (未設定 CSRF 防護)</div>
        <div class="card-body">
            <form method="POST" action="messages.php">
                <!-- 教學用弱點 2：可隨意冒用他人暱稱進行留言 -->
                <div class="form-group">
                    <label for="username_display">留言暱稱：</label>
                    <input type="text" name="username_display" id="username_display" class="form-control col-md-4" 
                           value="<?= isset($_SESSION['user']) ? $_SESSION['user']['name'] : '訪客' ?>">
                </div>
                <div class="form-group">
                    <label for="title">留言主題：</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="請輸入標題...">
                </div>
                <div class="form-group">
                    <label for="content">留言內容：</label>
                    <textarea name="content" id="content" rows="4" class="form-control" placeholder="支援 HTML (可用於測試 Stored XSS... 例如 <script>alert('XSS')</script>)"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">送出留言</button>
            </form>
        </div>
    </div>

    <!-- 顯示留言區 -->
    <div class="card">
        <div class="card-header font-weight-bold">留言列表</div>
        <div class="card-body">
            <?php if (empty($messages)): ?>
                <p class="text-muted">目前尚無留言。</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="border rounded p-3 mb-3 bg-white">
                        <div class="d-flex justify-content-between text-muted text-small mb-2">
                            <span>
                                👤 <strong><?= $msg['username_display'] ?></strong> 
                                (帳號關聯 ID: <?= $msg['user_id'] ?>)
                            </span>
                            <span>🕒 <?= $msg['created_at'] ?></span>
                        </div>
                        <!-- 教學用弱點 3：儲存型 XSS (Stored XSS)。這裡直接將 content 輸出，並未進行 htmlspecialchars 編碼 -->
                        <h5 class="mt-2"><?= $msg['title'] ?></h5>
                        <div class="mt-2 text-dark" style="white-space: pre-wrap;"><?= $msg['content'] ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
