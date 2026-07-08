<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 修補重點 1：留言板限制必須登入才能使用，防堵訪客或未授權帳密進行惡意灌水
check_auth();

$error = '';
$success = '';

// 處理新增留言
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 修補重點 2：防禦 CSRF 攻擊，校驗表單所帶入的 CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        $error = '安全防護偵測到無效的 CSRF Token 請求，操作已被拒絕！';
    } else {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        // 修補重點 3：限制暱稱直接綁定當前登入使用者的真實姓名，不允許前端自定義傳值，防範冒名留言
        $username_display = $_SESSION['user']['name'];
        $user_id = $_SESSION['user']['id'];

        if ($title === '' || $content === '') {
            $error = '標題與內容為必填欄位。';
        } else {
            try {
                // 修補重點 4：使用 Prepared Statements (參數化查詢) 進行留言寫入
                $stmt = $pdo->prepare("INSERT INTO messages (user_id, username_display, title, content) VALUES (:user_id, :username_display, :title, :content)");
                $stmt->execute([
                    'user_id' => $user_id,
                    'username_display' => $username_display,
                    'title' => $title,
                    'content' => $content
                ]);
                $success = '留言新增成功！';
            } catch (PDOException $e) {
                error_log("Insert message failed: " . $e->getMessage());
                $error = '寫入留言失敗，請稍後再試。';
            }
        }
    }
}

// 撈取所有留言
try {
    $stmt = $pdo->query("SELECT * FROM messages ORDER BY id DESC");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $messages = [];
    $error = '撈取留言失敗，請聯絡管理員。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>校園留言板 (安全版) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">💬 校園留言板 (安全版)</h2>
        <div>
            <span class="mr-3">目前登入：<?= h($_SESSION['user']['name']) ?> (<?= h($_SESSION['user']['role']) ?>)</span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= h($success) ?></div>
    <?php endif; ?>

    <!-- 新增留言區 -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-header bg-primary text-white font-weight-bold">撰寫新留言</div>
        <div class="card-body">
            <form method="POST" action="messages.php">
                <!-- 修補重點：嵌入防禦 CSRF 的 Token -->
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

                <div class="form-group mb-3">
                    <label class="form-label font-weight-bold">您的留言暱稱 (系統綁定)：</label>
                    <input type="text" class="form-control col-md-4 bg-light" readonly value="<?= h($_SESSION['user']['name']) ?>">
                </div>
                <div class="form-group mb-3">
                    <label for="title" class="form-label font-weight-bold">留言主題：</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="請輸入標題..." required>
                </div>
                <div class="form-group mb-3">
                    <label for="content" class="form-label font-weight-bold">留言內容：</label>
                    <textarea name="content" id="content" rows="4" class="form-control" placeholder="請輸入內容 (HTML 代碼將會被轉義編碼)..." required></textarea>
                </div>
                <button type="submit" class="btn btn-primary font-weight-bold">送出留言</button>
            </form>
        </div>
    </div>

    <!-- 顯示留言區 -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-dark text-white font-weight-bold">留言列表</div>
        <div class="card-body">
            <?php if (empty($messages)): ?>
                <p class="text-muted text-center py-4">目前尚無留言。</p>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="border rounded p-3 mb-3 bg-white">
                        <div class="d-flex justify-content-between text-muted text-small mb-2">
                            <span>
                                👤 <strong><?= h($msg['username_display']) ?></strong> 
                                (帳號關聯 ID: <?= h($msg['user_id']) ?>)
                            </span>
                            <span>🕒 <?= h($msg['created_at']) ?></span>
                        </div>
                        <!-- 修補重點 5：留言主題與內容全部使用 h() 轉義編碼，徹底消滅 Stored XSS -->
                        <h5 class="mt-2 text-dark font-weight-bold"><?= h($msg['title']) ?></h5>
                        <div class="mt-2 text-secondary" style="white-space: pre-wrap;"><?= h($msg['content']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
