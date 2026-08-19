<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

// 模擬的學生敏感個資清單
$students = [
    ['name' => '陳小明', 'sid' => 'D1101234', 'id_card' => 'A123456789', 'phone' => '0912-345-678', 'email' => 'xiaoming@vulncampus.local'],
    ['name' => '林美玲', 'sid' => 'D1101235', 'id_card' => 'F229876543', 'phone' => '0988-765-432', 'email' => 'meiling@vulncampus.local'],
    ['name' => '張大華', 'sid' => 'D1101236', 'id_card' => 'H122334455', 'phone' => '0933-111-222', 'email' => 'dahua@vulncampus.local']
];
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔍 自訂個資洩漏與 ZAP 腳本檢測 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #0b0f19; color: #e2e8f0; font-family: 'Noto Sans TC', sans-serif; padding-top: 50px; }
        .card { background-color: #111827; border: 1px solid #1f2937; border-radius: 14px; margin-bottom: 24px; }
        .card-header { background-color: #1f2937; border-bottom: 1px solid #374151; color: #fb923c; font-weight: 700; }
        pre { background-color: #030712 !important; border: 1px solid #374151; color: #10b981 !important; padding: 15px; border-radius: 8px; }
        .table { color: #e2e8f0; }
        .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(255, 255, 255, 0.02); }
        .table-bordered th, .table-bordered td { border-color: #1f2937 !important; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🔍 自訂個資洩漏與 ZAP 腳本檢測 (PII Leakage & ZAP Scripting)</h2>
        <div>
            <span class="mr-3 text-muted">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 個資清單展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    📋 模擬校園敏感個資清單 (明文暴露)
                </div>
                <div class="card-body">
                    <p class="text-muted">本表格展示未進行遮罩去識別化的學生敏感個資，極易遭到 ZAP 掃描或爬蟲分析：</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm">
                            <thead>
                                <tr class="text-warning">
                                    <th>姓名</th>
                                    <th>學號</th>
                                    <th>身分證字號</th>
                                    <th>聯絡電話</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['name']) ?></td>
                                        <td><code><?= htmlspecialchars($s['sid']) ?></code></td>
                                        <td><code class="text-danger"><?= htmlspecialchars($s['id_card']) ?></code></td>
                                        <td><code class="text-danger"><?= htmlspecialchars($s['phone']) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>教學實作指引（二選一）：</strong><br>
                        <strong>🌟 方式 A（一鍵載入，最推薦）：</strong><br>
                        1. 點選 ZAP 左側視窗的 <strong>"Scripts (腳本)"</strong> 頁籤（若沒看到，點選綠色加號 `+` -> `Scripts`）。<br>
                        2. 在目錄樹的 <strong>"Passive Rules"</strong> 上點擊右鍵 -> <strong>"Load Script..." (載入腳本)</strong>。<br>
                        3. 選取本專案目錄中的 <strong><code>zap/pii_detector.js</code></strong> 檔案並開啟。<br>
                        4. 在該腳本按右鍵確認為 <strong>"Enable Script" (已啟用)</strong>。<br>
                        <hr class="my-2">
                        <strong>方式 B（手動建立與貼上代碼）：</strong><br>
                        1. 在 <strong>"Passive Rules"</strong> 上按右鍵 -> <strong>"New Script..."</strong>。<br>
                        2. 類型選 <code>Passive Rules</code>，腳本引擎選 <code>ECMAScript : Graal.js</code>（若舊版則選 <code>Oracle Nashorn</code>），點選「儲存」。<br>
                        3. 將右側的 JavaScript 代碼完整貼入編輯區，按 <code>Ctrl + S</code> 儲存。<br>
                        <hr class="my-2">
                        👉 <strong>驗證方式：</strong> 重新整理本網頁（按 <code>Ctrl + F5</code>），ZAP 的 <strong>Alerts (警報)</strong> 面板就會立即跳出由自訂腳本觸發的身分證與手機洩漏警報！
                    </div>
                </div>
            </div>
        </div>

        <!-- ZAP 腳本拷貝區 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-warning">
                <div class="card-header">
                    💻 ZAP 被動防護規則腳本 (JavaScript / Graal.js)
                </div>
                <div class="card-body">
                    <p class="text-muted">本腳本使用 ZAP 官方標準 <code>ps.raiseAlert()</code> API 撰寫：</p>
                    <pre><code class="language-javascript">function scan(ps, msg, src) {
    // 排除二進位圖片等，僅檢測文字回應
    if (!msg.getResponseHeader().isImage() && msg.getResponseBody().length() > 0) {
        var body = msg.getResponseBody().toString();
        var uri = msg.getRequestHeader().getURI().toString();
        
        // 1. 偵測台灣身分證字號 (英文字母 + 1或2 + 8碼數字)
        var idRegex = /[A-Z][12]\d{8}/g;
        var idMatch = idRegex.exec(body);
        if (idMatch) {
            ps.raiseAlert(
                2, // Risk: Medium (2)
                3, // Confidence: High (3)
                "自訂個資洩漏：偵測到身分證字號 (PII Leakage)", 
                "網頁回應中洩漏了未遮罩的明文身分證字號：" + idMatch[0], 
                uri, "", "", 
                "建議對身分證字號進行資料遮罩處理 (如 A12****789)。", 
                "落實資料庫輸出過濾與個資遮蔽 (Data Masking) 規範。", 
                idMatch[0], 359, 13, msg
            );
        }
        
        // 2. 偵測台灣手機號碼 (09開頭)
        var phoneRegex = /09\d{2}-?\d{3}-?\d{3}/g;
        var phoneMatch = phoneRegex.exec(body);
        if (phoneMatch) {
            ps.raiseAlert(
                2, // Risk: Medium (2)
                3, // Confidence: High (3)
                "自訂個資洩漏：偵測到手機號碼 (Phone Leakage)", 
                "網頁回應中洩漏了未遮罩的明文手機號碼：" + phoneMatch[0], 
                uri, "", "", 
                "建議對手機號碼進行去識別化處理 (如 0912-***-678)。", 
                "落實輸出編碼與機敏欄位隱碼政策。", 
                phoneMatch[0], 359, 13, msg
            );
        }
    }
}</code></pre>
                    <div class="mt-3">
                        <small class="text-muted">
                            📚 <strong>官方開發手冊與範例庫：</strong><br>
                            • <a href="https://www.zaproxy.org/docs/desktop/addons/script-console/" target="_blank" class="text-warning">ZAP 官方 Scripting 手冊</a><br>
                            • <a href="https://github.com/zaproxy/community-scripts" target="_blank" class="text-warning">ZAP Community Scripts 社群腳本範例庫</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
