<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

check_login();

// 撈取所有活動
try {
    $stmt = $pdo->query("SELECT * FROM events");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $events = [];
    $error = $e->getMessage();
}

// 撈取當前使用者的報名紀錄
$user_id = $_SESSION['user']['id'];
$registrations = [];
try {
    // 弱點版：使用 INNER JOIN
    $stmt = $pdo->query("SELECT r.*, e.title as event_title, e.price as event_price FROM event_registrations r INNER JOIN events e ON r.event_id = e.id WHERE r.user_id = $user_id");
    $registrations = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>活動列表與報名 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📅 校園活動報名系統</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 左側：活動列表 -->
        <div class="col-md-7">
            <h4 class="mb-3">可報名的活動</h4>
            <?php foreach ($events as $event): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title font-weight-bold text-primary"><?= $event['title'] ?></h5>
                        <p class="card-text text-muted"><?= $event['description'] ?></p>
                        <div class="row">
                            <div class="col-6">
                                💰 單價：<span class="text-danger font-weight-bold"><?= $event['price'] ?> 元</span>
                            </div>
                            <div class="col-6">
                                👥 賸餘名額：<span class="badge badge-info"><?= $event['quota'] ?> 人</span>
                            </div>
                        </div>
                        <a href="/event_register.php?event_id=<?= $event['id'] ?>" class="btn btn-sm btn-success mt-3">立即報名</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 右側：我已報名的活動 -->
        <div class="col-md-5">
            <h4 class="mb-3">我的報名明細</h4>
            <?php if (empty($registrations)): ?>
                <div class="alert alert-secondary">您目前沒有任何活動報名紀錄。</div>
            <?php else: ?>
                <?php foreach ($registrations as $reg): ?>
                    <div class="card mb-2 border-success">
                        <div class="card-body p-3">
                            <h6 class="card-title font-weight-bold mb-1"><?= $reg['event_title'] ?></h6>
                            <p class="card-text text-small mb-1">
                                數量：<strong><?= $reg['quantity'] ?></strong> | 
                                折扣代碼：<code><?= $reg['coupon_code'] ? $reg['coupon_code'] : '無' ?></code>
                            </p>
                            <p class="card-text font-weight-bold text-danger mb-2">
                                實付金額：<?= $reg['final_price'] ?> 元
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge badge-<?= $reg['status'] === 'registered' ? 'warning' : ($reg['status'] === 'approved' ? 'success' : 'danger') ?>">
                                    <?= $reg['status'] ?>
                                </span>
                                <!-- 教學用弱點：取消報名越權 (GET 請求，只要竄改 registration_id 即可取消任何人報名) -->
                                <a href="/event_register.php?action=cancel&registration_id=<?= $reg['id'] ?>" 
                                   class="btn btn-sm btn-outline-danger" 
                                   onclick="return confirm('確定取消報名？')">取消報名</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
