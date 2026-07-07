<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 教學用弱點 1：後台頁面權限控制缺失 (Broken Access Control)
check_login();

$logs = [];
$error = '';

try {
    // 撈取日誌 (但弱點版所有操作根本不會呼叫日誌寫入函數，此處為空或僅有系統初始日誌)
    $stmt = $pdo->query("SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>稽核日誌 - VulnCampus</title>
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
                <h2>📝 稽核日誌 (Audit Logs)</h2>
                <a href="/admin/index.php" class="btn btn-secondary">回後台首頁</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="alert alert-danger font-weight-bold">
                ⚠️ 注意：此版本為【弱點版網站】，因未實作稽核與監控機制 (A09:2025 Security Logging Failures)，使用者在系統上的登入失敗、匯出個資、命令執行等異常行為皆「完全不會」記錄在此日誌中！
            </div>

            <div class="card shadow-sm">
                <div class="card-header font-weight-bold">系統操作紀錄</div>
                <div class="card-body">
                    <?php if (empty($logs)): ?>
                        <p class="text-muted text-center py-4">（目前尚無任何稽核紀錄，系統日誌功能處於失效狀態）</p>
                    <?php else: ?>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>編號</th>
                                    <th>使用者</th>
                                    <th>執行操作</th>
                                    <th>IP 位址</th>
                                    <th>瀏覽器 UA</th>
                                    <th>時間</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= $log['id'] ?></td>
                                        <td><?= $log['username'] ? $log['username'] : '系統/訪客' ?></td>
                                        <td><?= $log['action'] ?></td>
                                        <td><?= $log['ip_address'] ?></td>
                                        <td><small><?= $log['user_agent'] ?></small></td>
                                        <td><?= $log['created_at'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
