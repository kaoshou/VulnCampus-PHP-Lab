<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$error = '';
$preview_content = '';
$preview_url = '';

/**
 * 安全校驗 URL，防範 SSRF (Server-Side Request Forgery)
 */
function is_safe_url($url) {
    $parts = parse_url($url);
    if ($parts === false || !isset($parts['scheme']) || !isset($parts['host'])) {
        return false;
    }

    // 1. 限制只允許 http 及 https 協定（禁止 file://, gopher:// 等）
    $scheme = strtolower($parts['scheme']);
    if (!in_array($scheme, ['http', 'https'])) {
        return false;
    }

    $host = strtolower($parts['host']);
    
    // 2. 禁止本地迴路及 Docker 內部服務主機名
    if ($host === 'localhost' || $host === '127.0.0.1' || $host === 'db' || $host === 'app-vulnerable' || $host === 'app-fixed') {
        return false;
    }

    // 3. 解析 IP，並過濾 RFC1918 私有 IP 段 (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16) 與保留段
    $ip = gethostbyname($parts['host']);
    if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }

    // filter_var 的 FILTER_FLAG_NO_PRIV_RANGE 可以過濾私有 IP，FILTER_FLAG_NO_RES_RANGE 可以過濾保留區間
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return false;
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $preview_url = $_POST['url'] ?? '';
    
    if (empty($preview_url)) {
        $error = '網址不能為空！';
    } elseif (!is_safe_url($preview_url)) {
        // 安全修補：拒絕不安全的內部 URL
        $error = '不安全的網址！僅允許存取公開的外網 HTTP/HTTPS 服務，禁止訪問私有與本地網路。';
    } else {
        try {
            $context = stream_context_create([
                'http' => ['timeout' => 3.0]
            ]);
            $response = @file_get_contents($preview_url, false, $context);
            if ($response === false) {
                // 安全修補：返回通用錯誤，避免洩漏詳細連線 Trace 與埠口探測錯誤訊息
                $error = '獲取遠端內容失敗，請確認網址是否正確。';
            } else {
                $preview_content = $response;
            }
        } catch (Exception $e) {
            $error = '獲取內容失敗。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🌐 SSRF 伺服器端請求偽造演練 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🌐 SSRF 伺服器端請求偽造 (Server-Side Request Forgery) 安全防護演練 (已修正)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 輸入與測試 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white font-weight-bold">
                    🖼️ 遠端活動海報 / 圖片預覽器 (安全防禦)
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        本功能在後端加入了嚴格的 URL 解析與 IP 過濾機制，防止發起內網請求。
                    </p>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="url" class="font-weight-bold">請輸入圖片網址 (URL)：</label>
                            <input type="text" name="url" id="url" class="form-control" placeholder="http://example.com/poster.jpg" required value="<?= h($preview_url) ?>">
                        </div>
                        <button type="submit" class="btn btn-success btn-block">🚀 下載並預覽</button>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        🛡️ <strong>安全防禦說明：</strong><br>
                        1. <strong>協定白名單</strong>：僅允許 <code>http</code> 與 <code>https</code>，拒絕 <code>file://</code>、<code>gopher://</code> 等其他協定。<br>
                        2. <strong>私有與保留 IP 過濾 (RFC1918)</strong>：解析域名後，若發現解析出的 IP 屬於私有 IP 段 (如 <code>127.0.0.1</code>、<code>172.x.x.x</code> 等) 或 Docker 內部網絡，後端將直接拒絕訪問。<br>
                        3. <strong>通用錯誤響應</strong>：當連線失敗時，隱藏詳細系統報錯，僅輸出通用連線錯誤訊息。
                    </div>
                </div>
            </div>
        </div>

        <!-- 結果展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white font-weight-bold">
                    📄 預覽結果輸出
                </div>
                <div class="card-body bg-light" style="max-height: 500px; overflow-y: auto;">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php elseif ($preview_content): ?>
                        <div class="alert alert-success">成功下載內容！長度：<?= strlen($preview_content) ?> bytes</div>
                        <pre class="p-2 bg-white border rounded"><code><?= h($preview_content) ?></code></pre>
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
