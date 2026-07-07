<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');

// CORS 限制
$allowed_origin = 'http://localhost:8081';
header('Access-Control-Allow-Origin: ' . $allowed_origin);
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允許 POST 請求']);
    exit;
}

// 獲取 Token 驗證
$token = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = $matches[1];
    }
}
if (!$token) {
    $token = $_POST['token'] ?? '';
}

// 修補重點 1：API 存取控制。未提供合法 Token 拒絕報名
if ($token === '') {
    http_response_code(401);
    echo json_encode(['error' => '未提供授權 Token']);
    exit;
}

try {
    $token_stmt = $pdo->prepare("SELECT * FROM api_tokens WHERE token = :token AND (expires_at IS NULL OR expires_at > NOW())");
    $token_stmt->execute(['token' => $token]);
    $token_data = $token_stmt->fetch();

    if (!$token_data) {
        http_response_code(401);
        echo json_encode(['error' => '無效或已過期的 Token']);
        exit;
    }

    // 取得 token 綁定之 user_id
    $token_user_id = intval($token_data['user_id']);

    // 接收 JSON 或 POST 表單參數
    $input = json_decode(file_get_contents('php://output'), true) ?? $_POST;

    // 修補重點 2：參數白名單 (Mass Assignment 防禦)。
    // 後端只接收白名單欄位，強制 user_id 只能為 token 擁有者本人，不接受 status 與價格自定義傳入
    $event_id = intval($input['event_id'] ?? 0);
    $quantity = intval($input['quantity'] ?? 1);

    if ($event_id <= 0 || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['error' => '不合法的 event_id 或 quantity']);
        exit;
    }

    // 啟動交易處理防範超賣
    $pdo->beginTransaction();

    // 鎖定行
    $stmt = $pdo->prepare("SELECT price, quota FROM events WHERE id = :id FOR UPDATE");
    $stmt->execute(['id' => $event_id]);
    $event = $stmt->fetch();

    if (!$event) {
        throw new Exception('活動不存在');
    }

    if ($event['quota'] < $quantity) {
        throw new Exception('餘額不足，無法報名！');
    }

    // 後端計算價格，不接收前端傳入的 final_price 參數
    $final_price = $event['price'] * $quantity;

    // 扣減名額
    $sub_stmt = $pdo->prepare("UPDATE events SET quota = quota - :qty WHERE id = :id");
    $sub_stmt->execute(['qty' => $quantity, 'id' => $event_id]);

    // 寫入報名紀錄，狀態預設為 registered，忽略前端可能傳入的 status=approved 參數
    $ins_stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, quantity, final_price, status) 
                               VALUES (:event_id, :user_id, :quantity, :final_price, 'registered')");
    $ins_stmt->execute([
        'event_id' => $event_id,
        'user_id' => $token_user_id,
        'quantity' => $quantity,
        'final_price' => $final_price
    ]);
    
    $new_id = $pdo->lastInsertId();

    $pdo->commit();
    write_audit_log($pdo, "API 報名成功 (活動ID: $event_id, 數量: $quantity, 價格: $final_price, 報名編號: $new_id)");

    echo json_encode([
        'status' => 'success',
        'message' => '報名成功！',
        'registration_id' => $new_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("API register error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
