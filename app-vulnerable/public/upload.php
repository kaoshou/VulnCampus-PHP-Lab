<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>大頭貼上傳漏洞：四大挑戰入口 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #0b0f19; color: #e2e8f0; padding-top: 50px; }
        .card { background-color: #111827; border: 1px solid #1f2937; border-radius: 12px; transition: all 0.3s; }
        .card:hover { transform: translateY(-4px); border-color: #ea580c; }
        .btn-primary { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); border: none; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📤 大頭貼上傳漏洞：四大繞過挑戰</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <div class="alert alert-info py-3">
        💡 <strong>本頁為導覽大廳</strong>：我們將任意檔案上傳的防護繞過拆分為四個獨立的關卡，方便您依序進行測試與學習。請點選下方卡片進入各別關卡。
    </div>

    <div class="row mt-4">
        <!-- 第一關 -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-warning font-weight-bold">🎯 第一關：HTML accept 篩選</h5>
                    <p class="card-text text-muted flex-grow-1">此關卡僅在 HTML 端的 input 標籤使用 <code>accept</code> 屬性提示圖片類型，後端無任何限制。</p>
                    <a href="/upload_bypass_html.php" class="btn btn-primary font-weight-bold mt-3">進入第一關 ➔</a>
                </div>
            </div>
        </div>

        <!-- 第二關 -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-warning font-weight-bold">🎯 第二關：前端 JS 副檔名驗證</h5>
                    <p class="card-text text-muted flex-grow-1">此關卡除 HTML 限制外，更加入了瀏覽器端 JavaScript，若副檔名非圖片會進行阻擋彈窗。</p>
                    <a href="/upload_bypass_js.php" class="btn btn-primary font-weight-bold mt-3">進入第二關 ➔</a>
                </div>
            </div>
        </div>

        <!-- 第三關 -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-warning font-weight-bold">🎯 第三關：後端 Content-Type 弱驗證</h5>
                    <p class="card-text text-muted flex-grow-1">此關卡無前端驗證，但後端會校驗 Request Header 中的 <code>Content-Type</code> 欄位是否為圖片格式。</p>
                    <a href="/upload_bypass_backend.php" class="btn btn-primary font-weight-bold mt-3">進入第三關 ➔</a>
                </div>
            </div>
        </div>

        <!-- 第四關 -->
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-warning font-weight-bold">🎯 第四關：繞過圖像特徵檢測 (圖片木馬)</h5>
                    <p class="card-text text-muted flex-grow-1">此關卡後端使用 <code>getimagesize()</code> 嚴格檢測是否為真實圖片二進位特徵，但因未過濾副檔名，可使用內嵌 PHP 指令的「圖馬」進行繞過。</p>
                    <a href="/upload_bypass_polyglot.php" class="btn btn-primary font-weight-bold mt-3">進入第四關 ➔</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
