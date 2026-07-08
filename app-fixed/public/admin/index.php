<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 修補重點：嚴格的角色型權限控制 (Role-Based Access Control / RBAC)
// 僅允許角色為 admin 的使用者存取此頁面，一般學生 (student) 將會被阻擋並返回 403
check_auth(['admin']);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>管理員後台 (安全版) - VulnCampus</title>
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
                <h2 class="text-dark font-weight-bold">系統控制台 (管理員專區)</h2>
                <div class="alert alert-success mb-0 py-1 font-weight-bold">
                    當前身份：<strong><?= h($_SESSION['user']['name']) ?></strong> (管理權限已驗證)
                </div>
            </div>

            <div class="row text-center">
                <div class="col-md-4 mb-3">
                    <div class="card bg-primary text-white shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title font-weight-bold mb-3">報名名冊匯出</h5>
                            <p class="card-text flex-grow-1">下載全校學生報名活動的敏感名冊 (已進行個資遮罩處理)。</p>
                            <a href="/admin/export_registrations.php" target="_blank" class="btn btn-light text-primary mt-auto font-weight-bold">立即匯出</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-success text-white shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title font-weight-bold mb-3">系統診斷工具</h5>
                            <p class="card-text flex-grow-1">測試伺服器連線狀態 (限制輸入 IP 格式防範命令注入)。</p>
                            <a href="/admin/ping.php" class="btn btn-light text-success mt-auto font-weight-bold">進入診斷</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card bg-dark text-white shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title font-weight-bold mb-3">系統稽核日誌</h5>
                            <p class="card-text flex-grow-1">檢視使用者登入登出、個資下載、越權存取等敏感行為的完整日誌。</p>
                            <a href="/admin/logs.php" class="btn btn-light text-dark mt-auto font-weight-bold">查看日誌</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-4 border-0 shadow-sm bg-white">
                <div class="card-header bg-success text-white font-weight-bold py-3">🛡️ 安全防護成效驗證</div>
                <div class="card-body p-4">
                    <p>1. 試圖以 <code>student01</code> 帳號登入系統，並存取此後台 URL <code>http://localhost:8081/admin/index.php</code>。</p>
                    <p>2. 後端會攔截請求並回應 <code>403 Forbidden</code>，拒絕未授權的低權限學生角色進入後台。</p>
                    <p>3. 此越權存取嘗試會被詳細記錄於 <strong>稽核日誌</strong> 當中以供管理者追蹤。</p>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
