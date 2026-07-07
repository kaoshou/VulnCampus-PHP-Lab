<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// 教學用弱點 1：管理功能 API 存取權限缺失 (Broken Access Control / BOLA)
// 後端未校驗 Session 中的角色是否為 admin。任何一般登入的使用者，甚至是未登入的訪客，都可以直接對此 API 送出請求

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允許 POST 請求']);
    exit;
}

$input = json_decode(file_get_contents('php://output'), true) ?? $_POST;
$registration_id = $input['registration_id'] ?? '';
$status = $input['status'] ?? 'approved'; // 預設審核通過

if (!$registration_id) {
    http_response_code(400);
    echo json_encode(['error' => '缺少報名編號 registration_id']);
    exit;
}

try {
    // 教學用弱點 2：SQL 注入
    $sql = "UPDATE event_registrations SET status = '$status' WHERE id = " . $registration_id;
    $pdo->exec($sql);

    echo json_encode([
        'status' => 'success',
        'message' => '報名狀態更新成功！',
        'updated_id' => $registration_id,
        'new_status' => $status
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '審核失敗，資料庫錯誤：' . $e->getMessage()]);
}
