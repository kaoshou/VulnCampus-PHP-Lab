<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');

// 安全修補：不要任意配置 Access-Control-Allow-Origin: * 或任意反射 Origin 且帶 Credentials
// 僅限同源 (Same-Origin) 存取，或只允許特定信任域名，此處直接不輸出過寬的 CORS 標頭

// 安全修補：驗證使用者登入狀態
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => '未經授權的存取，請先登入']);
    exit;
}

$id = $_GET['id'] ?? '';

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必要的 id 參數']);
    exit;
}

// 安全修補：BOLA / IDOR 防護
// 一般學生僅能查詢自己的資料，除非是管理員 (role = admin)
if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['id'] != $id) {
    http_response_code(403);
    echo json_encode(['error' => '存取被拒絕：您無權檢視其他學生的資料。']);
    exit;
}

try {
    // 安全修補：SQL 注入防禦（使用 Prepared Statement 參數化查詢）
    $stmt = $pdo->prepare("SELECT id, username, role, name, email, phone, student_no, national_id_fake FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();

    if ($user) {
        // 安全修補：個資遮蔽處理，避免 API 敏感資訊過度暴露
        $user['national_id_fake'] = mask_data('national_id', $user['national_id_fake']);
        $user['phone'] = mask_data('phone', $user['phone']);
        $user['email'] = mask_data('email', $user['email']);
        
        echo json_encode([
            'status' => 'success',
            'data' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => '找不到該使用者']);
    }
} catch (PDOException $e) {
    // 安全修補：防禦 SQL Error 洩漏，不將詳細資料庫追蹤訊息拋給前端
    http_response_code(500);
    echo json_encode(['error' => '系統伺服器錯誤，請聯絡管理員。']);
}
