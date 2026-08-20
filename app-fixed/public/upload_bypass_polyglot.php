<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

// 1. 提供相同的圖馬下載以利對照測試 (採用合規無害之教學 PoC，避免防毒軟體誤判)
if (isset($_GET['download'])) {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="poc-avatar.php"');
    
    // GIF89a 圖片二進位標頭 (1x1 透明 GIF)
    $gif_bin = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    
    // 教學用無害 PoC：僅印出伺服器資訊與測試提示，不包含 system()/eval() 等危險特徵碼
    $poc_code = "\n<?php\n"
              . "echo '<div style=\"padding:20px;background:#ffebee;color:#c62828;border-left:5px solid #d32f2f;font-family:sans-serif;\">';\n"
              . "echo '<h2>🔥 [漏洞驗證成功] 圖片木馬 (Polyglot) 已被伺服器 PHP 引擎成功解析執行！</h2>';\n"
              . "echo '<p><strong>伺服器主機資訊：</strong>' . htmlspecialchars(php_uname()) . '</p>';\n"
              . "echo '<p><strong>當前 PHP 版本：</strong>' . htmlspecialchars(PHP_VERSION) . '</p>';\n"
              . "echo '<p><strong>傳入測試訊息 (msg)：</strong>' . htmlspecialchars(\$_GET['msg'] ?? 'Hello VulnCampus') . '</p>';\n"
              . "echo '</div>';\n"
              . "?>";
    
    echo $gif_bin . $poc_code;
    exit;
}

$error = '';
$success = '';
$uploaded_path = '';

// 2. 安全防禦上傳處理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['avatar']['name'];
        $file_tmp  = $_FILES['avatar']['tmp_name'];
        
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        // 安全修補 1：檢查副檔名是否在白名單中 (只允許 jpg, jpeg, png, gif)
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        // 安全修補 2：使用 getimagesize() 確認圖片二進位特徵
        $img_info = @getimagesize($file_tmp);

        if (!in_array($file_ext, $allowed_extensions)) {
            $error = '🚫 上傳失敗：不合法的檔案副檔名！僅允許 jpg, jpeg, png, gif。';
        } elseif ($img_info === false) {
            $error = '🚫 上傳失敗：檔案內容非真實的圖片格式特徵！';
        } else {
            // 安全修補 3：將檔名進行隨機重命名，徹底消除攻擊者上傳 .php 檔的可能
            $new_file_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $success = '✓ 大頭貼上傳成功（已安全重新命名）！';
                $uploaded_path = '/uploads/' . $new_file_name;
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
    <title>第四關：圖片木馬挑戰 (安全版) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🎯 第四關：圖片木馬挑戰 (安全防禦版)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2">
            <?= h($success) ?><br>
            安全儲存至：<a href="<?= h($uploaded_path) ?>" target="_blank"><code><?= h($uploaded_path) ?></code></a>
        </div>
    <?php endif; ?>

    <div class="card col-md-8 mx-auto shadow-sm border-0 bg-white">
        <div class="card-header bg-success text-white font-weight-bold py-3">
            大頭貼上傳 (啟用嚴格安全重命名與白名單過濾)
        </div>
        <div class="card-body p-4">
            <p class="text-muted">本安全版表單會對上傳的檔案進行嚴格的副檔名白名單驗證，並在儲存時強制採用隨機命名。</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="avatar" class="form-label">選擇大頭貼檔案：</label>
                    <input type="file" name="avatar" id="avatar" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success text-white font-weight-bold">開始上傳</button>
            </form>

            <hr>
            <div class="alert alert-success">
                🛡️ <strong>安全防禦與對照說明：</strong><br>
                1. <strong>防禦手段 A (副檔名白名單)</strong>：<br>
                   系統會強制檢驗檔案的副檔名（限制為 <code>.jpg, .jpeg, .png, .gif</code>）。當您上傳 <code>poc-avatar.php</code> 時，會被立即拒絕。<br>
                2. <strong>防禦手段 B (強制隨機重命名)</strong>：<br>
                   就算檔案二進位特徵符合（MIME 合法），寫入磁碟時也會被強制變更檔名為如 <code>a1b2c3d4...gif</code> 的隨機雜湊名，使其永遠無法保留危險的 <code>.php</code> 副檔名。<br>
                3. <strong>防禦手段 C (上傳資料夾 .htaccess 保護)</strong>：<br>
                   在 <code>/uploads/</code> 目錄配置的 <code>.htaccess</code> 限制了即便有惡意 php 被寫入，Apache 也會禁止執行該腳本。
                <br><br>
                <a href="?download=1" class="btn btn-sm btn-outline-success font-weight-bold">📥 下載相同的圖馬 PoC 進行安全防禦測試</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
