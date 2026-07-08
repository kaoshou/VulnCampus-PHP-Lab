<?php
require_once __DIR__ . '/../src/helpers.php';
// 權限檢查
check_auth();

// 預設樣式
if (!isset($_SESSION['custom_css_fixed'])) {
    $_SESSION['custom_css_fixed'] = "/* 安全版：在此輸入您的自訂 CSS 樣式 */\nbody {\n    background-color: #f4f7f6;\n}";
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $custom_css = $_POST['custom_css'] ?? '';
    
    // 安全防禦 1：阻斷 HTML 逃逸。若包含 <, >, 則可能透過 </style><script>... 逃逸 style 標籤發動 XSS
    if (preg_match('/[<>]/', $custom_css)) {
        $error = '儲存失敗：CSS 樣式中不得包含 < 或 > 等 HTML 逃逸字元！';
    }
    // 安全防禦 2：阻斷外聯載入與惡意 CSS。排除 url(), @import, expression, javascript:, content 等危險關鍵字
    else if (preg_match('/url\s*\(|@import|expression|javascript|content|behavior/i', $custom_css)) {
        $error = '儲存失敗：禁止使用 url()、@import、expression 或 javascript: 等潛在的外流/代碼執行屬性！';
    } 
    else {
        // 安全防禦 3：使用 htmlspecialchars 在輸出時編碼
        $_SESSION['custom_css_fixed'] = $custom_css;
        $success = '安全樣式儲存成功！已套用新樣式。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🎨 CSS Injection 安全防禦 - VulnCampus</title>
    <!-- 使用 Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- 安全防禦 4：在 style 標籤輸出時進行基本的 htmlspecialchars 編碼 (除了樣式符號外，防止 HTML 標籤逃逸) -->
    <style id="custom-style-tag">
        <?= htmlspecialchars($_SESSION['custom_css_fixed'], ENT_NOQUOTES, 'UTF-8') ?>
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🎨 版面樣式自訂 (CSS 注入已防禦)</h2>
        <div>
            <span class="me-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 設定面板 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white font-weight-bold">
                    自訂版面 CSS 樣式
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="custom_css" class="form-label font-weight-bold">輸入自訂 CSS 代碼：</label>
                            <textarea name="custom_css" id="custom_css" class="form-control" rows="8" required><?= h($_SESSION['custom_css_fixed']) ?></textarea>
                            <div class="form-text text-muted">
                                防禦機制：系統會過濾 <code>url()</code>, <code>@import</code>, <code>&lt;</code>, <code>&gt;</code> 等高風險字詞，以確保 CSS 注入無法發動。
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success w-100">💾 儲存並套用樣式</button>
                        <button type="button" class="btn btn-outline-danger w-100 mt-2" onclick="resetStyle(event)">🔄 重設為預設樣式</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 說明面板 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-success border-2">
                <div class="card-header bg-success text-white font-weight-bold">
                    🛡️ CSS 注入安全修補說明
                </div>
                <div class="card-body">
                    <p>在安全修正版中，我們對輸入的自訂 CSS 實施了多層次防禦：</p>
                    <ol class="mb-0">
                        <li class="mb-2"><strong>黑名單過濾關鍵字</strong>：阻斷包含 <code>url()</code>、<code>@import</code> 等 CSS 外聯字串，防範透過載入外部圖片、字型外洩網頁敏感資訊。</li>
                        <li class="mb-2"><strong>禁止 HTML 標籤逃逸</strong>：拒絕輸入包含 <code>&lt;</code> 或 <code>&gt;</code> 的代碼，防範攻擊者透過輸入 <code>&lt;/style&gt;&lt;script&gt;alert(1)&lt;/script&gt;</code> 閉合樣式標籤並發動 XSS。</li>
                        <li><strong>輸出進行編碼過濾</strong>：在 <code>&lt;style&gt;</code> 標籤渲染時對 CSS 內文實行 HTML 編碼轉義。</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function resetStyle(e) {
        e.preventDefault();
        if (confirm('確定要重設樣式嗎？')) {
            $.post('', { custom_css: "/* 安全版：在此輸入您的自訂 CSS 樣式 */\nbody {\n    background-color: #f4f7f6;\n}" }, function() {
                window.location.reload();
            });
        }
    }
</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
</body>
</html>
