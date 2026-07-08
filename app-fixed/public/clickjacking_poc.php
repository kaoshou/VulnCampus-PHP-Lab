<?php
$triggered = isset($_GET['triggered']) ? $_GET['triggered'] : 0;
$deleted = isset($_GET['deleted']) ? intval($_GET['deleted']) : 0;
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🎯 Clickjacking (點擊劫持) 漏洞驗證 - VulnCampus 修正版</title>
    <!-- 使用 Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; padding: 20px; }
        .instructions { background-color: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        #attack-container {
            position: relative;
            width: 500px;
            height: 300px;
            border: 3px dashed #198754;
            margin: 0 auto;
            background-color: #fff;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }

        #bait-button {
            position: absolute;
            top: 100px;
            left: 150px;
            width: 200px;
            height: 50px;
            z-index: 1;
            background-color: #198754;
            color: white;
            border: none;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }

        #target-iframe {
            width: 100%;
            height: 100%;
            border: none;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 10;
            opacity: 0.8;
        }
    </style>
</head>
<body>

<div class="container my-4">
    <div class="instructions shadow-sm">
        <h2 class="text-success font-weight-bold text-center">🛡️ Clickjacking (點擊劫持) 防禦驗證</h2>
        <p class="lead text-center">本頁面展示安全修正版網站 (Port 8081) 的點擊劫持防守效果。</p>
        
        <div class="alert alert-success">
            <strong>🟢 防禦機制與效果：</strong><br>
            1. 安全修正版的目標設定頁 `clickjacking_target.php` 輸出了回應標頭：<br>
            &nbsp;&nbsp;&nbsp;&nbsp;<code>X-Frame-Options: DENY</code> 與 <code>Content-Security-Policy: frame-ancestors 'none';</code><br>
            2. 當瀏覽器試圖在下方的 iframe 載入該頁面時，會自動拒絕渲染並報錯。您會看到 iframe 區域為空白，這意味著攻擊者<strong>無法透過 Clickjacking 覆蓋此按鈕</strong>，防護完全成功！
        </div>

        <div class="my-3 text-center">
            <span class="font-weight-bold me-2">調整 Target iframe 透明度：</span>
            <button class="btn btn-secondary btn-sm" onclick="setOpacity(1.0)">1.0</button>
            <button class="btn btn-primary btn-sm" onclick="setOpacity(0.4)">0.4</button>
            <button class="btn btn-danger btn-sm" onclick="setOpacity(0.0)">0.0</button>
        </div>
        
        <div class="text-secondary text-center small mb-3">
            ※ 若下方大框框中無法加載任何 VulnCampus 網站畫面（例如在開發者工具主控台中看到 <code>Refused to display...</code>），代表 Clickjacking 防守成功！
        </div>
        <div class="text-center">
            <a href="/index.php" class="btn btn-outline-secondary">回首頁</a>
        </div>
    </div>

    <!-- 演示區 -->
    <div id="attack-container">
        <!-- 惡意誘餌按鈕 (下層) -->
        <button id="bait-button">
            🎁 領取點券 (防守測試)
        </button>

        <!-- 目標網頁 iframe (上層) -->
        <iframe id="target-iframe" src="/clickjacking_target.php"></iframe>
    </div>
</div>

<script>
    function setOpacity(val) {
        document.getElementById('target-iframe').style.opacity = val;
    }
</script>
</body>
</html>
