<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 安全版強制進行登入驗證
check_auth();

// 1. 處理 Cookie 寫入
if (isset($_GET['cookie'])) {
    $cookie_data = $_GET['cookie'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    try {
        // 安全修補：使用 Prepared Statement 防範 SQLi
        $stmt = $pdo->prepare("INSERT INTO stolen_cookies (cookie_data, ip_address, user_agent) VALUES (?, ?, ?)");
        $stmt->execute([$cookie_data, $ip, $ua]);
    } catch (PDOException $e) {
        // 忽略
    }

    header('Content-Type: image/gif');
    echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    exit;
}

// 2. 處理清空收集箱
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    try {
        $pdo->exec("DELETE FROM stolen_cookies");
        header("Location: cookie_stealer.php?success=1");
        exit;
    } catch (PDOException $e) {
        $error = '清空失敗。';
    }
}

// 3. 撈取所有被竊取的 Cookie
$stolen = [];
try {
    $stmt = $pdo->query("SELECT * FROM stolen_cookies ORDER BY id DESC");
    $stolen = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = '無法載入 Cookie 清單。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📋 Cookie 竊取防禦展示箱 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; padding-top: 50px; }
        .text-cookie { font-family: monospace; color: #dc3545; word-break: break-all; }
    </style>
</head>
<body>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📋 Cookie 竊取防禦展示箱 (安全版)</h2>
        <div>
            <a href="?action=clear" class="btn btn-danger font-weight-bold" onclick="return confirm('確定要清空嗎？')">🗑️ 清空展示箱</a>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">已成功清空！</div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="alert alert-success">
        🛡️ <strong>安全防禦對比與說明：</strong><br>
        1. <strong>防禦原理 (HttpOnly)</strong>：在安全修正版中，我們在 <code>helpers.php</code> 中設定了 <code>session_set_cookie_params</code> 並將 <code>httponly</code> 屬性設為 <code>true</code>。<br>
        2. <strong>防禦效果</strong>：當 Session 啟用了 HttpOnly 屬性後，使用者的瀏覽器會被禁止透過 JavaScript（如 <code>document.cookie</code>）來存取此 Cookie。即使網頁不慎存有 XSS 漏洞，惡意腳本也<strong>完全無法讀取</strong>到 <code>PHPSESSID</code>。<br>
        3. <strong>測試方法</strong>：您可以嘗試在安全版中進行相同的留言板 XSS 攻擊，隨後觀察下方的列表，您會發現收集箱中只能收到空字串或是無害的其他 Cookie 欄位，極具機敏性的會話 Session ID 完全不會外洩！
    </div>

    <div class="card shadow-sm p-4 bg-white">
        <h4 class="text-success mb-3">被竊取的 Cookie 列表 (安全防護對比)</h4>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>時間</th>
                    <th>來源 IP</th>
                    <th>竊取到的 Cookie 資料</th>
                    <th>使用者瀏覽器 (User-Agent)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stolen)): ?>
                    <tr><td colspan="4" class="text-center text-muted">目前無任何記錄。</td></tr>
                <?php else: ?>
                    <?php foreach ($stolen as $row): ?>
                        <tr>
                            <td><?= h($row['created_at']) ?></td>
                            <td><code><?= h($row['ip_address']) ?></code></td>
                            <td class="text-cookie"><?= h($row['cookie_data']) ?></td>
                            <td class="small text-muted"><?= h($row['user_agent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
