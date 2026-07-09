<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
// 權限檢查：確保登入
check_auth();

// 🟢 安全防禦：在頁面中輸出禁止嵌入之標頭
header("X-Frame-Options: DENY");
header("Content-Security-Policy: frame-ancestors 'none';");

$action_triggered = false;
$deleted_count = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 驗證 CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf_token)) {
        $action_triggered = true;
        
        $user_id = intval($_SESSION['user']['id']);
        // 統計筆數
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $deleted_count = intval($stmt->fetchColumn());
        
        // 參數化查詢執行刪除
        $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // 重導向父視窗
        echo "<script>window.parent.location.href = '/clickjacking_poc.php?triggered=1&deleted=' + " . $deleted_count . " + '&guest=0';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>帳號安全設定 - VulnCampus 修正版</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #fff; font-family: sans-serif; margin: 0; padding: 0; width: 500px; height: 300px; overflow: hidden; }
        .target-box { width: 500px; height: 300px; border: 1px solid #ddd; padding: 20px; box-sizing: border-box; position: relative; }
        
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
            <h5 class="text-success font-weight-bold">🟢 已安全清空活動報名</h5>
            <p class="text-muted small">模擬清空成功（共清空了 <strong><?= $deleted_count ?></strong> 筆資料）。</p>
            <a href="/events.php" target="_parent" class="btn btn-sm btn-outline-success">📅 前往活動列表</a>
        </div>
    <?php else: ?>
        <h5 class="text-dark font-weight-bold">📅 活動報名管理設定 (安全防護)</h5>
        <p class="text-muted small">點擊下方按鈕將立刻<strong>取消並清空</strong>您所有的活動報名歷史紀錄：</p>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
            <button type="submit" id="critical-button" class="btn btn-danger shadow-sm">
                💥 確定清空我的活動報名
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
