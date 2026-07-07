<?php
require_once __DIR__ . '/../../src/helpers.php';

// 教學用弱點 1：後台頁面權限控制缺失 (Broken Access Control)
check_login();

$output = '';
$ip = $_POST['ip'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ip !== '') {
    // 教學用弱點 2：命令注入漏洞 (Command Injection)。後端未對 $ip 做任何合法性校驗 (如過濾分號或用 escapeshellarg)
    // 直接將輸入拼入 shell 命令中執行。在 Linux 下可以使用 127.0.0.1; whoami 來進行測試
    if (stristr(PHP_OS, 'WIN')) {
        // 如果在 Windows 環境下測試
        $cmd = "ping -n 1 " . $ip;
    } else {
        // 在 Docker Container (Linux) 環境下
        $cmd = "ping -c 1 " . $ip;
    }
    
    // 執行命令並捕獲輸出
    $output = shell_exec($cmd);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>系統診斷工具 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #343a40; min-height: 100vh; color: white; padding-top: 20px; }
        .sidebar a { color: #dfdfdf; display: block; padding: 10px 15px; text-decoration: none; }
        .sidebar a:hover { background-color: #495057; color: white; }
        .content { padding: 30px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- 側邊欄 -->
        <div class="col-md-2 sidebar">
            <h5 class="text-center mb-4">🛡️ VulnCampus 後台</h5>
            <a href="/admin/index.php">📊 後台首頁</a>
            <a href="/admin/export_registrations.php" target="_blank">📥 匯出報名名冊</a>
            <a href="/admin/ping.php">🛠️ 系統診斷 (Ping)</a>
            <a href="/admin/logs.php">📝 稽核日誌 (Audit Logs)</a>
            <hr class="bg-secondary">
            <a href="/index.php">🚪 返回前台</a>
        </div>

        <!-- 主要內容 -->
        <div class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                <h2>🛠️ 系統診斷工具 (命令注入測試)</h2>
                <a href="/admin/index.php" class="btn btn-secondary">回後台首頁</a>
            </div>

            <div class="card mb-4">
                <div class="card-header font-weight-bold">Ping 測試</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-group">
                            <label for="ip">請輸入要測試的 IP 位址或主機名稱：</label>
                            <input type="text" name="ip" id="ip" class="form-control col-md-6" placeholder="例如: 127.0.0.1" value="<?= sanitize($ip) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-danger">執行 Ping 測試</button>
                    </form>
                </div>
            </div>

            <?php if ($output !== ''): ?>
                <div class="card">
                    <div class="card-header bg-dark text-white font-weight-bold">診斷結果輸出</div>
                    <div class="card-body bg-dark text-light">
                        <pre class="text-light mb-0"><?= $output ?></pre>
                    </div>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning mt-4">
                💡 <strong>教學演練指引 (Command Injection)：</strong><br>
                1. 在輸入框中輸入：<code>127.0.0.1; whoami</code> (Linux 容器) 或是 <code>127.0.0.1 & whoami</code>。<br>
                2. 送出表單，您會發現在 ping 的結果下方，竟然輸出了 <code>www-data</code> (執行網頁伺服器的權限帳號)！<br>
                3. 這代表您可以透過此漏洞在主機上執行任何指令，例如 <code>127.0.0.1; cat /etc/passwd</code> 來獲取系統帳號。
            </div>
        </div>
    </div>
</div>

</body>
</html>
