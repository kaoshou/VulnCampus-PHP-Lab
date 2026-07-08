<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$submitted = false;
$page = $_GET['page'] ?? '';
$output_content = '';

// 安全防禦版：只允許加載白名單內的合法本地模組檔案，嚴禁任何使用者自訂路徑
$white_list = [
    'about.php' => '模組介紹頁面',
    'contact.php' => '校園聯絡資訊',
    'faq.php' => '常見問題解答'
];

if ($page !== '') {
    $submitted = true;
    
    if (array_key_exists($page, $white_list)) {
        // 白名單校驗成功，安全載入
        $safe_path = __DIR__ . '/' . $page;
        if (file_exists($safe_path) && is_file($safe_path)) {
            ob_start();
            include $safe_path;
            $output_content = ob_get_clean();
        } else {
            $output_content = "<div class='alert alert-warning'>❌ 模組載入錯誤。</div>";
        }
    } else {
        // 非法請求攔截
        $output_content = "
        <div class='alert alert-danger font-weight-bold'>🛡️ [安全防禦機制攔截成功]</div>
        <p class='text-danger'>系統拒絕載入未授權的檔案或外部網址：<code>" . htmlspecialchars($page) . "</code></p>
        <p class='text-muted small'>防護原理：系統採用硬編碼白名單限制。僅允許載入白名單中定義的安全模組，防止 LFI / RFI 與目錄穿越攻擊。</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📂 檔案引入防禦驗證 (LFI / RFI) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f4f7f6; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📂 檔案引入防禦驗證 (LFI / RFI 白名單)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 引入表單 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    動態模組加載器 (安全防禦白名單版)
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="mb-3">
                            <label for="page" class="form-label font-weight-bold">請選擇或輸入要引入的檔案路徑 (Page / URL)：</label>
                            <input type="text" name="page" id="page" class="form-control" placeholder="例如：/etc/passwd 或 http://attacker.com/shell.txt" required value="<?= h($page) ?>">
                        </div>
                        <button type="submit" class="btn btn-success w-100">🚀 安全載入 (Include)</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護說明：</strong><br>
                        安全修正版配置了硬編碼的**白名單校驗**，僅允許輸入白名單鍵值（如 <code>about.php</code>）。任何嘗試輸入 <code>/etc/passwd</code>、<code>../</code> 或外部 http 連結都會被直接安全封鎖。
                    </div>
                </div>
            </div>
        </div>

        <!-- 執行結果展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-dark">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
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
