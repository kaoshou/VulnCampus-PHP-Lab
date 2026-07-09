<?php
require_once __DIR__ . '/../src/helpers.php';

check_login();

$error = '';
$success = '';
$uploaded_path = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['avatar']['name'];
        $file_tmp  = $_FILES['avatar']['tmp_name'];
        
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 第三關：不安全後端 MIME 類型驗證 (只檢查 Content-Type 標頭)
        // 這會直接信任用戶端（如 ZAP/瀏覽器）發送的 Content-Type Request Header，容易被修改繞過
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $client_mime = $_FILES['avatar']['type'] ?? '';

        if (!in_array($client_mime, $allowed_types)) {
            $error = '上傳失敗：後端檢測此檔案之 Content-Type 非圖片格式（僅允許 image/jpeg, image/png, image/gif），您傳送的是：' . htmlspecialchars($client_mime);
        } else {
            $destination = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $success = '大頭貼上傳成功！';
                $uploaded_path = '/uploads/' . $file_name;
            } else {
                $error = '檔案移動失敗，請檢查權限。';
            }
        }
    } else {
        $error = '上傳出錯，錯誤代碼：' . ($_FILES['avatar']['error'] ?? '未知');
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>第三關：後端 Content-Type 弱驗證繞過 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🎯 第三關：後端 Content-Type 弱驗證繞過</h2>
        <div>
            <a href="/upload.php" class="btn btn-info">回上傳大廳</a>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?= $success ?><br>
            您的檔案已儲存至：<a href="<?= $uploaded_path ?>" target="_blank"><code><?= $uploaded_path ?></code></a>
        </div>
    <?php endif; ?>

    <div class="card col-md-8 mx-auto shadow-sm">
        <div class="card-header bg-info text-white font-weight-bold">
            大頭貼上傳 (僅限制後端 Content-Type 標頭)
        </div>
        <div class="card-body">
            <p class="text-muted">本表單前端無任何限制。但後端 PHP 會校驗客戶端傳送的 <code>$_FILES['avatar']['type']</code> 是否為圖片格式。</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatar">選擇大頭貼檔案：</label>
                    <!-- 刻意不加 accept 與 JS -->
                    <input type="file" name="avatar" id="avatar" class="form-control-file" required>
                </div>
                <button type="submit" class="btn btn-primary">開始上傳</button>
            </form>

            <hr>
            <div class="alert alert-warning">
                💡 <strong>本關卡繞過演練指引：</strong><br>
                1. <strong>漏洞原理</strong>：PHP 的 <code>$_FILES['...']['type']</code> 是從 HTTP Request 請求標頭（HTTP Headers）中的 <code>Content-Type</code> 提取出來的。因為這是由客戶端瀏覽器所產生的欄位，攻擊者可以使用代理工具（如 ZAP）將其改掉，因此只相信這個欄位是不安全的。<br>
                2. <strong>繞過方法 (ZAP 攔截與 Content-Type 竄改)</strong>：<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;a. 點選「選擇檔案」，直接選取您的 PHP 後門檔案（如 <code>shell.php</code>）。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;b. 開啟 ZAP 的中斷點攔截（Breakpoint）。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;c. 點選「開始上傳」，在 ZAP 攔截到 POST 請求時，往下拉找到該段參數的 <code>Content-Type: application/x-php</code>。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;d. 將其手動修改為 <code>Content-Type: image/png</code> 後放行送出，即可成功騙過後端弱驗證，上傳 Webshell！
            </div>
        </div>
    </div>
</div>

</body>
</html>
