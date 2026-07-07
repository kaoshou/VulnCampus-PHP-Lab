<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');

// 教學用弱點 1：API 存取缺乏認證。未檢查 API Token 或 Session
// 教學用弱點 2：CORS 錯誤配置。直接回傳 Access-Control-Allow-Origin: * 允許任何網域跨站讀取個資
header('Access-Control-Allow-Origin: *');

$id = $_GET['id'] ?? '';

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必要的 id 參數']);
    exit;
}

try {
    // 教學用弱點 3：SQL 注入。直接拼接參數
    $sql = "SELECT * FROM users WHERE id = " . $id;
    $stmt = $pdo->query($sql);
    $user = $stmt->fetch();

    if ($user) {
        // 教學用弱點 4：API 資料過度暴露。直接把整行 database row (包含 MD5 password_hash, national_id 等敏感個資) 轉成 JSON 拋出
        echo json_encode([
            'status' => 'success',
            'data' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => '找不到該使用者']);
    }
} catch (PDOException $e) {
    // 錯誤外洩
    http_response_code(500);
    echo json_encode(['status' => 'error', 'debug_message' => $e->getMessage()]);
}
