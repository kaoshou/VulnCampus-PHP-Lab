<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$output = '';
$ip = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ip = trim($_POST['ip']);
    
    // 安全修補防護：驗證輸入必須為合法的 IP 地址或主機名稱格式，阻絕一切 command 拼接特殊字元
    $is_ip = filter_var($ip, FILTER_VALIDATE_IP);
    $is_hostname = filter_var($ip, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    
    if ($is_ip || $is_hostname) {
        // 使用 escapeshellarg 對參數進行終端機安全轉義
        $safe_ip = escapeshellarg($ip);
        
        if (stristr(php_uname("s"), "Windows")) {
            $raw_output = shell_exec("ping -n 3 " . $safe_ip);
            if ($raw_output) {
                $output = mb_convert_encoding($raw_output, 'UTF-8', 'BIG5');
            }
        } else {
            $output = shell_exec("ping -c 3 " . $safe_ip);
        }
    } else {
        $error = "❌ 安全警告：輸入包含非法字元或格式不正確！必須為合法的 IP 地址或主機名稱。";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>💻 系統命令注入修補 (Command Injection) - VulnCampus</title>
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
        <h2 class="text-primary">💻 系統命令注入安全修補 (Command Injection)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold py-3 mb-4"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與安全防禦說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    ⚙️ 伺服器連線 Ping 診斷工具 (已進行安全防護)
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted">本安全版引進了嚴格的 IP 格式過濾與 escapeshellarg 轉義，阻絕所有注入攻擊。</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="ip" class="form-label font-weight-bold">目標 IP / 主機名稱：</label>
                            <input type="text" name="ip" id="ip" class="form-control" placeholder="例如: 127.0.0.1" value="<?= htmlspecialchars($ip) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success text-white w-100 font-weight-bold">執行連線診斷</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全修補對照說明</h5>
                <p class="text-muted small">
                    <strong>如何防範系統命令注入 (Command Injection)？</strong>
                    <br><br>
                    <strong>1. 避免使用 Shell 執行外部指令</strong>：
                    在可能的情況下，應儘量使用伺服器端程式語言自帶的 API（例如使用 PHP 的 <code>fsockopen()</code> 或 <code>socket_create()</code> 進行網路連線測試，而非直接呼叫系統的 <code>ping</code> 指令）。
                    <br><br>
                    <strong>2. 強制進行嚴格的格式白名單校驗</strong>：
                    如果是輸入 IP 或是 Domain，可使用 PHP 內建安全過濾器 <code>filter_var($ip, FILTER_VALIDATE_IP)</code>。這可以確保傳入的變數百分之百是一組標準的 IP 地址，完全不含任何命令連接符。
                    <br><br>
                    <strong>3. 參數轉義防護</strong>：
                    在拼接入 Shell 前，強制使用 <code>escapeshellarg()</code> 對變數進行封裝與轉義，將其包裹在單引號內並對內部的引號進行處理，防止逃逸。
                </p>
            </div>
        </div>

        <!-- 右側：診斷輸出與代碼修補對照 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📊 診斷結果輸出
                </div>
                <div class="card-body bg-white p-4" style="min-height: 250px;">
                    <?php if ($output !== ''): ?>
                        <pre><code><?= htmlspecialchars($output) ?></code></pre>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <h4>等待診斷執行...</h4>
                            <p class="small">請在左方輸入並送出，此處將顯示經過防禦檢查後的安全診斷結果。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全命令執行代碼 (Command Injection 修補)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (直接字串拼接)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>$output = shell_exec("ping -c 3 " . $ip);</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (安全校驗與參數轉義)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>$is_ip = filter_var($ip, FILTER_VALIDATE_IP);
$is_hostname = filter_var($ip, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

if ($is_ip || $is_hostname) {
    // escapeshellarg 確保變數被正確包覆，無法跳出參數邊界
    $safe_ip = escapeshellarg($ip);
    $output = shell_exec("ping -c 3 " . $safe_ip);
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
