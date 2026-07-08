<?php
require_once __DIR__ . '/../src/helpers.php';
// 權限檢查
check_auth();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🌐 DOM-based XSS 安全防禦 - VulnCampus</title>
    <!-- 使用 Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f4f7f6; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🌐 DOM-based XSS 安全防禦頁面</h2>
        <div>
            <span class="me-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="card col-md-8 mx-auto">
        <div class="card-header bg-primary text-white font-weight-bold">
            DOM-based 迎賓系統 (已防禦 DOM XSS)
        </div>
        <div class="card-body">
            <p class="text-muted">
                本頁面由前端 JavaScript 讀取 URL 中的 <code>#name=X</code> 參數進行渲染。
            </p>
            <hr>
            
            <div class="alert alert-success">
                <h4>👋 <span id="welcome-message">載入中...</span></h4>
            </div>

            <div class="mt-4">
                <h5>🛡️ 安全修補防禦說明</h5>
                <p>在安全修正版中，我們使用內建的 <code>textContent</code> 屬性取代不安全的 <code>innerHTML</code> 屬性來載入並顯示參數值：</p>
                <code class="bg-dark text-warning p-2 d-block rounded mb-3">
                    document.getElementById('welcome-message').textContent = "歡迎光臨，" + name + "！";
                </code>
                <p class="mb-0">
                    這強制瀏覽器將傳入的資料（包括 <code>&lt;script&gt;</code>、<code>&lt;img&gt;</code>）視為<b>純文字字串</b>而非 HTML/JavaScript 代碼渲染，從而徹底解決了 DOM-based XSS 的威脅。
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    function renderFromHash() {
        const hash = window.location.hash;
        let name = "訪客";
        
        if (hash && hash.startsWith('#name=')) {
            name = decodeURIComponent(hash.substring(6));
        }

        // 安全修補：使用 textContent 來輸出渲染，確保瀏覽器將其視為字串，防止 DOM-based XSS
        document.getElementById('welcome-message').textContent = "歡迎光臨，" + name + "！";
    }

    window.addEventListener('hashchange', renderFromHash);
    window.addEventListener('DOMContentLoaded', renderFromHash);
</script>
</body>
</html>
