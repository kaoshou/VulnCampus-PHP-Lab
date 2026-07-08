<?php
require_once __DIR__ . '/../src/helpers.php';
// 權限檢查：確保登入
check_auth();

$keyword = $_GET['keyword'] ?? '';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📖 反射型 XSS 安全防禦 - VulnCampus</title>
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
        <h2 class="text-primary">📖 反射型 XSS 安全防禦頁面</h2>
        <div>
            <span class="me-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="card col-md-8 mx-auto">
        <div class="card-header bg-primary text-white font-weight-bold">
            課程與關鍵字搜尋 (已修補防禦)
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="input-group mb-3">
                    <input type="text" name="keyword" class="form-control" placeholder="輸入搜尋關鍵字..." value="<?= h($keyword) ?>">
                    <button class="btn btn-primary" type="submit">🔍 搜尋</button>
                </div>
            </form>

            <?php if ($keyword !== ''): ?>
                <hr>
                <div class="alert alert-success">
                    <!-- 修補防禦：使用 h() 或 htmlspecialchars 進行輸出編碼，徹底防止反射型 XSS -->
                    <h4>您搜尋的關鍵字是：<strong><?= h($keyword) ?></strong></h4>
                    <p class="text-muted mt-2 mb-0">
                        安全機制：任何 HTML 標籤（如 <code>&lt;script&gt;</code>）均已被轉義為 HTML 實體編碼 (如 <code>&amp;lt;script&amp;gt;</code>)，防止瀏覽器將其視為程式碼執行。
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
