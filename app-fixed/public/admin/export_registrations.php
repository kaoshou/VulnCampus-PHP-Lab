<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 修補重點 1：嚴格後端權限校驗，僅限 admin 角色下載
check_auth(['admin']);

// 修補重點 2：對高敏感個資下載操作寫入日誌 (A09:2025 Security Logging)
write_audit_log($pdo, "管理員匯出學生報名名冊");

try {
    // 撈取名冊
    $sql = "SELECT r.id as reg_id, e.title as event_title, u.username, u.name, u.email, u.phone, u.student_no, u.national_id_fake, r.quantity, r.final_price, r.status, r.created_at 
            FROM event_registrations r 
            INNER JOIN events e ON r.event_id = e.id 
            INNER JOIN users u ON r.user_id = u.id";
    
    // 使用預處理安全讀取
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
            // 修補重點 3：個資去識別化與遮蔽 (Sensitive Data Masking)
            mask_data('email', $row['email']), 
            mask_data('phone', $row['phone']),
            $row['student_no'],
            mask_data('national_id', $row['national_id_fake']), 
            $row['quantity'],
            $row['final_price'],
            $row['status'],
            $row['created_at']
        ]);
    }
    
    fclose($output);
    exit;

} catch (PDOException $e) {
    error_log("Export registrations failed: " . $e->getMessage());
    http_response_code(500);
    echo "系統錯誤，無法匯出資料。";
}
