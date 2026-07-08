<?php
require_once __DIR__ . '/../src/helpers.php';

$url = $_GET['url'] ?? '/index.php';

// 修補重點：對跳轉的目標進行安全過濾與比對
$is_safe = true;

// 檢查是否為外部網域跳轉
if (preg_match('/^(https?:)?\/\//i', $url)) {
    $host = parse_url($url, PHP_URL_HOST);
    if ($host !== 'localhost' && $host !== $_SERVER['HTTP_HOST']) {
        // 如果是外部網域且非本機，判定為不安全跳轉，強制重導向回首頁
        $is_safe = false;
        $url = '/index.php';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>正在跳轉... (安全版)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <meta http-equiv="refresh" content="2;url=<?= h($url) ?>">
</head>
<body class="bg-light text-center" style="padding-top: 100px;">
    <div class="container col-md-6">
        <div class="card p-5 shadow-sm border-0">
            <?php if ($is_safe): ?>
                <h3 class="text-success mb-4">🟢 安全引導跳轉中</h3>
                <p>正在前往本站安全網頁：</p>
                <div class="alert alert-secondary font-weight-bold my-3 text-break">
                    <?= h($url) ?>
                </div>
            <?php else: ?>
                <h3 class="text-danger mb-4">🛡️ 安全防護已阻擋外部跳轉</h3>
                <p class="text-danger font-weight-bold">偵測到不安全的外部 Open Redirect 跳轉請求！系統已將目標強制重設為首頁。</p>
                <div class="alert alert-warning font-weight-bold my-3 text-break">
                    已重設為：/index.php
                </div>
            <?php endif; ?>
            
            <p class="text-muted mt-3">若系統在 2 秒內未自動跳轉，請點擊下方連結：</p>
            <a href="<?= h($url) ?>" class="btn btn-primary font-weight-bold">手動前往</a>
        </div>
        
        <div class="alert alert-success mt-4 text-start">
            🛡️ <strong>安全改善說明：</strong><br>
            本頁面引入了跳轉白名單過濾機制。當檢測到 <code>url</code> 參數包含非白名單的外部主機網域時（例如 <code>google.com</code>），後端程式會立即攔截該請求，拒絕轉向外部非信任網站，從而防範釣魚轉導攻擊。
        </div>
    </div>
</body>
</html>
