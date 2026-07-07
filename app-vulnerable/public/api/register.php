<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 教學用弱點 1：API 缺乏認證 (Broken Object Level Authorization / BOLA)
// 未檢查 API Token 或者是 Session，且直接接收 POST 的 user_id

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允許 POST 請求']);
    exit;
}

// 接收 JSON 或 POST 表單參數
$input = json_decode(file_get_contents('php://output'), true) ?? $_POST;

$event_id = $input['event_id'] ?? '';
$user_id = $input['user_id'] ?? ''; // 教學用弱點 2：可隨意傳入 user_id 冒用他人身份報名
$quantity = $input['quantity'] ?? 1;
$final_price = $input['final_price'] ?? 0; // 教學用弱點 3：前端傳入價格，後端不重新計算，直接寫入庫

// 教學用弱點 4：Mass Assignment / 未過濾欄位寫入。
// 這裡直接接收前端的 status 參數 (例如傳入 approved 即可跳過管理員審核)
$status = $input['status'] ?? 'registered'; 

if (!$event_id || !$user_id) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必要的 event_id 或 user_id']);
    exit;
}

try {
    // 檢查活動是否存在 (拼接 SQLi)
    $stmt = $pdo->query("SELECT * FROM events WHERE id = " . $event_id);
    $event = $stmt->fetch();
    
    if (!$event) {
        http_response_code(404);
        echo json_encode(['error' => '活動不存在']);
        exit;
    }

    // 插入報名紀錄
    $sql = "INSERT INTO event_registrations (event_id, user_id, quantity, final_price, status) 
            VALUES ($event_id, $user_id, $quantity, $final_price, '$status')";
    $pdo->exec($sql);
    
    $new_id = $pdo->lastInsertId();

    echo json_encode([
        'status' => 'success',
        'message' => '報名成功！(API 方式)',
        'registration_id' => $new_id
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '資料庫錯誤：' . $e->getMessage()]);
}
