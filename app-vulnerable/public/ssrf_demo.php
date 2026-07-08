<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$error = '';
$preview_content = '';
$preview_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preview_url = $_POST['url'] ?? '';
    
    if (empty($preview_url)) {
        $error = '網址不能為空！';
    } else {
        // 漏洞點：SSRF (Server-Side Request Forgery)
        // 伺服器對使用者傳入的 URL 沒有做任何域名、IP 段白名單限制，直接使用 file_get_contents 發起 HTTP 請求
        // 攻擊者可以輸入 http://127.0.0.1:8080/admin/index.php 或 http://db:3306 探測內網服務
        try {
            // 設定超時防止請求 hang 住
            $context = stream_context_create([
                'http' => ['timeout' => 3.0]
            ]);
            $response = @file_get_contents($preview_url, false, $context);
            if ($response === false) {
                // 將詳細連線失敗錯誤拋出，更利於進行內網 Port 探測 (Error-based SSRF)
                $error = '無法獲取該 URL 的內容，錯誤詳情：' . error_get_last()['message'];
            } else {
                $preview_content = $response;
            }
        } catch (Exception $e) {
            $error = '讀取 URL 時出錯：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🌐 SSRF 伺服器端請求偽造演練 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🌐 SSRF 伺服器端請求偽造 (Server-Side Request Forgery) 專屬演練</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 輸入與測試 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white font-weight-bold">
                    🖼️ 遠端活動海報 / 圖片預覽器
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        本功能允許輸入外部活動圖片 URL（例如其他學校或機構的海報 URL），由本伺服器代為下載並展示。
                    </p>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="url" class="font-weight-bold">請輸入圖片網址 (URL)：</label>
                            <input type="text" name="url" id="url" class="form-control" placeholder="http://example.com/poster.jpg" required value="<?= htmlspecialchars($preview_url) ?>">
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">🚀 下載並預覽</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>教學演練指引：</strong><br>
                        1. <strong>讀取本地敏感檔案</strong>：試著輸入 <code>file:///etc/passwd</code> (Linux 環境下) 或是網頁目錄檔案 <code>file:///var/www/html/src/db.php</code>，點擊下載，觀察是否能直接竊取伺服器內部代碼與敏感憑證。<br>
                        2. <strong>探測內網與 Port</strong>：輸入 <code>http://127.0.0.1:8080/admin/index.php</code> (管理員後台，原本一般帳號點擊會被跳轉，但經由伺服器本地存取將直接回傳後台 HTML 面板)；或輸入 <code>http://db:3306</code> (探測 MySQL 埠口)，觀察錯誤回顯訊息是否成功偵測到內網服務存活。
                    </div>
                </div>
            </div>
        </div>

        <!-- 結果展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📄 預覽結果輸出
                </div>
                <div class="card-body bg-light" style="max-height: 500px; overflow-y: auto;">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($preview_content): ?>
                        <div class="alert alert-success">成功下載內容！長度：<?= strlen($preview_content) ?> bytes</div>
                        <pre class="p-2 bg-white border rounded"><code><?= htmlspecialchars($preview_content) ?></code></pre>
                    <?php else: ?>
                        <p class="text-center text-muted">目前尚無預覽內容</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
