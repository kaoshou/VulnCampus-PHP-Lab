<?php
require_once __DIR__ . '/../src/helpers.php';
// 檢查是否登入
check_login();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🌐 DOM-based XSS 測試 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🌐 DOM-based XSS 專屬演練</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="card shadow-sm col-md-8 mx-auto">
        <div class="card-header bg-primary text-white font-weight-bold">
            DOM-based 迎賓系統 (前端渲染)
        </div>
        <div class="card-body">
            <p class="text-muted">
                本頁面不經過後端伺服器回顯，而是完全由前端 JavaScript 讀取 URL 中的 <code>#name=X</code> 參數進行動態渲染。
            </p>
            <hr>
            
            <div class="alert alert-warning">
                <h4>👋 <span id="welcome-message">載入中...</span></h4>
            </div>

            <div class="mt-4">
                <h5>💡 課堂漏洞演練指引</h5>
                <p>請在網址列的後面加上 <code>#name=您的名字</code>，然後重新整理網頁（或按 Enter）。例如：</p>
                <code class="bg-dark text-warning p-2 d-block rounded mb-3">
                    http://localhost:8080/xss_dom.php#name=&lt;img src=x onerror=alert(document.cookie)&gt;
                </code>
                <p class="text-danger font-weight-bold">
                    ※ 核心安全設定錯誤對照：由於弱點版中的 Session Cookie 缺少 <code>HttpOnly</code> 屬性，此 XSS 漏洞可以直接彈出您的整個 Session Cookie (含 PHPSESSID)，使您的帳號可以直接被 Session Hijacking 劫持！
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    // 讀取 URL Hash 並進行解析
    function renderFromHash() {
        const hash = window.location.hash;
        let name = "訪客";
        
        if (hash && hash.startsWith('#name=')) {
            name = decodeURIComponent(hash.substring(6));
        }

        // 漏洞點：前端 JavaScript 直接使用 innerHTML 來渲染使用者控制的 hash 參數，造成 DOM-based XSS
        document.getElementById('welcome-message').innerHTML = "歡迎光臨，" + name + "！";
    }

    // 監聽 hash 變化與頁面加載
    window.addEventListener('hashchange', renderFromHash);
    window.addEventListener('DOMContentLoaded', renderFromHash);
</script>
</body>
</html>
