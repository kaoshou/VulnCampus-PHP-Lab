<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');

// CORS
$allowed_origin = 'http://localhost:8081';
header('Access-Control-Allow-Origin: ' . $allowed_origin);
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => '只允許 POST 請求']);
    exit;
}

// 獲取 Token 與 Session 進行雙重認證檢查
$token = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
        $token = $matches[1];
    }
}
if (!$token) {
    $token = $_POST['token'] ?? '';
}

$is_admin = false;
$user_id = null;

// 1. 優先檢查 Session 登入狀態是否為管理員
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin') {
    $is_admin = true;
    $user_id = $_SESSION['user']['id'];
}

// 2. 若無 Session，檢查 API Token 是否為管理員所有
if (!$is_admin && $token !== '') {
    try {
        $token_stmt = $pdo->prepare("SELECT t.user_id, u.role FROM api_tokens t 
                                     INNER JOIN users u ON t.user_id = u.id 
                                     WHERE t.token = :token AND (t.expires_at IS NULL OR t.expires_at > NOW())");
        $token_stmt->execute(['token' => $token]);
        $token_data = $token_stmt->fetch();
        
        if ($token_data && $token_data['role'] === 'admin') {
            $is_admin = true;
            $user_id = intval($token_data['user_id']);
        }
    } catch (PDOException $e) {
        error_log("API Admin check failed: " . $e->getMessage());
    }
}

// 修補重點 1：管理權限校驗。非管理員角色一律拒絕呼叫此 API (返回 403)
if (!$is_admin) {
    write_audit_log($pdo, "未授權 API 審核呼叫嘗試！");
    http_response_code(403);
    echo json_encode(['error' => '權限不足 (403 Forbidden)，此操作需要管理員權限']);
    exit;
}

$input = json_decode(file_get_contents('php://output'), true) ?? $_POST;
$registration_id = intval($input['registration_id'] ?? 0);
$status = trim($input['status'] ?? 'approved');

// 限制 status 只能是白名單值
if ($registration_id <= 0 || !in_array($status, ['approved', 'registered', 'cancelled'])) {
    http_response_code(400);
    echo json_encode(['error' => '不合法的引數參數']);
    exit;
}

try {
    // 修補重點 2：參數化查詢更新報名狀態
    $stmt = $pdo->prepare("UPDATE event_registrations SET status = :status WHERE id = :id");
    $stmt->execute([
        'status' => $status,
        'id' => $registration_id
    ]);

    write_audit_log($pdo, "管理員審核報名 (報名編號: $registration_id, 狀態更新為: $status)");

    echo json_encode([
        'status' => 'success',
        'message' => '報名狀態審核更新成功！',
        'updated_id' => $registration_id,
        'new_status' => $status
    ]);
} catch (PDOException $e) {
    error_log("API admin approve error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => '系統異常，審核失敗']);
}
