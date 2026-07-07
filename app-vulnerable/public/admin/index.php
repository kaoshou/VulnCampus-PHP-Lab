<?php
require_once __DIR__ . '/../../src/helpers.php';

// 教學用弱點 1：後台首頁權限控制缺失 (Broken Access Control)
// 這裡後端完全沒有檢查 $_SESSION['user']['role'] 是否為 admin
// 只要一般學生帳號 (例如 student01) 登入後，在網址上手動輸入 /admin/ 即可成功跨越權限進入管理後台！
check_login(); 
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>管理員後台 - VulnCampus</title>
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
                <h2>系統控制台 (管理員專區)</h2>
                <div class="alert alert-danger mb-0 py-1">
                    當前身份：<strong><?= $_SESSION['user']['name'] ?></strong> (角色: <?= $_SESSION['user']['role'] ?>)
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5 class="card-title">報名名冊匯出</h5>
                            <p class="card-text">下載全校學生報名活動的敏感名冊 (CSV 格式)，包含個資。</p>
                            <a href="/admin/export_registrations.php" target="_blank" class="btn btn-light text-info">立即匯出</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5 class="card-title">系統診斷工具</h5>
                            <p class="card-text">測試伺服器與資料庫或外部主機的連線狀態 (Ping 測試)。</p>
                            <a href="/admin/ping.php" class="btn btn-light text-success">進入診斷</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h5 class="card-title">系統稽核日誌</h5>
                            <p class="card-text">檢視系統敏感操作紀錄 (弱點版此處無寫入防護紀錄)。</p>
                            <a href="/admin/logs.php" class="btn btn-light text-secondary">查看日誌</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4 border-danger">
                <div class="card-header bg-danger text-white font-weight-bold">💡 權限控制缺失 (Broken Access Control) 演練說明</div>
                <div class="card-body">
                    <p>1. 請先使用 <code>student01</code> 帳號登入系統。</p>
                    <p>2. 在瀏覽器網址列直接輸入：<code>http://localhost:8080/admin/index.php</code></p>
                    <p>3. 您會發現您不需要管理員帳密，就可以輕鬆跨越權限，存取管理後台的敏感功能！</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
