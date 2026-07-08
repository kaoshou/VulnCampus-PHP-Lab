<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📍 行動定位與打卡 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📍 行動定位與校園打卡 (HTML5 Geolocation / localStorage)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 打卡表單 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white font-weight-bold">
                    校園行動打卡
                </div>
                <div class="card-body">
                    <form id="checkinForm">
                        <div class="form-group">
                            <label class="font-weight-bold">GPS 經度 (Longitude)：</label>
                            <input type="text" id="lng" name="longitude" class="form-control" placeholder="點選下方按鈕取得定位..." required>
                        </div>
                        <div class="form-group">
                            <label class="font-weight-bold">GPS 緯度 (Latitude)：</label>
                            <input type="text" id="lat" name="latitude" class="form-control" placeholder="點選下方按鈕取得定位..." required>
                        </div>
                        <div class="form-group">
                            <label for="memo" class="font-weight-bold">打卡備註 (Memo)：</label>
                            <textarea id="memo" name="memo" class="form-control" rows="3" placeholder="例如：我在圖書館讀書..." required></textarea>
                            <small class="form-text text-muted">提示：打卡備註未進行 XSS 過濾。試試輸入 <code>&lt;script&gt;alert(document.cookie)&lt;/script&gt;</code></small>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-info" onclick="getLocation()">🧭 獲取當前 GPS 定位</button>
                            <button type="submit" class="btn btn-success">✅ 送出打卡</button>
                        </div>
                    </form>
                    <div id="statusMsg" class="mt-3"></div>
                    <div class="alert alert-secondary mt-3 border-danger">
                        <span>🗺️ <strong>模擬地圖服務：</strong></span><br>
                        <small class="text-danger">（前端已硬編碼載入 Google Maps API 憑證金鑰 <code>AIzaSyB3X_Vulnerable_Mock_Google_Maps_API_Key_XYZ123</code>）</small>
                    </div>
                </div>
            </div>

            <!-- DOM-based XSS 示範區 -->
            <div class="card shadow-sm mt-4 border-warning">
                <div class="card-header bg-warning text-dark font-weight-bold">
                    💾 本地端快取資訊 (DOM-based XSS 測試)
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">系統從瀏覽器 <code>localStorage</code> 讀取您上一次的打卡備註：</p>
                    <div class="alert alert-warning">
                        <strong>上一次打卡備註：</strong>
                        <span id="last-memo-display">無快取紀錄</span>
                    </div>
                    <small class="text-danger font-weight-bold">
                        * 漏洞點：本區塊直接使用 <code>innerHTML</code> 渲染 <code>localStorage</code> 內容，造成 DOM XSS。
                    </small>
                </div>
            </div>
        </div>

        <!-- 歷史打卡紀錄 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-dark text-white font-weight-bold d-flex justify-content-between align-items-center">
                    <span>📜 我的打卡歷史足跡</span>
                    <button class="btn btn-sm btn-outline-light" onclick="loadHistory()">🔄 重新整理</button>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">歷史打卡資料讀取自 API <code>/api/checkin_history.php?user_id=<?= $_SESSION['user']['id'] ?></code></p>
                    <div class="alert alert-info">
                        <strong>教學用 IDOR 越權提示：</strong><br>
                        試著修改網址列的參數或修改 API 請求中的 <code>user_id</code> 為其他同學的 ID (例如 <code>user_id=3</code>)，即可偷窺他人的歷史行動定位軌跡！
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>時間</th>
                                    <th>經緯度</th>
                                    <th>打卡備註</th>
                                </tr>
                            </thead>
                            <tbody id="historyTableBody">
                                <tr><td colspan="3" class="text-center">載入中...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script>
    // 漏洞點：敏感的 Google Maps API 金鑰硬編碼在前端 JavaScript 程式碼中 (ZAP 掃描器可被動偵測並報警)
    const GOOGLE_MAPS_API_KEY = "AIzaSyB3X_Vulnerable_Mock_Google_Maps_API_Key_XYZ123";

    // 網頁載入時載入歷史資料與本地快取
    $(document).ready(function() {
        loadHistory();
        renderLastMemo();
    });

    // 1. 呼叫 HTML5 Geolocation API
    function getLocation() {
        const status = document.getElementById('statusMsg');
        status.innerHTML = "<span class='text-info'>🧭 正在嘗試取得定位權限並定位...</span>";

        if (!navigator.geolocation) {
            status.innerHTML = "<span class='text-danger'>❌ 您的瀏覽器不支援 HTML5 定位功能！</span>";
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                document.getElementById('lat').value = position.coords.latitude;
                document.getElementById('lng').value = position.coords.longitude;
                status.innerHTML = "<span class='text-success'>✓ 定位取得成功！</span>";
            },
            function(error) {
                let msg = "定位取得失敗：";
                switch(error.code) {
                    case error.PERMISSION_DENIED:
                        msg += "使用者拒絕提供定位權限。";
                        break;
                    case error.POSITION_UNAVAILABLE:
                        msg += "無法取得當前位置資訊。";
                        break;
                    case error.TIMEOUT:
                        msg += "取得定位超時。";
                        break;
                    default:
                        msg += "不明原因錯誤。";
                }
                // 弱點版演示：若瀏覽器定位不可用，允許學員手動輸入測試，不受限制
                status.innerHTML = "<span class='text-warning'>⚠️ " + msg + " (已允許手動填寫以供測試)</span>";
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    }

    // 2. 提交打卡
    $('#checkinForm').on('submit', function(e) {
        e.preventDefault();
        const status = document.getElementById('statusMsg');
        const lat = $('#lat').val();
        const lng = $('#lng').val();
        const memo = $('#memo').val();

        status.innerHTML = "<span class='text-info'>⏳ 正在傳送打卡請求...</span>";

        $.ajax({
            url: '/api/checkin.php',
            type: 'POST',
            data: { latitude: lat, longitude: lng, memo: memo },
            success: function(response) {
                try {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        status.innerHTML = "<span class='text-success'>✓ 打卡成功！</span>";
                        // 將本次打卡備註寫入 localStorage 快取
                        localStorage.setItem('last_location_memo', memo);
                        
                        renderLastMemo();
                        loadHistory();
                        $('#memo').val('');
                    } else {
                        status.innerHTML = "<span class='text-danger'>❌ 打卡失敗：" + res.message + "</span>";
                    }
                } catch(e) {
                    // 若後端發生 SQL 注入錯誤爆出 Exception，直接印出，展現異常處理不當
                    status.innerHTML = "<span class='text-danger'>❌ 伺服器錯誤回傳：</span><pre class='bg-dark text-warning p-2 mt-2'>" + response + "</pre>";
                }
            },
            error: function() {
                status.innerHTML = "<span class='text-danger'>❌ 無法連線至伺服器</span>";
            }
        });
    });

    // 3. 載入打卡歷史
    function loadHistory() {
        // 先獲取當前 URL 中是否有 user_id 參數，若有則越權讀取該 ID 的資料，否則預設使用當前 Session ID
        const urlParams = new URLSearchParams(window.location.search);
        let userId = urlParams.get('user_id');
        if (!userId) {
            userId = <?= $_SESSION['user']['id'] ?>;
        }
        
        const tbody = $('#historyTableBody');
        tbody.html('<tr><td colspan="3" class="text-center">載入中...</td></tr>');

        $.ajax({
            url: '/api/checkin_history.php?user_id=' + userId,
            type: 'GET',
            success: function(response) {
                try {
                    const res = JSON.parse(response);
                    if (res.status === 'success') {
                        let html = '';
                        if (res.data.length === 0) {
                            html = '<tr><td colspan="3" class="text-center">無打卡紀錄</td></tr>';
                        } else {
                            res.data.forEach(function(item) {
                                // 漏洞點：弱點版直接使用 html 輸出 memo，未做 XSS 編碼，引發 Stored XSS
                                html += '<tr>' +
                                        '<td>' + item.created_at + '</td>' +
                                        '<td>' + item.latitude + ', ' + item.longitude + '</td>' +
                                        '<td>' + item.memo + '</td>' +
                                        '</tr>';
                            });
                        }
                        tbody.html(html);
                    } else {
                        tbody.html('<tr><td colspan="3" class="text-danger text-center">載入失敗：' + res.message + '</td></tr>');
                    }
                } catch(e) {
                    tbody.html('<tr><td colspan="3" class="text-danger text-center">JSON 解析出錯</td></tr>');
                }
            }
        });
    }

    // 4. 渲染本地快取 last_location_memo
    function renderLastMemo() {
        const lastMemo = localStorage.getItem('last_location_memo');
        const display = document.getElementById('last-memo-display');
        if (lastMemo) {
            // 漏洞點：innerHTML 渲染本地變數，引起 DOM-based XSS
            display.innerHTML = lastMemo;
        } else {
            display.innerHTML = "無快取紀錄";
        }
    }
</script>
</body>
</html>
