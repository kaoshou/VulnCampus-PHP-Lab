<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 檢查是否登入
check_login();

$error = '';
$success = '';

// 處理留言送出
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $content = $_POST['content'] ?? '';

    if (empty($name) || empty($content)) {
        $error = '姓名或留言內容不能為空！';
    } else {
        try {
            // 漏洞點 1：SQL 注入。此處直接拼接 SQL
            $sql = "INSERT INTO stored_messages (name, content) VALUES ('$name', '$content')";
            $pdo->exec($sql);
            $success = '留言發表成功！';
        } catch (PDOException $e) {
            $error = '資料庫出錯：' . $e->getMessage();
        }
    }
}

// 讀取所有留言
try {
    $stmt = $pdo->query("SELECT * FROM stored_messages ORDER BY created_at DESC");
    $messages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = '無法讀取留言：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">

<head>
    <meta charset="UTF-8">
    <title>💬 預存型 XSS 測試 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .card {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
            <h2>💬 預存型 XSS (Stored XSS) 專屬演練</h2>
            <div>
                <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
                <a href="/index.php" class="btn btn-secondary">回首頁</a>
            </div>
        </div>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
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
                            <div class="form-group">
                                <label for="name" class="font-weight-bold">您的姓名 (Name)：</label>
                                <input type="text" name="name" id="name" class="form-control" placeholder="輸入暱稱..."
                                    required value="<?= sanitize($_SESSION['user']['name']) ?>">
                            </div>
                            <div class="form-group">
                                <label for="content" class="font-weight-bold">留言內容 (Message)：</label>
                                <textarea name="content" id="content" class="form-control" rows="4"
                                    placeholder="歡迎留下您的足跡..." required></textarea>
                                <small class="form-text text-muted">提示：留言送出後會被永久儲存在資料庫。任何造訪本頁面的人皆會觸發漏洞。</small>
                            </div>
                            <button type="submit" class="btn btn-success btn-block">📢 送出留言</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 歷史留言列表 -->
            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-dark text-white font-weight-bold">
                        📜 訪客留言列表
                    </div>
                    <div class="card-body">
                            <?php if (empty($messages)): ?>
                            <p class="text-center text-muted">目前尚無留言</p>
                            <?php else: ?>
                                <?php foreach ($messages as $msg): ?>
                                <div class="border-bottom pb-2 mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <!-- 漏洞點 2：直接印出 name，造成 XSS -->
                                        <span class="font-weight-bold text-primary">👤 <?= $msg['name'] ?></span>
                                        <small class="text-muted"><?= $msg['created_at'] ?></small>
                                    </div>
                                    <!-- 漏洞點 3：直接印出 content，未經過濾與轉義，引發預存型 XSS 漏洞 -->
                                    <p class="mb-0 bg-light p-2 rounded"><?= $msg['content'] ?></p>
                                </div>
                                        <?php foreach_end: // 這裡可以直接括號結束，PHP 括號使用標準即可 ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>

</html>