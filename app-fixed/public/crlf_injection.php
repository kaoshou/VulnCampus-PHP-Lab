<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$url_input = $_POST['url'] ?? '';
$headers_output = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $url_input !== '') {
    $decoded_url = urldecode($url_input);
    
    // 安全修補防禦 (CWE-93)：徹底清除輸入中的換行字元 (\r, \n) 及其對應 URL 編碼，阻斷 CRLF 注入
    $safe_url = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], "", $decoded_url);
    
    $headers_packet = "HTTP/1.1 302 Found\r\n";
    $headers_packet .= "Location: " . $safe_url . "\r\n";
    $headers_packet .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers_packet .= "Connection: close\r\n\r\n";
    
    $headers_packet .= "<html><body><p>Redirecting to <a href=\"" . htmlspecialchars($safe_url) . "\">here</a>...</p></body></html>";
    
    $headers_output = $headers_packet;
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🌐 CRLF 注入修補 (CWE-93) - VulnCampus</title>
    <!-- 使用 Bootstrap 5 與修正版風格對齊 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f8fafc; color: #0f172a; }
        .instructions { background-color: #e8f5e9; border-left: 5px solid #2e7d32; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🌐 CRLF 注入漏洞安全修補 (CWE-93)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 左側：工具表單與安全防禦說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    🛡️ 跳轉 URL 診斷工具 (已安全防禦)
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted">本安全版已清除輸入中的任何 <code>\r\n</code> 換行字元，以防止 HTTP 響應分裂與 Header 注入風險。</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="url" class="form-label font-weight-bold">目標跳轉網址：</label>
                            <input type="text" name="url" id="url" class="form-control" placeholder="例如: http://localhost/index.php" value="<?= htmlspecialchars($url_input) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success text-white w-100 font-weight-bold">發送跳轉請求</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全修補對照說明</h5>
                <p class="text-muted small">
                    <strong>如何徹底防範 CRLF 注入與響應分裂 (CWE-93)？</strong>
                    <br><br>
                    <strong>1. 徹底清除換行字元 (Sanitization)</strong>：
                    在任何使用者輸入被寫入 HTTP 響應標頭 (如 <code>header()</code> 函數) 前，必須利用字串替換或正則表達式，將 <code>\r</code>、<code>\n</code> 及其 URL 編碼 <code>%0d</code>, <code>%0a</code> 完全過濾或移除。
                    <br><br>
                    <strong>2. 依靠現代運行環境的安全保護</strong>：
                    現代的 PHP 版本 (5.1.2 起) 內部已有對 <code>header()</code> 的原生安全防禦。如果發現標頭中包含多行或換行符，會拒絕執行並拋出警告。但自行在代碼中進行嚴格的過濾 (如本例) 仍是最佳安全實踐，特別是在自訂協議或生成日誌 (防範 CWE-117 日誌注入) 的情況下。
                </p>
            </div>
        </div>

        <!-- 右側：診斷輸出與代碼修補對照 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📊 模擬 HTTP Response 數據包
                </div>
                <div class="card-body bg-white p-4" style="min-height: 350px;">
                    <?php if ($headers_output !== ''): ?>
                        <pre><code><?= htmlspecialchars($headers_output) ?></code></pre>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <h4>等待診斷執行...</h4>
                            <p class="small">請在左方輸入並送出，此處將顯示生成的安全 HTTP 數據包。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全跳轉標頭代碼 (CWE-93 修補)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (直接拼接變數)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>$headers_packet = "Location: " . $decoded_url . "\r\n";</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (安全過濾移除 \r\n 防止分裂注入)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>// 徹底過濾任何可能破壞 HTTP Header 結構的換行控制字元
$safe_url = str_replace(["\r", "\n", "%0d", "%0a", "%0D", "%0A"], "", $decoded_url);
$headers_packet = "Location: " . $safe_url . "\r\n";</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
