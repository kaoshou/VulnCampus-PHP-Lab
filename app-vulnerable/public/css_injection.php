<?php
require_once __DIR__ . '/../src/helpers.php';
// 檢查是否登入
check_login();

// 預設樣式
if (!isset($_SESSION['custom_css'])) {
    $_SESSION['custom_css'] = "/* 在此輸入您的自訂 CSS 樣式 */\nbody {\n    background-color: #f8f9fa;\n}";
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $custom_css = $_POST['custom_css'] ?? '';
    
    // 漏洞點：弱點版完全沒有對輸入的 CSS 進行任何過濾、編碼或白名單限制，直接儲存
    $_SESSION['custom_css'] = $custom_css;
    $success = '樣式儲存成功！已套用新樣式。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🎨 CSS Injection 測試 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    
    <!-- 漏洞點：直接輸出未經過濾的自訂 CSS 程式碼 -->
    <style id="custom-style-tag">
        <?= $_SESSION['custom_css'] ?>
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🎨 版面樣式自訂 (CSS Injection 專屬演練)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 設定面板 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white font-weight-bold">
                    自訂版面 CSS 樣式
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="custom_css" class="font-weight-bold">輸入自訂 CSS 代碼：</label>
                            <textarea name="custom_css" id="custom_css" class="form-control" rows="8" required><?= htmlspecialchars($_SESSION['custom_css']) ?></textarea>
                            <small class="form-text text-muted">
                                提示：後端未進行任何檢查。您可以輸入自訂樣式改變網頁背景色、隱藏元素或載入外部資源。
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success btn-block">💾 儲存並套用樣式</button>
                        <a href="?reset=1" class="btn btn-outline-danger btn-block mt-2" onclick="resetStyle(event)">🔄 重設為預設樣式</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- 說明面板 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark font-weight-bold">
                    💡 CSS 注入 (CSS Injection) 演示指南
                </div>
                <div class="card-body">
                    <p>CSS Injection 漏洞允許攻擊者控制或注入自訂的樣式表 (Stylesheet)。攻擊者可藉此實行以下攻擊：</p>
                    <ul>
                        <li><strong>改變網頁排版/仿冒按鈕</strong>：藉由絕對定位 (Absolute Positioning) 覆蓋隱藏重要按鈕或表單，實施手動的點擊劫持 (Clickjacking)。</li>
                        <li><strong>外洩敏感資訊 (Data Exfiltration)</strong>：使用 CSS 的屬性選擇器 (Attribute Selectors) 與背景載入特性，逐字竊取網頁中其他 input 欄位的敏感資料。</li>
                    </ul>
                    <hr>
                    <p class="font-weight-bold">🔥 課堂測試 Payload 範例：</p>
                    
                    <p>1. 注入 CSS 強制修改網頁背景，使其顯現出明顯的紅色特徵：</p>
                    <pre class="bg-dark text-warning p-2 rounded">body { background-color: #ffcccc !important; }</pre>
                    
                    <p>2. 注入外部圖片連結，在用戶載入網頁時悄悄外洩流量（可搭配 ZAP 觀察發送的外聯網址）：</p>
                    <pre class="bg-dark text-warning p-2 rounded">body { background-image: url('http://localhost:8080/api/checkin_history.php?leak=1'); }</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function resetStyle(e) {
        e.preventDefault();
        if (confirm('確定要重設樣式嗎？')) {
            // 直接以 AJAX 或前端清空 Session 並重整
            $.post('', { custom_css: "/* 在此輸入您的自訂 CSS 樣式 */\nbody {\n    background-color: #f8f9fa;\n}" }, function() {
                window.location.reload();
            });
        }
    }
</script>
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
</body>
</html>
