<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$url_input = $_POST['url'] ?? '';
$headers_output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $url_input !== '') {
    // 模擬後端對跳轉網址進行 URL 解碼
    $decoded_url = urldecode($url_input);
    
    // 漏洞點 (CWE-93)：未對解碼後的 URL 進行換行字元 (\r\n) 的過濾，直接拼接進 HTTP Response Headers 中
    $headers_packet = "HTTP/1.1 302 Found\r\n";
    $headers_packet .= "Location: " . $decoded_url . "\r\n";
    $headers_packet .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers_packet .= "Connection: close\r\n\r\n";
    
    // 模擬返回的 HTML Body
    $headers_packet .= "<html><body><p>Redirecting to <a href=\"" . htmlspecialchars($decoded_url) . "\">here</a>...</p></body></html>";
    
    $headers_output = $headers_packet;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🌐 CRLF 注入漏洞 (CWE-93) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🌐 HTTP 響應分裂 / CRLF 注入漏洞 (CWE-93) 演練</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 左側：工具表單與說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    🔗 跳轉 URL 診斷工具
                </div>
                <div class="card-body">
                    <p class="text-muted">輸入一個跳轉網址，系統將模擬生成對應的 HTTP Response Header 數據包。</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="url">目標跳轉網址：</label>
                            <input type="text" name="url" id="url" class="form-control" placeholder="例如: http://localhost/index.php" value="<?= htmlspecialchars($url_input) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block font-weight-bold">發送跳轉請求</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 CRLF 注入演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：後端程式在設置 HTTP 響應標頭（如跳轉 Location）時，未將輸入中的換行字元 <code>CR (\r, %0d)</code> 和 <code>LF (\n, %0a)</code> 進行過濾，導致攻擊者能藉此閉合當前 Header 並「注入」全新的自訂 Header，甚至偽造整個 HTTP Body。</li>
                    <li class="mb-2"><strong>正常測試</strong>：<br>
                        輸入 <code>http://google.com</code> 並送出，觀察右側輸出，<code>Location: http://google.com</code> 正常成行。
                    </li>
                    <li class="mb-2"><strong>漏洞驗證 (CRLF Injection)</strong>：<br>
                        試著輸入包含 URL 編碼 CRLF 字元的注入內容：
                        <br><code>http://google.com%0d%0aSet-Cookie: dummy_session=hacker_fix_12345</code>
                        <br>並送出查詢。
                    </li>
                    <li class="mb-2">觀察右側的診斷結果中，<strong>是否成功多出了一行 <code>Set-Cookie: dummy_session=hacker_fix_12345</code> 的響應標頭？</strong> 這說明攻擊者藉由 CRLF 注入，已能在造訪者瀏覽器中強植 Cookie！</li>
                </ol>
            </div>
        </div>

        <!-- 右側：診斷輸出區 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📊 模擬 HTTP Response 數據包
                </div>
                <div class="card-body bg-white" style="min-height: 350px;">
                    <?php if ($headers_output !== ''): ?>
                        <pre><code><?= htmlspecialchars($headers_output) ?></code></pre>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <h4>等待診斷執行...</h4>
                            <p class="small">請在左方輸入目標並送出以檢視生成的 HTTP 數據包。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white font-weight-bold">
                    📝 脆弱跳轉 Header 拼接代碼
                </div>
                <div class="card-body bg-light">
                    <pre style="background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px;" class="small"><code>// 直接拼接解碼後的輸入，未過濾 CRLF (\r\n) 字元
$decoded_url = urldecode($url_input);
$headers_packet = "HTTP/1.1 302 Found\r\n";
$headers_packet .= "Location: " . $decoded_url . "\r\n";</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
