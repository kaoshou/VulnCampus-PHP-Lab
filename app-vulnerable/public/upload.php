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
        
        // 確保 uploads 目錄存在
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // 教學用弱點 1：任意檔案上傳漏洞 (Unrestricted File Upload)
        // 這裡沒有對檔案副檔名 (extension) 與 MIME 類型進行任何安全校驗，允許上傳 .php 後門
        // 教學用弱點 2：直接使用使用者原始檔案名稱，容易造成檔名衝突、特殊字元注入
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            $success = '大頭貼上傳成功！';
            // 用於前端顯示與點擊執行
            $uploaded_path = '/uploads/' . $file_name;
        } else {
            $error = '檔案移動失敗，請檢查權限。';
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
    <title>上傳大頭貼 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📤 上傳個人大頭貼 (任意檔案上傳漏洞測試)</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
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
            大頭貼上傳 (無副檔名限制)
        </div>
        <div class="card-body">
            <p class="text-muted">上傳格式建議為 JPG/PNG 圖片。然而，系統並未對此進行限制...</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatar">選擇大頭貼檔案：</label>
                    <input type="file" name="avatar" id="avatar" class="form-control-file">
                </div>
                <button type="submit" class="btn btn-primary">開始上傳</button>
            </form>

            <hr>
            <div class="alert alert-warning">
                💡 <strong>教學演練指引：</strong><br>
                1. 建立一個簡單的 PHP 後門，例如 <code>&lt;?php system($_GET['cmd']); ?&gt;</code>，命名為 <code>shell.php</code>。<br>
                2. 在此頁面上傳該檔案，上傳後點選上傳成功的連結。<br>
                3. 在網址列後方加入 <code>?cmd=id</code> 或是 <code>?cmd=whoami</code>，即可在伺服器上執行系統命令！
            </div>
        </div>
    </div>
</div>

</body>
</html>
