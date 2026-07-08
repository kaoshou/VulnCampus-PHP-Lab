<?php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/helpers.php';

// 修補重點 1：嚴格後台權限檢查
check_auth(['admin']);

$logs = [];
$error = '';

try {
    // 撈取日誌 (修正版具備完善的日誌寫入，包含登入失敗、下載、越權等)
    $stmt = $pdo->query("SELECT l.*, u.username FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id ORDER BY l.id DESC LIMIT 100");
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Get audit logs error: " . $e->getMessage());
    $error = '無法載入系統稽核日誌。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>稽核日誌 - VulnCampus (安全版)</title>
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
                <h2 class="text-dark font-weight-bold">📝 稽核日誌 (Audit Logs)</h2>
                <a href="/admin/index.php" class="btn btn-secondary">回後台首頁</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= h($error) ?></div>
            <?php endif; ?>

            <div class="alert alert-success font-weight-bold mb-4">
                🟢 安全提示：此版本為【安全修正版】，已實作全方位的稽核與監控機制 (A09:2025 Security Logging and Alerting)。使用者的登入失敗、個資下載、越權嘗試、密碼變更等行為皆會自動寫入下方日誌。
            </div>

            <div class="card shadow-sm border-0 bg-white">
                <div class="card-header bg-dark text-white font-weight-bold">最近 100 筆系統操作紀錄</div>
                <div class="card-body p-4">
                    <?php if (empty($logs)): ?>
                        <p class="text-muted text-center py-4">目前尚無任何稽核紀錄。</p>
                    <?php else: ?>
                        <table class="table table-hover table-striped mb-0">
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
                                        <td><?= h($log['id']) ?></td>
                                        <td><?= h($log['username'] ? $log['username'] : '系統/訪客') ?></td>
                                        <td><?= h($log['action']) ?></td>
                                        <td><?= h($log['ip_address']) ?></td>
                                        <td><small class="text-muted"><?= h($log['user_agent']) ?></small></td>
                                        <td><?= h($log['created_at']) ?></td>
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
