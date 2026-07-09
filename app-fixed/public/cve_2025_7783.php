<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

// 重置歷史紀錄
if (isset($_GET['reset'])) {
    $_SESSION['cve_fixed_history'] = [];
}

$generated_boundary = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate'])) {
        // 安全修補防護 (CVE-2025-7783 / CWE-330 修補)：使用密碼學安全隨機數生成器 (CSPRNG)
        // 透過 random_bytes 獲得 16 位元組的高熵隨機數，確保完全不可預測性
        try {
            $random_hex = bin2hex(random_bytes(16));
            $generated_boundary = "----WebKitFormBoundary" . $random_hex;
            $_SESSION['cve_fixed_history'][] = $generated_boundary;
        } catch (Throwable $e) {
            $generated_boundary = "錯誤：無法生成安全隨機數";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔒 CVE-2025-7783: 安全隨機 Boundary 生成修補 - VulnCampus</title>
    <!-- 使用 Bootstrap 5 與修正版風格對齊 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f8fafc; color: #0f172a; }
        .instructions { background-color: #e8f5e9; border-left: 5px solid #2e7d32; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-break: break-all; }
        .log-box { max-height: 200px; overflow-y: auto; background-color: #f1f5f9; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🔒 CVE-2025-7783: 安全隨機 Boundary 生成修補 (CWE-330)</h2>
        <div>
            <a href="/cve_2025_7783.php?reset=1" class="btn btn-warning font-weight-bold">重設歷史紀錄</a>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 左側：工具表單與安全防禦說明 -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    🚀 安全 Multipart Form-Data 邊界生成器 (CSPRNG 已部署)
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted">本安全版已使用 CSPRNG 技術重構，產生的 boundary 具備極高不可預測性。</p>
                    
                    <form method="POST">
                        <button type="submit" name="generate" class="btn btn-success text-white w-100 font-weight-bold py-2 mb-3">🎲 安全生成 Boundary</button>
                    </form>

                    <?php if ($generated_boundary): ?>
                        <div class="alert alert-success">
                            <h6 class="font-weight-bold">安全 Boundary (Hex 編碼)：</h6>
                            <code class="h6 font-weight-bold text-success"><?= htmlspecialchars($generated_boundary) ?></code>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h6>📜 歷史 Boundary 產生紀錄：</h6>
                        <div class="log-box small">
                            <?php if (empty($_SESSION['cve_fixed_history'])): ?>
                                <span class="text-muted">尚無產生紀錄</span>
                            <?php else: ?>
                                <?php foreach ($_SESSION['cve_fixed_history'] as $idx => $b): ?>
                                    <div>[第 <?= $idx + 1 ?> 次]: <code><?= htmlspecialchars($b) ?></code></div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ CVE-2025-7783 安全修補與防禦說明</h5>
                <div class="text-muted small">
                    <p class="border-left border-success pl-2 py-1 bg-white p-2 rounded mb-3">
                        <strong>💡 科普：什麼是 Boundary（邊界分隔符）？</strong><br>
                        當我們在網頁填寫表單，並同時上傳檔案時，瀏覽器會把文字欄位和圖片二進位數據包裝成同一個 HTTP 請求發給伺服器。
                        為了讓後端伺服器知道「哪裡是名字的結束，哪裡是檔案的開始」，瀏覽器會隨機產生一個叫做 <strong><code>Boundary</code></strong> 的特殊字串，像「隔板」一樣插在欄位之間。
                    </p>

                    <p><strong>💡 防範隨機數預測漏洞 (CWE-330 / CVE-2025-7783)</strong>：<br>
                        本安全修正版已徹底修復了隨機 Boundary 預測的安全漏洞。
                        <br><br>
                        <strong>1. 徹底棄用密碼學不安全的偽隨機數生成器 (PRNG)</strong>：<br>
                        在涉及系統安全邊界 (Boundary)、Session 憑證、密碼重設 Token、API 密鑰等任何安全敏感的應用中，切勿使用基於代數遞迴公式的 PRNG（如 JavaScript 的 <code>Math.random()</code>、PHP 的 <code>rand()</code>/<code>mt_rand()</code>、或 <code>uniqid()</code>）。一旦攻擊者觀測到部分輸出，即可逆向推算出隨機序列，進而執行 HTTP 參數污染 (HPP) 或繞過身分校驗。
                        <br><br>
                        <strong>2. 採用密碼學安全隨機數生成器 (CSPRNG)</strong>：<br>
                        安全版改用 PHP 內建的 <code>random_bytes(16)</code> 生成高熵隨機字串。CSPRNG 藉由底層作業系統收集物理熵源（例如硬體中斷與 CPU 雜訊），無法被任何數學公式逆向預測，從根本上確保了 Boundary 分隔符的保密性與不可預測性。
                    </p>
                </div>
            </div>
        </div>

        <!-- 右側：預測器失效實驗與代碼修補對照 -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    🧮 預測器失效驗證
                </div>
                <div class="card-body bg-white p-4">
                    <p class="text-muted small">現在您可以嘗試將安全版的 Boundary 內容複製進去計算，由於 CSPRNG 產生的數值屬於高熵值且無數學公式規律，任何基於 LCG 或常規代數公式的預測模型皆會<strong>宣告完全失效</strong>。</p>
                    
                    <div class="form-group mb-3">
                        <label for="current_val" class="form-label font-weight-bold">請輸入觀測到的 Hex 部分（如 7f6a8b...）：</label>
                        <input type="text" id="current_val" class="form-control" placeholder="例如: f1e2d3c4b5a6">
                    </div>
                    <button type="button" id="predict_btn" class="btn btn-secondary w-100 font-weight-bold">🔮 嘗試預測 (將失敗)</button>

                    <div class="alert alert-secondary py-2 mt-3 mb-0 text-center">
                        <span class="small font-weight-bold text-muted">⚠️ 隨機熵源來自系統底層物理訊號，預測機率趨近於 0。</span>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全 Boundary 生成代碼 (CVE-2025-7783 修補)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (偽隨機數 LCG 算法)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>$next_seed = ($a * $current_seed + $c) % $m;
$boundary = "----WebKitFormBoundary" . $next_seed;</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (採用強隨機 CSPRNG 函式)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>// 使用 random_bytes 生成不可預測的 128 位元 (16-byte) 隨機序列
$random_hex = bin2hex(random_bytes(16));
$boundary = "----WebKitFormBoundary" . $random_hex;</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('predict_btn').addEventListener('click', function() {
        alert('預測失敗！密碼學安全隨機數 (CSPRNG) 無法使用代數公式進行逆向推導或預測。');
    });
</script>
</body>
</html>
