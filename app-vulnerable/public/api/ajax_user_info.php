<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

header('Content-Type: application/json');

// 漏洞點：錯誤配置 CORS，允許跨域讀取敏感憑證與資料
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
}

// 漏洞點：API 缺乏認證校驗，未檢查 Session
$id = $_GET['id'] ?? '';

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => '缺少必要的 id 參數']);
    exit;
}

try {
    // 漏洞點：SQL 注入。直接拼接參數
    $sql = "SELECT * FROM users WHERE id = " . $id;
    $stmt = $pdo->query($sql);
    $user = $stmt->fetch();

    if ($user) {
        // 漏洞點：API 資料過度暴露，直接回傳整行個資 (包含雜湊及假身分證)
        echo json_encode([
            'status' => 'success',
            'data' => $user
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => '找不到該使用者']);
    }
} catch (PDOException $e) {
    // 漏洞點：詳細資料庫錯誤外洩
    http_response_code(500);
    echo json_encode(['error' => '資料庫出錯：' . $e->getMessage()]);
}
