<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$output = '';
$ip = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ip'])) {
    $ip = $_POST['ip'];
    
    // 漏洞點：直接拼接使用者輸入到系統指令中執行，未進行過濾或過濾不全
    if (stristr(php_uname("s"), "Windows")) {
        // Windows 環境下使用 ping -n 3
        // 為了支援繁體中文 Windows 伺服器，對 shell 輸出進行 BIG5 到 UTF-8 的轉碼
        $raw_output = shell_exec("ping -n 3 " . $ip . " 2>&1");
        if ($raw_output) {
            $output = mb_convert_encoding($raw_output, 'UTF-8', 'BIG5');
        }
    } else {
        // Linux 環境下使用 ping -c 3
        $output = shell_exec("ping -c 3 " . $ip . " 2>&1");
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>💻 系統命令注入漏洞 (Command Injection) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>💻 系統命令注入 (Command Injection) 演練</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 左側：工具表單與說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    ⚙️ 伺服器連線 Ping 診斷工具
                </div>
                <div class="card-body">
                    <p class="text-muted">請輸入一個 IP 地址或主機名稱，系統將在後端呼叫 <code>ping</code> 命令檢測連線狀態。</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="ip">目標 IP / 主機名稱：</label>
                            <input type="text" name="ip" id="ip" class="form-control" placeholder="例如: 127.0.0.1" value="<?= htmlspecialchars($ip) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block font-weight-bold">執行連線診斷</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 命令注入漏洞演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：後端程式將使用者的輸入直接拼接到系統 Shell 終端機命令中執行，且未對特殊字元（如 <code>&</code>, <code>|</code>, <code>;</code>）進行過濾與防護。</li>
                    <li class="mb-2"><strong>正常測試</strong>：輸入 <code>127.0.0.1</code> 並點擊執行，觀察是否有正常的 Ping 回顯輸出。</li>
                    <li class="mb-2"><strong>漏洞驗證 (Command Injection)</strong>：<br>
                        試著使用命令拼接符號執行額外的系統指令，例如：
                        <br>- Windows 環境：<code>127.0.0.1 & whoami</code>
                        <br>- Linux 環境：<code>127.0.0.1 ; whoami</code>
                    </li>
                    <li class="mb-2">觀察輸出的結果中，除了原本的 Ping 數據外，是否成功執行並印出了當前運行 PHP 伺服器的系統帳號名稱！</li>
                </ol>
            </div>
        </div>

        <!-- 右側：診斷輸出區 -->
        <div class="col-md-7">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📊 診斷結果輸出
                </div>
                <div class="card-body bg-white" style="min-height: 400px;">
                    <?php if ($output !== ''): ?>
                        <pre><code><?= htmlspecialchars($output) ?></code></pre>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <h4>等待診斷執行...</h4>
                            <p class="small">請在左方輸入目標並送出以檢視後端終端機輸出。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
