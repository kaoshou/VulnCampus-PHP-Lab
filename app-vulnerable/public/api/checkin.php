<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 檢查是否登入
check_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user']['id'];
    $lat = $_POST['latitude'] ?? '';
    $lng = $_POST['longitude'] ?? '';
    $memo = $_POST['memo'] ?? '';

    // 教學用漏洞：SQL 注入。此處直接將 $lat, $lng, $memo 與 $user_id 拼接進 SQL 語句中
    // 可以輸入：memo = "test', '0', '0') -- " 來截斷
    $sql = "INSERT INTO checkins (user_id, latitude, longitude, memo) VALUES ($user_id, '$lat', '$lng', '$memo')";
    
    // 直接調用 exec，若有 SQL 語法錯誤會直接拋出 PDOException
    $pdo->exec($sql);

    echo json_encode([
        'status' => 'success',
        'message' => '打卡成功'
    ]);
    exit;
} else {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed'
    ]);
    exit;
}
