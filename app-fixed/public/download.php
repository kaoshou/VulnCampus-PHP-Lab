<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 檢查登入
check_auth();

$user_id = $_SESSION['user']['id'];
$error_msg = '';

// ==========================================
// 1. 處理下載邏輯 (修補重點：使用 File ID 查表下載，移除用戶對路徑的直接控制權)
// ==========================================
if (isset($_GET['file_id'])) {
    $file_id = intval($_GET['file_id']);

    try {
        // 使用預處理從資料庫撈取檔案記錄
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = :id");
        $stmt->execute(['id' => $file_id]);
        $file = $stmt->fetch();

        if ($file) {
            // 修補重點：檢查越權存取。若為私密檔案，則僅能由本人或 admin 下載
            if (!$file['is_public'] && $file['owner_user_id'] !== $user_id && $_SESSION['user']['role'] !== 'admin') {
                write_audit_log($pdo, "越權下載嘗試：試圖讀取檔案 ID $file_id");
                http_response_code(403);
                die("權限不足：您無權下載此私密檔案！");
            }

            // 安全建構下載路徑，只允許 basename 檔名，防止 path traversal
            $safe_filename = basename($file['storage_path']);
            // 檔案實際存放於 public/files/ 目錄下 (若是敏感備份等已被移出 web root)
            $filepath = __DIR__ . '/files/' . $safe_filename;

            if (file_exists($filepath) && is_file($filepath)) {
                // 寫入下載成功稽核日誌
                write_audit_log($pdo, "安全下載檔案：$safe_filename (檔案編號: $file_id)");

                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($file['filename']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                
                readfile($filepath);
                exit;
            } else {
                $error_msg = "下載失敗：伺服器檔案遺失。";
            }
        } else {
            $error_msg = "下載失敗：無此檔案編號。";
        }
    } catch (PDOException $e) {
        error_log("Download file error: " . $e->getMessage());
        $error_msg = "下載出錯，系統異常。";
    }
}

// ==========================================
// 2. 撈取檔案列表
// ==========================================
$files = [];
try {
    // 修補重點：一般用戶在列表只能看到「公開檔案」或是「自己上傳的私密檔案」(防範個資外洩)
    if ($_SESSION['user']['role'] === 'admin') {
        $stmt = $pdo->query("SELECT f.*, u.username as owner_name FROM files f INNER JOIN users u ON f.owner_user_id = u.id");
    } else {
        $stmt = $pdo->prepare("SELECT f.*, u.username as owner_name 
                               FROM files f 
                               INNER JOIN users u ON f.owner_user_id = u.id 
                               WHERE f.is_public = 1 OR f.owner_user_id = :user_id");
        $stmt->execute(['user_id' => $user_id]);
    }
    $files = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get file list failed: " . $e->getMessage());
    $error_msg = '無法獲取檔案清單。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>檔案下載區 - VulnCampus (安全版)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📥 校園檔案下載區 (安全版)</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <?php if ($error_msg !== ''): ?>
        <div class="alert alert-danger font-weight-bold"><?= h($error_msg) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-primary text-white font-weight-bold py-3">
            授權檔案列表
        </div>
        <div class="card-body p-4">
            <p class="text-muted">本區提供校園公開檔案及您上傳的個人文件下載。</p>
            
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>檔案編號</th>
                        <th>檔案名稱</th>
                        <th>擁有者</th>
                        <th>類型</th>
                        <th>下載連結</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $f): ?>
                        <tr>
                            <td><?= h($f['id']) ?></td>
                            <td><?= h($f['filename']) ?></td>
                            <td><?= h($f['owner_name']) ?></td>
                            <td><?= $f['is_public'] ? '<span class="badge bg-success">公開</span>' : '<span class="badge bg-warning">私密</span>' ?></td>
                            <td>
                                <!-- 修補重點：下載連結全面改用 ID 查表傳遞，不暴露 storage_path 硬碟路徑 -->
                                <a href="download.php?file_id=<?= h($f['id']) ?>" class="btn btn-sm btn-primary font-weight-bold">安全下載</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-success">
        🛡️ <strong>安全防範設計：</strong><br>
        1. <strong>查表式下載</strong>：不接收任何檔案路徑作為參數，前端僅傳遞 <code>file_id=1</code>，後端向資料庫查詢該 ID 取得檔案路徑。<br>
        2. <strong>路徑過濾</strong>：在載入硬碟檔案時，後端強制執行 <code>basename()</code> 過濾，避免遭受 Path Traversal (如 <code>../../etc/passwd</code>) 跨越目錄限制。<br>
        3. <strong>授權校驗</strong>：在下載前，後端校驗此檔案是否屬公開檔案。若是私密檔案，則檢查擁有者與當前登入 Session 是否一致，否則回傳 403 拒絕請求，防範 Broken Access Control。
    </div>
</div>

</body>
</html>
