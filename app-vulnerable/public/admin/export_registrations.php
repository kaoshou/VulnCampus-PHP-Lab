<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 教學用弱點 1：Broken Access Control。未在後端做權限校驗，任何一般使用者皆能訪問此檔案下載名冊
// 甚至不需要檢查是否登入！
// 教學用弱點 2：A09:2025 紀錄失效。此下載操作包含全校學生的敏感個資 (學號, 身分證字號, 手機等)，後端沒有寫入任何 Audit Log 稽核日誌。

try {
    // 撈取名冊
    $sql = "SELECT r.id as reg_id, e.title as event_title, u.username, u.name, u.email, u.phone, u.student_no, u.national_id_fake, r.quantity, r.final_price, r.status, r.created_at 
            FROM event_registrations r 
            INNER JOIN events e ON r.event_id = e.id 
            INNER JOIN users u ON r.user_id = u.id";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll();
    
    // 設定下載 CSV 檔頭
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=vulncampus_registrations_' . date('YmdHis') . '.csv');
    
    // 輸出 BOM 防止 Excel 開啟亂碼
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // 寫入 CSV 標題
    fputcsv($output, ['報名編號', '活動名稱', '使用者帳號', '姓名', '電子郵件', '聯絡電話', '學號', '身分證字號', '數量', '實付金額', '狀態', '時間']);
    
    // 寫入資料
    foreach ($rows as $row) {
        fputcsv($output, [
            $row['reg_id'],
            $row['event_title'],
            $row['username'],
            $row['name'],
            $row['email'], // 教學用弱點 3：資料過度暴露，完全明文匯出敏感個資
            $row['phone'],
            $row['student_no'],
            $row['national_id_fake'], // 敏感個資 (假身分證字號) 直接外洩
            $row['quantity'],
            $row['final_price'],
            $row['status'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    // 錯誤外洩
    echo "匯出出錯：" . $e->getMessage();
}
