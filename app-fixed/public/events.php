<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 修補重點：嚴格登入權限檢查
check_auth();

$user_id = $_SESSION['user']['id'];
$events = [];
$registrations = [];
$error = '';

try {
    // 參數化查詢活動
    $stmt = $pdo->query("SELECT * FROM events");
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Select events failed: " . $e->getMessage());
    $error = '系統發生異常，請聯絡管理員。';
}

try {
    // 參數化查詢當前使用者的報名紀錄
    $stmt = $pdo->prepare("SELECT r.*, e.title as event_title, e.price as event_price 
                           FROM event_registrations r 
                           INNER JOIN events e ON r.event_id = e.id 
                           WHERE r.user_id = :user_id");
    $stmt->execute(['user_id' => $user_id]);
    $registrations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Select registrations failed: " . $e->getMessage());
    $error = '系統發生異常，請聯絡管理員。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>活動列表與報名 (安全版) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📅 校園活動報名系統 (安全版)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：活動列表 -->
        <div class="col-md-7">
            <h4 class="mb-3 text-dark font-weight-bold">可報名的活動</h4>
            <?php foreach ($events as $event): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title font-weight-bold text-primary"><?= h($event['title']) ?></h5>
                        <p class="card-text text-muted"><?= h($event['description']) ?></p>
                        <div class="row">
                            <div class="col-6">
                                💰 單價：<span class="text-danger font-weight-bold"><?= h($event['price']) ?> 元</span>
                            </div>
                            <div class="col-6">
                                👥 賸餘名額：<span class="badge bg-success"><?= h($event['quota']) ?> 人</span>
                            </div>
                        </div>
                        <a href="/event_register.php?event_id=<?= h($event['id']) ?>" class="btn btn-sm btn-success mt-3 font-weight-bold">立即報名</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- 右側：我已報名的活動 -->
        <div class="col-md-5">
            <h4 class="mb-3 text-dark font-weight-bold">我的報名明細</h4>
            <?php if (empty($registrations)): ?>
                <div class="alert alert-secondary">您目前沒有任何活動報名紀錄。</div>
            <?php else: ?>
                <?php foreach ($registrations as $reg): ?>
                    <div class="card mb-2 border-success shadow-sm">
                        <div class="card-body p-3">
                            <h6 class="card-title font-weight-bold mb-1"><?= h($reg['event_title']) ?></h6>
                            <p class="card-text text-small mb-1">
                                數量：<strong><?= h($reg['quantity']) ?></strong> | 
                                折扣代碼：<code><?= $reg['coupon_code'] ? h($reg['coupon_code']) : '無' ?></code>
                            </p>
                            <p class="card-text font-weight-bold text-danger mb-2">
                                實付金額：<?= h($reg['final_price']) ?> 元
                            </p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?= $reg['status'] === 'registered' ? 'warning' : ($reg['status'] === 'approved' ? 'success' : 'danger') ?>">
                                    <?= h($reg['status']) ?>
                                </span>
                                <!-- 修補重點：取消報名連結帶入 CSRF Token 以防禦 CSRF 攻擊 -->
                                <a href="/event_register.php?action=cancel&registration_id=<?= h($reg['id']) ?>&csrf_token=<?= get_csrf_token() ?>" 
                                   class="btn btn-sm btn-outline-danger font-weight-bold" 
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
