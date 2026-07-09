<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$file = $_GET['file'] ?? 'default_avatar.svg';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🖼️ 大頭貼圖片檢視大廳 (路徑穿越演練) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #0b0f19; color: #e2e8f0; padding-top: 50px; }
        .card { background-color: #111827; border: 1px solid #1f2937; border-radius: 12px; }
        .list-group-item { background-color: #1f2937; border-color: #374151; color: #e2e8f0; }
        .list-group-item:hover { background-color: #374151; color: #ffffff; }
        .active-file { background-color: #ea580c !important; color: white !important; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🖼️ 大頭貼圖片檢視器 (Path Traversal 實作)</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <div class="row">
        <!-- 左側：模擬選單與圖片預覽 -->
        <div class="col-md-6 mb-4">
            <div class="card p-3 mb-4 shadow-sm">
                <h5 class="text-info mb-3">📁 系統內建大頭貼庫</h5>
                <div class="list-group">
                    <a href="?file=default_avatar.svg" class="list-group-item list-group-item-action <?= $file === 'default_avatar.svg' ? 'active-file' : '' ?>">
                        👤 預設大頭貼 (default_avatar.svg)
                    </a>
                    <div class="list-group-item text-muted small">
                        * 已上傳的其他使用者大頭貼，將透過網址 <code>?file=檔名</code> 來進行加載檢視。
                    </div>
                </div>
            </div>

            <div class="card p-4 shadow-sm text-center">
                <h5 class="text-warning mb-3 text-left">圖片預覽區</h5>
                <hr style="border-color: #1f2937;">
                <div class="p-3 bg-dark rounded d-inline-block w-100">
                    <!-- 此處透過 <img> 標籤請求後端的 show_image.php 取得二進位串流 -->
                    <img src="show_image.php?file=<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded" style="max-height: 250px;" onerror="this.src='https://placehold.co/200x200?text=Broken+Image';">
                </div>
                <div class="text-left mt-3">
                    <p class="small text-muted mb-1">HTML 請求語法：</p>
                    <code>&lt;img src="show_image.php?file=<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>"&gt;</code>
                </div>
            </div>
        </div>

        <!-- 右側：演練說明 -->
        <div class="col-md-6 mb-4">
            <div class="card p-4 shadow-sm border-warning h-100">
                <h4 class="text-warning mb-3">💡 圖片讀取接口之任意檔案讀取漏洞</h4>
                <p>在許多 Web 應用中，讀取圖片或多媒體是由一個後端腳本（如 <code>show_image.php</code>）來負責讀取檔案並向瀏覽器輸出 binary 二進位資料。</p>
                
                <div class="alert alert-danger">
                    🚨 <strong>漏洞成因：</strong><br>
                    弱點版中的 <code>show_image.php</code> 在讀取檔案時，直接將傳入的參數拼接到路徑中，且<strong>未限制目錄穿越字元 (<code>../</code>)</strong>。這會允許任何人跳脫圖片資料夾，讀取伺服器內部任何檔案。
                </div>

                <div class="alert alert-info">
                    🔥 <strong>課堂滲透測試演練：</strong><br>
                    若直接用 <code>&lt;img&gt;</code> 載入非圖片檔案，瀏覽器會因為解析失敗而顯示「破圖」。<br>
                    <strong>但身為攻擊者，我們可以直接在新分頁開啟或使用 ZAP / Curl 請求該二進位端點！</strong><br><br>

                    1. <strong>讀取資料庫明文密碼檔</strong>：<br>
                       在新分頁中直接造訪以下 URL：<br>
                       <a href="show_image.php?file=../../src/db.php" target="_blank" class="btn btn-sm btn-warning font-weight-bold my-1">🔗 新分頁開啟 show_image.php?file=../../src/db.php</a><br>
                       * 預期結果：瀏覽器將直接把資料庫明文設定檔內容輸出在網頁畫面上！<br><br>

                    2. <strong>讀取系統機密檔案 (/etc/passwd)</strong>：<br>
                       在新分頁中直接造訪以下 URL：<br>
                       <a href="show_image.php?file=../../../../../../../../etc/passwd" target="_blank" class="btn btn-sm btn-warning font-weight-bold my-1">🔗 新分頁開啟 show_image.php?file=../../../../../../../../etc/passwd</a><br>
                       * 預期結果：成功將 Linux 系統所有帳號列表讀出！
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
