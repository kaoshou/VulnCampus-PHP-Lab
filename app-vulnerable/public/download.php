<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

check_login();

// ==========================================
// 1. 處理檔案下載邏輯 (弱點版：路徑遍歷 Path Traversal)
// ==========================================
if (isset($_GET['file'])) {
    $file = $_GET['file']; // 例如: files/calendar_2026.pdf

    // 教學用弱點 1：路徑遍歷 (Path Traversal)。直接將用戶輸入拼入路徑，且未過濾 ../ 字元
    // 可以測試 download.php?file=../src/db.php 來獲取資料庫帳密
    // 可以測試 download.php?file=../../../../../../etc/passwd 來獲取系統敏感檔案
    $filepath = __DIR__ . '/' . $file; 

    // 教學用弱點 2：越權存取。未校驗該檔案的所有者 (owner_user_id) 與當前登入者是否一致
    if (file_exists($filepath) && is_file($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // 輸出檔案內容
        readfile($filepath);
        exit;
    } else {
        $error_msg = "下載失敗：檔案不存在！嘗試路徑：" . $filepath;
    }
}

// ==========================================
// 2. 撈取檔案列表供前端展示
// ==========================================
$files = [];
try {
    // 弱點版：撈出全部檔案 (包含非公開與他人的檔案)，提供越權下載的目標
    $stmt = $pdo->query("SELECT f.*, u.username as owner_name FROM files f INNER JOIN users u ON f.owner_user_id = u.id");
    $files = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>檔案下載區 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📥 校園檔案下載區 (Path Traversal 測試)</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger font-weight-bold"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white font-weight-bold">
            可用檔案列表
        </div>
        <div class="card-body">
            <p class="text-muted">本區提供校園公開檔案及您上傳的個人文件下載。</p>
            
            <table class="table table-hover">
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
                            <td><?= $f['id'] ?></td>
                            <td><?= $f['filename'] ?></td>
                            <td><?= $f['owner_name'] ?></td>
                            <td><?= $f['is_public'] ? '<span class="badge badge-success">公開</span>' : '<span class="badge badge-warning">私密</span>' ?></td>
                            <td>
                                <!-- 弱點版：下載連結直接暴露了檔案的 storage_path，方便進行路徑竄改 -->
                                <a href="download.php?file=<?= $f['storage_path'] ?>" class="btn btn-sm btn-primary">下載檔案</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="alert alert-warning">
        💡 <strong>教學演練指引：</strong><br>
        1. 觀察「王小明_學生證影本.jpg」的下載連結：<code>download.php?file=files/student01_card.jpg</code><br>
        2. 嘗試修改 `file` 參數，將其改為 <code>../src/db.php</code>。<br>
        3. 送出請求，您將能直接下載 <code>db.php</code>，並在文字編輯器中看到資料庫的連線密碼！
    </div>
</div>

</body>
</html>
