<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

// 初始化或重置隨機 Seed
if (!isset($_SESSION['cve_seed']) || isset($_GET['reset'])) {
    $_SESSION['cve_seed'] = mt_rand(100000, 999999);
    $_SESSION['history'] = [];
}

$generated_boundary = '';

// LCG 參數
$a = 1103515245;
$c = 12345;
$m = 2147483648;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generate'])) {
        // 漏洞點 (CVE-2025-7783 / CWE-330)：使用可預測的偽隨機數生成器 (LCG PRNG) 來產生安全邊界 Boundary
        $current_seed = $_SESSION['cve_seed'];
        $next_seed = ($a * $current_seed + $c) % $m;
        $_SESSION['cve_seed'] = $next_seed;
        
        $generated_boundary = "----WebKitFormBoundary" . $next_seed;
        $_SESSION['history'][] = $generated_boundary;
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔒 CVE-2025-7783: 弱隨機 Boundary 生成漏洞 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-break: break-all; }
        .log-box { max-height: 200px; overflow-y: auto; background-color: #e9ecef; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🔒 CVE-2025-7783: 弱隨機 Boundary 生成漏洞演練 (CWE-330)</h2>
        <div>
            <a href="/cve_2025_7783.php?reset=1" class="btn btn-warning font-weight-bold">重設亂數種子</a>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 左側：工具表單與演練指引 -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    🚀 Multipart Form-Data 邊界生成器
                </div>
                <div class="card-body">
                    <p class="text-muted">本模組模擬前端/後端套件在發送 Multipart 請求時，生成隔離內容的 <code>boundary</code> 邊界字串。</p>
                    
                    <form method="POST">
                        <button type="submit" name="generate" class="btn btn-danger btn-block font-weight-bold py-2 mb-3">🎲 生成新 Boundary</button>
                    </form>

                    <?php if ($generated_boundary): ?>
                        <div class="alert alert-info">
                            <h6 class="font-weight-bold">最新產生的 Boundary：</h6>
                            <code class="h5 text-danger font-weight-bold"><?= htmlspecialchars($generated_boundary) ?></code>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <h6>📜 歷史 Boundary 產生紀錄 (最新在最下面)：</h6>
                        <div class="log-box small">
                            <?php if (empty($_SESSION['history'])): ?>
                                <span class="text-muted">尚無產生紀錄</span>
                            <?php else: ?>
                                <?php foreach ($_SESSION['history'] as $idx => $b): ?>
                                    <div class="border-bottom py-1">
                                        <strong>[#<?= $idx + 1 ?>]</strong> 
                                        <code><?= htmlspecialchars($b) ?></code>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 科普說明卡片 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white font-weight-bold">
                    💡 科普：什麼是 Boundary（邊界分隔符）？
                </div>
                <div class="card-body small text-secondary">
                    <p class="mb-3">
                        <strong>📦 【生活化比喻】快遞箱裡的「塑膠隔板」</strong><br>
                        想像你寄一個快遞大箱子給伺服器，裡面塞了<strong>「收件人姓名（文字）」</strong> and <strong>「大頭貼（圖片檔）」</strong>。<br>
                        如果箱子裡沒有放任何隔板，伺服器打開時根本分不清「姓名到哪裡結束」和「圖片二進位資料從哪裡開始」。<br>
                        為了解決這個問題，瀏覽器會隨機產生一個叫做 <strong><code>Boundary</code></strong> 的特殊字串，像<strong>「隔板」</strong>一樣插在每個欄位之間。伺服器只要找到這串隔板字串，就能像切蛋糕一樣，把姓名和圖片整整齊齊地切開拿出來。
                    </p>
                    
                    <strong>📋 HTTP 請求示意圖（用隔板分開欄位）：</strong>
                    <div class="bg-dark text-light p-3 rounded mt-2" style="font-family: monospace; font-size: 0.8rem; line-height: 1.4; overflow-x: auto;">
                        <span class="text-secondary">[整個 HTTP 請求箱子]</span><br>
                        POST /upload.php HTTP/1.1<br>
                        Content-Type: multipart/form-data; boundary=<span class="text-warning">----WebKitFormBoundary7MA4</span><br>
                        <br>
                        <span class="text-warning">------WebKitFormBoundary7MA4</span> <span class="text-muted">(隔板 A 開始)</span><br>
                        Content-Disposition: form-data; name="username"<br>
                        <br>
                        張小明 <span class="text-success">(欄位1：文字內容)</span><br>
                        <br>
                        <span class="text-warning">------WebKitFormBoundary7MA4</span> <span class="text-muted">(隔板 B 開始)</span><br>
                        Content-Disposition: form-data; name="avatar"; filename="me.png"<br>
                        Content-Type: image/png<br>
                        <br>
                        [圖片的二進位資料亂碼...] <span class="text-success">(欄位2：檔案內容)</span><br>
                        <br>
                        <span class="text-warning">------WebKitFormBoundary7MA4--</span> <span class="text-muted">(雙橫線代表箱子結束)</span>
                    </div>
                </div>
            </div>

            <!-- 漏洞原理卡片 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    🎯 CVE-2025-7783 漏洞原理：可預測的隔板？
                </div>
                <div class="card-body small text-secondary">
                    <p>
                        <strong>⚠️ 致命問題：你的隨機隔板名稱是可以被猜到的！</strong><br>
                        JavaScript 常用的 <code>form-data</code> 套件在生成這個隔板字串（Boundary）時，使用了密碼學不安全的偽隨機數生成器（PRNG，如 <code>Math.random()</code>）。<br>
                        本演練模擬了類似的 <strong>線性同餘法 (LCG)</strong> 演算法。這種演算法的特性是：<strong>只要你知道了先前產生的隨機數，你就能 100% 精準算出來下一次會產生什麼數字！</strong>
                    </p>
                    <p class="mb-0">
                        <strong>🔥 攻擊者能做什麼？</strong><br>
                        如果攻擊者能預測下一個隔板字串，他們就可以在輸入框（例如 username）裡偷偷填入偽造的隔板和惡意指令。後端解析器在拆箱子時，就會被欺騙，誤以為這是另一個獨立的請求或檔案，這被稱為 <strong>HTTP 請求走私 (Request Smuggling)</strong> 或 <strong>HTTP 參數污染 (HPP)</strong>，可以繞過防火牆 (WAF) 甚至偽造其他人的請求！
                    </p>
                </div>
            </div>
        </div>

        <!-- 右側：攻擊者預測計算器與代碼審查 -->
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white font-weight-bold">
                    🧮 攻擊者隨機數預測計算器
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="current_val">請輸入最後一次觀測到的 Boundary 數字部分：</label>
                        <input type="number" id="current_val" class="form-control" placeholder="例如: 87654321">
                    </div>
                    <button type="button" id="predict_btn" class="btn btn-warning btn-block font-weight-bold text-dark">🔮 計算並預測下一個 Boundary</button>

                    <div class="mt-3 d-none" id="prediction_result_box">
                        <div class="alert alert-warning py-2 mb-0">
                            <strong>🔮 預測下一次產生的 Boundary 將會是：</strong><br>
                            <code class="h6 font-weight-bold text-dark" id="prediction_text"></code>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 實驗步驟卡片 -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white font-weight-bold">
                    🔬 預測驗證實驗步驟
                </div>
                <div class="card-body small text-secondary">
                    <ol class="pl-3 mb-0">
                        <li class="mb-2">點擊左上角的「🎲 生成新 Boundary」按鈕至少兩次。</li>
                        <li class="mb-2">從歷史紀錄中，複製最後一次產生的數字部分（例如：<code>----WebKitFormBoundary<b>87654321</b></code> 中的 <code>87654321</code>）。</li>
                        <li class="mb-2">將該數字貼入右上角的「攻擊者隨機數預測計算器」並點擊計算。</li>
                        <li class="mb-2">此時計算器會預測下一個產生的 Boundary 字串。</li>
                        <li class="mb-2">接著，請再次點擊左邊的「🎲 生成新 Boundary」按鈕。</li>
                        <li class="mb-2"><strong>驗證結果：</strong>比對新產生的 Boundary 字串是否與右邊預測的一模一樣？你會發現完全一致！</li>
                    </ol>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white font-weight-bold">
                    📝 脆弱偽隨機數代碼
                </div>
                <div class="card-body bg-light">
                    <pre style="background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px;" class="small"><code>// 使用密碼學不安全的偽隨機算法 (LCG PRNG)，種子一旦外洩，後續所有隨機數皆可被推算
$a = 1103515245;
$c = 12345;
$m = 2147483648;
$next_seed = ($a * $current_seed + $c) % $m;
$boundary = "----WebKitFormBoundary" . $next_seed;</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('predict_btn').addEventListener('click', function() {
        const currentValStr = document.getElementById('current_val').value;
        if (!currentValStr) {
            alert('請先輸入一組數字！');
            return;
        }
        
        try {
            // 使用 BigInt 避免 JavaScript 的安全整數溢出問題 (Number.MAX_SAFE_INTEGER)
            const currentVal = BigInt(currentValStr);
            
            // 模擬後端 LCG 預測邏輯
            const a = 1103515245n;
            const c = 12345n;
            const m = 2147483648n;
            
            const nextSeed = (a * currentVal + c) % m;
            
            document.getElementById('prediction_text').innerText = "----WebKitFormBoundary" + nextSeed.toString();
            document.getElementById('prediction_result_box').classList.remove('d-none');
        } catch (e) {
            alert('請輸入有效的整數！');
        }
    });
</script>
</body>
</html>
