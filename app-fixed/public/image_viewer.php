<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$file = $_GET['file'] ?? 'default_avatar.svg';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🖼️ 圖片檢視大廳 (安全版) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f8f9fa; font-family: system-ui, -apple-system, sans-serif; padding-top: 50px; }
        .card { background-color: white; border-radius: 12px; border: none; }
        .active-file { background-color: #0d6efd !important; color: white !important; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🖼️ 圖片檢視器 (安全修補版)</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <div class="row">
        <!-- 左側：選單與圖片預覽 -->
        <div class="col-md-6 mb-4">
            <div class="card p-3 mb-4 shadow-sm bg-white">
                <h5 class="text-primary mb-3">📁 系統內建大頭貼庫 (安全版)</h5>
                <div class="list-group">
                    <a href="?file=default_avatar.svg" class="list-group-item list-group-item-action <?= $file === 'default_avatar.svg' ? 'active-file' : '' ?>">
                        👤 預設大頭貼 (default_avatar.svg)
                    </a>
                </div>
            </div>

            <div class="card p-4 shadow-sm text-center bg-white">
                <h5 class="text-success mb-3 text-left">圖片預覽區</h5>
                <hr>
                <div class="p-3 bg-dark rounded d-inline-block w-100">
                    <!-- 連向安全的 show_image.php -->
                    <img src="show_image.php?file=<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>" class="img-fluid rounded" style="max-height: 250px;" onerror="this.src='https://placehold.co/200x200?text=Broken+Image';">
                </div>
                <div class="text-start mt-3">
                    <p class="small text-muted mb-1">HTML 請求語法：</p>
                    <code>&lt;img src="show_image.php?file=<?= htmlspecialchars($file, ENT_QUOTES, 'UTF-8') ?>"&gt;</code>
                </div>
            </div>
        </div>

        <!-- 右側：防禦說明 -->
        <div class="col-md-6 mb-4">
            <div class="card p-4 shadow-sm border-0 bg-white h-100">
                <h4 class="text-success mb-3">🛡️ 安全防禦對照說明</h4>
                <p>在安全修正版中，我們對 <code>show_image.php</code> 檔案讀取端點進行了嚴格的安全防範：</p>
                
                <div class="alert alert-success mt-3">
                    <h5>🔒 關鍵安全修補手段：</h5>
                    <ol class="mb-0">
                        <li>
                            <strong><code>basename()</code> 過濾目錄結構</strong>：<br>
                            即使傳入 <code>../../src/db.php</code>，後端也會強制簡化為純檔名 <code>db.php</code>，完全拔除任何 <code>../</code> 目錄遍歷特徵。
                        </li>
                        <li class="mt-2">
                            <strong>嚴格圖片 MIME 類型限制</strong>：<br>
                            後端讀取檔案後會檢測其真實 MIME 類型，非圖片格式（非 <code>image/</code> 或是 SVG 類型）之二進位將一律拒絕傳輸，防範程式碼洩漏。
                        </li>
                    </ol>
                </div>

                <div class="alert alert-secondary mt-3">
                    💡 <strong>防禦測試：</strong><br>
                    嘗試在新分頁中直接造訪相同的攻擊 URL：<br>
                    <a href="show_image.php?file=../../src/db.php" target="_blank" class="btn btn-sm btn-outline-danger font-weight-bold my-1">🔗 新分頁開啟 show_image.php?file=../../src/db.php</a><br>
                    * 預期結果：伺服器將直接回傳 <code>403 Access Denied</code> 或是 404 錯誤，成功防堵路徑穿越！
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
