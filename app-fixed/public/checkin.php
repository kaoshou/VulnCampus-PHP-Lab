<?php
require_once __DIR__ . '/../src/helpers.php';
// 權限檢查：確保登入
check_auth();
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📍 行動定位與打卡 - VulnCampus</title>
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
        <h2 class="text-primary">📍 行動定位與校園打卡 (已修補防禦)</h2>
        <div>
            <span class="me-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 打卡表單 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white font-weight-bold">
                    校園行動打卡 (安全參數化寫入)
                </div>
                <div class="card-body">
                    <form id="checkinForm">
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">GPS 經度 (Longitude)：</label>
                            <input type="text" id="lng" name="longitude" class="form-control" placeholder="請取得 GPS 定位..." readonly required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">GPS 緯度 (Latitude)：</label>
                            <input type="text" id="lat" name="latitude" class="form-control" placeholder="請取得 GPS 定位..." readonly required>
                        </div>
                        <div class="mb-3">
                            <label for="memo" class="form-label font-weight-bold">打卡備註 (Memo)：</label>
                            <textarea id="memo" name="memo" class="form-control" rows="3" placeholder="例如：我在圖書館讀書..." required></textarea>
                            <div class="form-text text-muted">提示：此備註輸入在修正版已透過預處理防止 SQL 注入，且在渲染時已做 HTML 輸出編碼防止 XSS。</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-info text-white" onclick="getLocation()">🧭 獲取當前 GPS 定位</button>
                            <button type="submit" class="btn btn-success">✅ 送出打卡</button>
                        </div>
                    </form>
                    <div id="statusMsg" class="mt-3"></div>
                    <div class="alert alert-secondary mt-3 border-success">
                        <span>🗺️ <strong>模擬地圖服務：</strong></span><br>
                        <small class="text-success">（安全防禦：前端不硬編碼金鑰，由後端依據 Session 動態代查或僅於伺服器端調用，保障金鑰安全性）</small>
                    </div>
                </div>
            </div>

            <!-- DOM-based XSS 防禦快取區 -->
            <div class="card shadow-sm mt-4 border-success">
                <div class="card-header bg-success text-white font-weight-bold">
                    💾 本地端快取資訊 (DOM XSS 防禦完成)
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">系統從瀏覽器 <code>localStorage</code> 讀取您上一次的打卡備註：</p>
                    <div class="alert alert-success">
                        <strong>上一次打卡備註：</strong>
                        <span id="last-memo-display">無快取紀錄</span>
                    </div>
                    <small class="text-success font-weight-bold">
                        * 防禦說明：本區塊使用安全的方法渲染文字內容，防止任何 HTML/JavaScript 被瀏覽器執行。
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
                    <div class="alert alert-success">
                        <strong>IDOR 越權防禦提示：</strong><br>
                        後端已實施存取控制校驗。若有使用者嘗試修改 API 的 <code>user_id</code> 為他人 ID 進行越權讀取，將被強制拒絕並回傳 <code>403 Forbidden</code>。
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

<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script>
    $(document).ready(function() {
        loadHistory();
        renderLastMemo();
    });

    function getLocation() {
        const status = document.getElementById('statusMsg');
        status.innerHTML = "<span class='text-info'>🧭 正在嘗試取得定位權限並定位...</span>";

        if (!navigator.geolocation) {
            status.innerHTML = "<span class='text-danger'>❌ 您的瀏覽器不支援定位功能！</span>";
            return;
        }

        navigator.geolocation.getCurrentPosition(
            function(position) {
                // 安全防禦：僅允許通過 Geolocation 獲取，且經緯度欄位為唯讀 (readonly) 以防惡意竄改
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
                status.innerHTML = "<span class='text-danger'>❌ " + msg + " (安全版限制：無法在無定位資料下提交)</span>";
            },
            { enableHighAccuracy: true, timeout: 5000 }
        );
    }

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
                        // 將備註快取至 localStorage
                        localStorage.setItem('last_location_memo', memo);
                        
                        renderLastMemo();
                        loadHistory();
                        $('#memo').val('');
                    } else {
                        status.innerHTML = "<span class='text-danger'>❌ 打卡失敗：" + escapeHtml(res.message) + "</span>";
                    }
                } catch(e) {
                    status.innerHTML = "<span class='text-danger'>❌ 伺服器回應解析錯誤</span>";
                }
            },
            error: function() {
                status.innerHTML = "<span class='text-danger'>❌ 無法連線至伺服器</span>";
            }
        });
    });

    function loadHistory() {
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
                                // 安全防護：對資料進行 HTML 安全輸出編碼，防止 Stored XSS 攻擊
                                html += '<tr>' +
                                        '<td>' + escapeHtml(item.created_at) + '</td>' +
                                        '<td>' + escapeHtml(item.latitude) + ', ' + escapeHtml(item.longitude) + '</td>' +
                                        '<td>' + escapeHtml(item.memo) + '</td>' +
                                        '</tr>';
                            });
                        }
                        tbody.html(html);
                    } else {
                        tbody.html('<tr><td colspan="3" class="text-danger text-center">載入失敗：' + escapeHtml(res.message) + '</td></tr>');
                    }
                } catch(e) {
                    tbody.html('<tr><td colspan="3" class="text-danger text-center">JSON 解析出錯</td></tr>');
                }
            },
            error: function(xhr) {
                if (xhr.status === 403) {
                    tbody.html('<tr><td colspan="3" class="text-danger text-center">🚫 權限不足 (403 Forbidden)：您無權查看他人打卡歷史</td></tr>');
                } else {
                    tbody.html('<tr><td colspan="3" class="text-danger text-center">伺服器連線錯誤</td></tr>');
                }
            }
        });
    }

    function renderLastMemo() {
        const lastMemo = localStorage.getItem('last_location_memo');
        const display = document.getElementById('last-memo-display');
        if (lastMemo) {
            // 安全防護：使用 text() 或 textContent 渲染本地快取變數，防止 DOM-based XSS 攻擊
            $(display).text(lastMemo);
        } else {
            $(display).text("無快取紀錄");
        }
    }

    // HTML 輸出防護輔助函數
    function escapeHtml(string) {
        return String(string).replace(/[&<>"']/g, function (s) {
            const entityMap = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            };
            return entityMap[s];
        });
    }
</script>
</body>
</html>
