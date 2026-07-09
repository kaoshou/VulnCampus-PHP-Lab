<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 不再強制跳轉登入，以利學員/訪客直接點擊體驗！
$is_logged_in = isset($_SESSION['user']);
$action_triggered = false;
$deleted_count = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_triggered = true;
    
    if ($is_logged_in) {
        $user_id = intval($_SESSION['user']['id']);
        // 統計有多少筆報名會被清空以供顯示
        $stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations WHERE user_id = $user_id");
        $deleted_count = intval($stmt->fetchColumn());
        
        // 執行真實刪除
        $pdo->exec("DELETE FROM event_registrations WHERE user_id = $user_id");
    }
    
    // 重導向父視窗，給予學員最即時、震撼的點擊劫持成功回饋
    $guest_param = $is_logged_in ? '0' : '1';
    echo "<script>window.parent.location.href = '/clickjacking_poc.php?triggered=1&deleted=' + " . $deleted_count . " + '&guest=' + " . $guest_param . ";</script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>帳號安全設定 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #fff; font-family: sans-serif; margin: 0; padding: 0; width: 500px; height: 300px; overflow: hidden; }
        .target-box { width: 500px; height: 300px; border: 1px solid #ddd; padding: 20px; box-sizing: border-box; position: relative; }
        
        /* 關鍵按鈕的位置：固定像素排版，以利 Clickjacking PoC 精準對齊 */
        #critical-button {
            position: absolute;
            top: 100px;
            left: 150px;
            width: 200px;
            height: 50px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="target-box">
    <?php if ($action_triggered): ?>
        <div class="text-center mt-3">
            <h5 class="text-danger font-weight-bold">💥 警告：您已中招！</h5>
            <?php if ($is_logged_in): ?>
                <p class="text-muted small mb-2">已在您不知情下，成功清空了您的 <strong><?= $deleted_count ?></strong> 筆活動報名紀錄！</p>
            <?php else: ?>
                <p class="text-muted small mb-2">（目前為訪客身分）若您已登入，此動作會立刻將您的活動報名紀錄清空！</p>
            <?php endif; ?>
            <p class="text-secondary small">這就是點擊劫持 (Clickjacking) 的實際危害。</p>
            <a href="/events.php" target="_parent" class="btn btn-sm btn-outline-danger">📅 前往活動列表驗證</a>
        </div>
    <?php else: ?>
        <h5 class="text-dark font-weight-bold">📅 活動報名管理設定</h5>
        <p class="text-muted small">點擊下方按鈕將立刻<strong>取消並清空</strong>您所有的活動報名歷史紀錄：</p>
        
        <form method="POST">
            <button type="submit" id="critical-button" class="btn btn-danger shadow-sm">
                💥 確定清空我的活動報名
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
