<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 檢查是否登入
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '未登入']);
    exit;
}

// 接收欲查詢的 user_id。若無則預設為當前登入者 ID
$target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $_SESSION['user']['id'];

// 安全防禦 1：IDOR 存取控制。後端校驗請求之 user_id 是否與當前 Session 的使用者本人一致
// 僅允許使用者查詢自己，或是系統管理員（role === 'admin'）查詢，其餘一律拒絕
if ($target_user_id !== $_SESSION['user']['id'] && $_SESSION['user']['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => '權限不足 (403 Forbidden)：您無權存取其他使用者的隱私定位歷史資料'
    ]);
    exit;
}

try {
    // 安全防禦 2：使用參數化查詢
    $stmt = $pdo->prepare("SELECT id, latitude, longitude, memo, created_at FROM checkins WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute([':user_id' => $target_user_id]);
    $checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $checkins
    ]);
    exit;
} catch (PDOException $e) {
    error_log("Database error during fetching check-in history: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => '伺服器錯誤，無法載入歷史資料'
    ]);
    exit;
}
