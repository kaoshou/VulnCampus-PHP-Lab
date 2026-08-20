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
            @mkdir($upload_dir, 0777, true);
        }

        // 第二關：後端不做任何安全校驗，直接儲存原始檔名
        $destination = $upload_dir . $file_name;

        if (move_uploaded_file($file_tmp, $destination)) {
            $success = '大頭貼上傳成功！';
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
    <title>第二關：前端 JS 驗證繞過 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🎯 第二關：前端 JS 驗證繞過</h2>
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
            大頭貼上傳 (配置前端 JavaScript 副檔名卡控)
        </div>
        <div class="card-body">
            <p class="text-muted">本表單配置了前端 JS，在送出表單前檢查副檔名是否為 <code>.jpg, .jpeg, .png, .gif</code>。</p>
            
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateForm()">
                <div class="form-group">
                    <label for="avatar">選擇大頭貼檔案：</label>
                    <input type="file" name="avatar" id="avatar" class="form-control-file" accept=".jpg,.jpeg,.png,.gif">
                </div>
                <button type="submit" class="btn btn-primary">開始上傳</button>
            </form>

            <script>
            function validateForm() {
                var fileInput = document.getElementById('avatar');
                var filePath = fileInput.value;
                if (!filePath) return true;
                
                // 僅允許圖片副檔名
                var allowedExtensions = /(\.jpg|\.jpeg|\.png|\.gif)$/i;
                if (!allowedExtensions.exec(filePath)) {
                    alert('🚫 [前端 JS 攔截] 僅允許上傳 JPG, JPEG, PNG, GIF 格式的大頭貼！');
                    fileInput.value = '';
                    return false;
                }
                return true;
            }
            </script>

            <hr>
            <div class="alert alert-warning">
                💡 <strong>本關卡繞過演練指引：</strong><br>
                1. <strong>漏洞原理</strong>：任何前端 JavaScript 限制都是在「使用者瀏覽器」內執行，攻擊者可以輕易停用 JS，或在表單送出後攔截封包進行修改，令前端驗證形同虛設。<br>
                2. <strong>繞過方法 A (停用 JavaScript)</strong>：在瀏覽器設定或使用外掛停用本網頁的 JS，即可直接選取並上傳 <code>shell.php</code>。<br>
                3. <strong>繞過方法 B (ZAP 攔截並修改封包)</strong>：<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;a. 先選擇一個合法的圖片檔案（如 <code>test.jpg</code>）。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;b. 開啟 ZAP 的中斷點攔截（Breakpoint）。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;c. 點選「開始上傳」，在 ZAP 攔截到 POST 請求時，將該段參數的 <code>filename="test.jpg"</code> 修改為 <code>filename="shell.php"</code> 後放行送出，即可成功繞過前端 JS 卡控！
            </div>
        </div>
    </div>
</div>

</body>
</html>
