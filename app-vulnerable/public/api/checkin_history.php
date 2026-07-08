<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 檢查是否登入
check_login();

// 接收 user_id，若無則預設為當前登入者 ID
$target_user_id = $_GET['user_id'] ?? $_SESSION['user']['id'];

// 教學用漏洞：IDOR (水平越權)。後端「完全沒有」檢查 $target_user_id 是否為當前登入的用戶本人。
// 任何人只要帶入別人的 user_id，都可以查出其所有的定位打卡隱私足跡。
try {
    // 同時，這裡亦存在 SQL 注入，因為直接拼接了來自 GET 的 user_id
    $sql = "SELECT id, latitude, longitude, memo, created_at FROM checkins WHERE user_id = " . $target_user_id . " ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    $checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $checkins
    ]);
    exit;
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => '資料庫錯誤：' . $e->getMessage()
    ]);
    exit;
}
