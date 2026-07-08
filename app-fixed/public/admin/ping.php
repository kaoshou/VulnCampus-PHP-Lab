<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 修補重點：嚴格權限控管
check_auth(['admin']);

$output = '';
$ip = trim($_POST['ip'] ?? '');
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $ip !== '') {
    // 修補重點 2：過濾與限制輸入格式 (Input Validation)，僅允許合法的 IPv4/IPv6 或 hostname
    // 這使得攻擊者無法拼入分號 (;)、管道符 (|)、雙引號等特殊 shell 控制符，徹底阻斷命令注入漏洞
    $is_ip = filter_var($ip, FILTER_VALIDATE_IP);
    $is_domain = filter_var($ip, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);

    if ($is_ip || $is_domain) {
        // 修補重點 3：使用 escapeshellarg 處理命令參數，確保其作為單一引數傳入，防範參數注入
        $safe_ip = escapeshellarg($ip);
        
        if (stristr(PHP_OS, 'WIN')) {
            $cmd = "ping -n 1 " . $safe_ip;
        } else {
            $cmd = "ping -c 1 " . $safe_ip;
        }
        
        $output = shell_exec($cmd);
        write_audit_log($pdo, "執行系統診斷 (Ping 測試標的: $ip)");
    } else {
        $error = '輸入錯誤：不合法的 IP 位址或主機名稱格式！';
        write_audit_log($pdo, "系統診斷異常請求 (不合法 IP 輸入: $ip)");
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>系統診斷工具 - VulnCampus (安全版)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { background-color: #212529; min-height: 100vh; color: white; padding-top: 20px; }
        .sidebar a { color: #cfd2d6; display: block; padding: 12px 15px; text-decoration: none; }
        .sidebar a:hover { background-color: #343a40; color: white; }
        .content { padding: 30px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        <!-- 側邊欄 -->
        <div class="col-md-2 sidebar shadow-sm">
            <h5 class="text-center mb-4 text-primary font-weight-bold">🛡️ VulnCampus 後台</h5>
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
                <h2 class="text-dark font-weight-bold">🛠️ 系統診斷工具 (安全防護版)</h2>
                <a href="/admin/index.php" class="btn btn-secondary">回後台首頁</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= h($error) ?></div>
            <?php endif; ?>

            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-success text-white font-weight-bold py-3">安全 Ping 測試</div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label for="ip" class="form-label font-weight-bold">請輸入要測試的 IP 位址或主機名稱：</label>
                            <input type="text" name="ip" id="ip" class="form-control col-md-6" placeholder="例如: 127.0.0.1" value="<?= h($ip) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary font-weight-bold">執行 Ping 測試</button>
                    </form>
                </div>
            </div>

            <?php if ($output !== ''): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-dark text-white font-weight-bold">診斷結果輸出</div>
                    <div class="card-body bg-dark text-light p-4">
                        <pre class="text-light mb-0"><?= h($output) ?></pre>
                    </div>
                </div>
            <?php endif; ?>

            <div class="alert alert-success mt-4">
                🛡️ <strong>安全控制項說明：</strong><br>
                1. <strong>嚴格格式驗證 (Filter Validate)</strong>：利用 PHP 的 <code>filter_var</code> 機制，驗證使用者輸入必須完全符合 IPv4、IPv6 位址格式，或符合標準主機網域名稱，不接受分號 <code>;</code>、<code>&</code> 等命令拼接字元。<br>
                2. <strong>Shell 參數跳脫</strong>：使用 <code>escapeshellarg()</code> 函數處理命令參數，確保任何輸入都不會被系統 Shell 當成多條命令執行，徹底消滅命令注入漏洞。
            </div>
        </div>
    </div>
</div>

</body>
</html>
