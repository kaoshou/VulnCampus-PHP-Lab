<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 檢查是否登入
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '未登入']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $lat = $_POST['latitude'] ?? '';
    $lng = $_POST['longitude'] ?? '';
    $memo = $_POST['memo'] ?? '';

    // 安全防禦 1：驗證經緯度是否符合基本格式（浮點數字串）
    if (!is_numeric($lat) || !is_numeric($lng)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'GPS 經緯度格式錯誤']);
        exit;
    }

    try {
        // 安全防禦 2：使用 PDO 預處理 (Prepared Statements) 防範 SQL 注入
        $stmt = $pdo->prepare("INSERT INTO checkins (user_id, latitude, longitude, memo) VALUES (:user_id, :latitude, :longitude, :memo)");
        $stmt->execute([
            ':user_id' => $user_id,
            ':latitude' => $lat,
            ':longitude' => $lng,
            ':memo' => $memo
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => '打卡成功'
        ]);
        exit;
    } catch (PDOException $e) {
        // 安全防禦 3：發生例外時進行捕獲，只記錄在伺服器 log，前端僅顯示安全模糊錯誤，防止結構外洩
        error_log("Database error during check-in: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => '伺服器目前無法處理此打卡，請稍後再試。'
        ]);
        exit;
    }
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed'
    ]);
    exit;
}
