<?php
require_once __DIR__ . '/../src/helpers.php';
// 檢查是否登入
check_login();

$keyword = $_GET['keyword'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📖 反射型 XSS 測試 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📖 反射型 XSS (Reflected XSS) 專屬演練</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="card shadow-sm col-md-8 mx-auto">
        <div class="card-header bg-primary text-white font-weight-bold">
            課程與關鍵字搜尋
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="input-group mb-3">
                    <input type="text" name="keyword" class="form-control" placeholder="輸入搜尋關鍵字，例如: XSS 測試..." value="<?= sanitize($keyword) ?>">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">🔍 搜尋</button>
                    </div>
                </div>
            </form>

            <?php if ($keyword !== ''): ?>
                <hr>
                <div class="alert alert-info">
                    <!-- 漏洞點：弱點版直接回顯搜尋關鍵字，完全無編碼，引發反射型 XSS -->
                    <h4>您搜尋的關鍵字是：<strong><?= $keyword ?></strong></h4>
                    <p class="text-muted mt-2">
                        ZAP 主動掃描器將會為此回顯送出 <code>&lt;script&gt;alert(1)&lt;/script&gt;</code> 測試並識別出漏洞。
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
