<?php
// 教學用弱點：未過濾的跳轉 (Open Redirect)。後端直接讀取 GET 參數中的 url 並跳轉
// 攻擊者可以利用此功能將 url 修改為任意外部釣魚網站，例如 redirect.php?url=https://www.google.com
// 藉此偽裝成學校官方網域發送連結，誘騙使用者登入假網站

$url = $_GET['url'] ?? '/index.php';

// 教學展示：在跳轉前在畫面上停留 2 秒，方便學員看清跳轉的 URL
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>正在跳轉... (Open Redirect 測試)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <meta http-equiv="refresh" content="2;url=<?= $url ?>">
</head>
<body class="bg-light text-center" style="padding-top: 100px;">
    <div class="container col-md-6">
        <div class="card p-5 shadow-sm">
            <h3 class="text-warning mb-4">🔗 系統正在引導跳轉</h3>
            <p>您即將離開當前頁面，正在前往以下目標位置：</p>
            <div class="alert alert-secondary font-weight-bold my-3 text-break">
                <?= $url ?>
            </div>
            <p class="text-muted">若系統在 2 秒內未自動跳轉，請點擊下方連結：</p>
            <a href="<?= $url ?>" class="btn btn-primary">手動前往</a>
        </div>
        
        <div class="alert alert-danger mt-4 text-left">
            💡 <strong>教學演示指引：</strong><br>
            1. 觀察目前的 URL：<code>redirect.php?url=courses.php</code>。<br>
            2. 嘗試將 <code>url</code> 參數修改為外部網址，例如 <code>https://www.google.com</code>。<br>
            3. 重新載入，您會發現本站竟然毫無防護地將使用者「直接導向」到外部網站！這在釣魚攻擊中常被用來偽裝連結。
        </div>
    </div>
</body>
</html>
