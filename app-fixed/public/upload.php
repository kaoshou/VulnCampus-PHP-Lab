<?php
require_once __DIR__ . '/../src/helpers.php';

// 修補重點：強制登入
check_auth();

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

        // 修補重點 1：檢查副檔名 (Extension Whitelist)
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // 修補重點 2：使用 MIME 類型檢測 (利用 finfo)，防止黑客偽造副檔名上傳 webshell
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime_type = $finfo->file($file_tmp);
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];

        // 修補重點 5：限制上傳檔案大小以防範 Upload DoS
        $max_size = 2 * 1024 * 1024; // 最大 2MB
        if ($_FILES['avatar']['size'] > $max_size) {
            $error = '上傳失敗：檔案大小不能超過 2MB！';
        } elseif (!in_array($file_ext, $allowed_extensions)) {
            $error = '上傳失敗：不允許的檔案格式！僅限 JPG, JPEG, PNG, GIF。';
        } elseif (!in_array($mime_type, $allowed_mimes)) {
            $error = '上傳失敗：檔案 MIME 類型不符，請勿嘗試偽造圖片檔案！';
        } else {
            // 修補重點 3：檔名隨機化 (Randomized Filename)。使用強隨機雜湊命名檔案，防止目錄遍歷與檔名注入
            $new_file_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $success = '大頭貼上傳成功！';
                $uploaded_path = '/uploads/' . $new_file_name;
            } else {
                $error = '檔案儲存失敗，請檢查權限。';
            }
        }
    } else {
        $error = '請選擇要上傳的檔案。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>上傳大頭貼 (安全版) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📤 上傳個人大頭貼 (安全過濾版)</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2">
            <?= h($success) ?><br>
            您的檔案已安全儲存至：<a href="<?= h($uploaded_path) ?>" target="_blank"><code><?= h($uploaded_path) ?></code></a>
        </div>
    <?php endif; ?>

    <div class="card col-md-8 mx-auto shadow-sm border-0">
        <div class="card-header bg-primary text-white font-weight-bold py-3">
            大頭貼上傳 (嚴格限制副檔名與 MIME 檢測)
        </div>
        <div class="card-body p-4">
            <p class="text-muted">上傳格式僅限 JPG, JPEG, PNG, GIF 圖片。</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group mb-3">
                    <label for="avatar" class="form-label font-weight-bold">選擇大頭貼檔案：</label>
                    <input type="file" name="avatar" id="avatar" class="form-control" accept=".jpg,.jpeg,.png,.gif" required>
                </div>
                <button type="submit" class="btn btn-primary font-weight-bold">開始上傳</button>
            </form>

            <hr>
            <div class="alert alert-success">
                🛡️ <strong>安全防禦說明：</strong><br>
                1. <strong>副檔名白名單</strong>：僅允許 <code>jpg, jpeg, png, gif</code> 副檔名。這使得任何透過 ZAP 攔截並繞過前端將檔名改回 <code>.php</code> 的嘗試，都會在後端被直接拒絕。<br>
                2. <strong>MIME 類型檢測</strong>：利用 PHP <code>finfo</code> (FILEINFO_MIME_TYPE) 讀取檔案內部二進位特徵，而不是相信 Request 標頭中的 <code>Content-Type</code>。因此即使攻擊者手動將 PHP 後門的 Content-Type 改為 <code>image/png</code> 繞過，依然會被偵測並攔截。<br>
                3. <strong>檔名隨機化</strong>：上傳後的檔名會被改為亂數（如 <code>a4f3b...png</code>），攻擊者無法猜測檔名來直接存取 Webshell。<br>
                4. <strong>執行權限限制</strong>：`/uploads` 目錄內配置有 Apache `.htaccess` 控制項，禁止解析任何 PHP 程式，確保即使有漏網之魚也無法在主機上執行命令。<br>
                5. <strong>防範 Upload DoS</strong>：後端嚴格校驗 <code>$_FILES['avatar']['size']</code> 限制檔案大小不大於 2MB，防止攻擊者上傳多個超大檔案塞爆伺服器硬碟。
            </div>
        </div>
    </div>
</div>

</body>
</html>
