<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');

// 修補重點 1：CORS 安全設定。限定僅允許本機信任的 Origin 進行跨站請求，而非 wildcard (*)
$allowed_origin = 'http://localhost:8081';
header('Access-Control-Allow-Origin: ' . $allowed_origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// 處理 OPTIONS 預檢請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// 獲取 Token (支援 Header 或 URL 參數)
$token = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = $matches[1];
    }
}
if (!$token) {
    $token = $_GET['token'] ?? '';
}

// 修補重點 2：API 存取控制。校驗 Token 的合法性與時效性
if ($token === '') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => '未提供授權憑證 (Unauthorized)']);
    exit;
}

try {
    $token_stmt = $pdo->prepare("SELECT * FROM api_tokens WHERE token = :token AND (expires_at IS NULL OR expires_at > NOW())");
    $token_stmt->execute(['token' => $token]);
    $token_data = $token_stmt->fetch();

    if (!$token_data) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => '無效或已過期的 API Token']);
        exit;
    }

    $token_user_id = intval($token_data['user_id']);
    
    // 獲取當前 token 使用者的權限角色
    $user_role_stmt = $pdo->prepare("SELECT role FROM users WHERE id = :id");
    $user_role_stmt->execute(['id' => $token_user_id]);
    $token_user = $user_role_stmt->fetch();
    $token_user_role = $token_user['role'] ?? 'student';

    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => '缺少必要的 id 參數']);
        exit;
    }

    // 修補重點 3：API 權限檢查 (BOLA/IDOR 防護)。確保 Token 擁有的 user_id 與查詢 ID 匹配，或者為 admin 角色
    if ($id !== $token_user_id && $token_user_role !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => '無權讀取該使用者檔案 (Forbidden)']);
        exit;
    }

    // 參數化查詢
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if ($user) {
        // 修補重點 4：防範資料過度暴露。過濾掉敏感的 password_hash，並對電話、身分證字號及信箱進行遮罩 (Masking)
        $safe_user = [
            'id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
            'name' => $user['name'],
            'email' => mask_data('email', $user['email']),
            'phone' => mask_data('phone', $user['phone']),
            'student_no' => $user['student_no'],
            'national_id_fake' => mask_data('national_id', $user['national_id_fake']),
            'created_at' => $user['created_at']
        ];

        echo json_encode([
            'status' => 'success',
            'data' => $safe_user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => '找不到該使用者']);
    }

} catch (PDOException $e) {
    error_log("API Profile error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => '伺服器內部錯誤']);
}
