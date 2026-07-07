<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$id = $_GET['id'] ?? '';
$course = null;
$error = '';

try {
    // 教學用弱點 1：SQL 注入。直接拼接 $_GET['id'] 到 SQL 語句中
    // 可以測試 course_detail.php?id=1 UNION SELECT 1,username,password_hash,role,name,email,7 FROM users
    $sql = "SELECT * FROM courses WHERE id = " . $id;
    $stmt = $pdo->query($sql);
    $course = $stmt->fetch();
} catch (PDOException $e) {
    // 教學用弱點 2：詳細錯誤外洩，利於黑客調試 UNION-based SQLi
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>課程詳細 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📖 課程詳細資訊</h2>
        <div>
            <a href="/courses.php" class="btn btn-primary">回課程查詢</a>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold">
            資料庫查詢錯誤：<br>
            <pre class="bg-dark text-warning p-3 mt-2 rounded"><?= $error ?></pre>
        </div>
    <?php endif; ?>

    <?php if ($course): ?>
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white font-weight-bold">
                <?= $course['title'] ?>
            </div>
            <div class="card-body">
                <h5 class="card-title">課程大綱與介紹</h5>
                <p class="card-text text-muted"><?= $course['description'] ?></p>
                <hr>
                <div class="row">
                    <div class="col-md-4">
                        <strong>學分數：</strong> <?= $course['credit'] ?> 學分
                    </div>
                    <div class="col-md-4">
                        <strong>上課教室：</strong> <?= $course['classroom'] ?>
                    </div>
                    <div class="col-md-4">
                        <strong>建立時間：</strong> <?= $course['created_at'] ?>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php if (!$error): ?>
            <div class="alert alert-warning">找不到該課程資料。您可以嘗試輸入 <code>1</code>。</div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
