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

        // 第一關：後端不做任何安全校驗，直接儲存原始檔名
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
    <title>第一關：HTML accept 篩選繞過 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🎯 第一關：HTML accept 篩選繞過</h2>
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
            大頭貼上傳 (僅使用 HTML accept 提示)
        </div>
        <div class="card-body">
            <p class="text-muted">本網頁表單僅在 <code>&lt;input&gt;</code> 配置了 <code>accept=".jpg,.jpeg,.png,.gif"</code> 屬性。</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatar">選擇大頭貼檔案：</label>
                    <input type="file" name="avatar" id="avatar" class="form-control-file" accept=".jpg,.jpeg,.png,.gif">
                </div>
                <button type="submit" class="btn btn-primary">開始上傳</button>
            </form>

            <hr>
            <div class="alert alert-warning">
                💡 <strong>本關卡繞過演練指引：</strong><br>
                1. <strong>漏洞原理</strong>：HTML 的 <code>accept</code> 屬性僅僅是用於「提示」瀏覽器在彈出選檔視窗時，預設顯示圖片檔案，對惡意用戶完全無阻擋能力。<br>
                2. <strong>繞過方法 A (選取所有檔案)</strong>：<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;a. 點選「選擇檔案」。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;b. 在彈出的檔案選擇對話框右下角過濾條件中，手動切換成「所有檔案 (*.*)」即可看到並選取您的 <code>shell.php</code> 送出。<br>
                3. <strong>繞過方法 B (F12 修改 DOM)</strong>：<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;a. 在選擇按鈕上按右鍵「檢查」，打開開發者工具。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;b. 直接在原始碼中將 <code>accept="..."</code> 屬性刪除或改為空值，即可在選檔視窗正常看到並選擇 <code>shell.php</code> 上傳。
            </div>
        </div>
    </div>
</div>

</body>
</html>
