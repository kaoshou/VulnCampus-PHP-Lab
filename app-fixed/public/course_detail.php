<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$id = intval($_GET['id'] ?? 0); // 修補重點 1：將 ID 轉成整數，防禦 SQLi 與無效參數
$course = null;
$error = '';

if ($id > 0) {
    try {
        // 修補重點 2：使用 Prepared Statement 參數化查詢，防止 Union-based SQLi
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $course = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Database course_detail query error: " . $e->getMessage());
        $error = '系統目前無法完成查詢，請稍後再試。';
    }
} else {
    $error = '無效的課程編號。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>課程詳細 - VulnCampus (安全版)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📖 課程詳細資訊 (安全版)</h2>
        <div>
            <a href="/courses.php" class="btn btn-primary">回課程查詢</a>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <!-- 安全錯誤訊息 -->
    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($course): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-primary text-white font-weight-bold py-3">
                <?= h($course['title']) ?>
            </div>
            <div class="card-body p-4">
                <h5 class="card-title mb-3">課程大綱與介紹</h5>
                <!-- 使用 h() 確保 XSS 安全 -->
                <p class="card-text text-muted mb-4"><?= h($course['description']) ?></p>
                <hr>
                <div class="row text-center">
                    <div class="col-md-4">
                        <strong>學分數：</strong> <?= h($course['credit']) ?> 學分
                    </div>
                    <div class="col-md-4">
                        <strong>上課教室：</strong> <?= h($course['classroom']) ?>
                    </div>
                    <div class="col-md-4">
                        <strong>建立時間：</strong> <?= h($course['created_at']) ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
