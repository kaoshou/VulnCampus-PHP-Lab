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
                        💡 <strong>教學步驟指引：</strong><br>
                        1. 開啟 <strong>OWASP ZAP</strong>。<br>
                        2. 點選 ZAP 上方的 <strong>"Scripts"</strong> 頁籤（若沒看到，點選綠色加號 `+` -> `Scripts`）。<br>
                        3. 在 `Scripts` 目錄樹的 <strong>"Passive Rules"</strong> 上點擊右鍵 -> <strong>"New Script..."</strong>。<br>
                        4. 腳本名稱填入 `PII_Detector`，類型選 `Passive Rules`，腳本引擎選 `Oracle Nashorn` (或 `Graal.js`)，範本選 `Empty`。<br>
                        5. 將右側的 JavaScript 程式碼貼入腳本編輯區，點選儲存 (Save)，並點擊右鍵選擇 <strong>"Enable Script"</strong> 啟用。<br>
                        6. 用瀏覽器重新載入此網頁，觀察 ZAP 的 <strong>"Alerts (警報)"</strong> 面板，您將會看見由我們自訂腳本發出的身分證與手機洩漏警報！
                    </div>
                </div>
            </div>
        </div>

        <!-- ZAP 腳本拷貝區 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-warning">
                <div class="card-header">
                    💻 ZAP 被動防護規則腳本 (JavaScript)
                </div>
                <div class="card-body">
                    <p class="text-muted">複製下方代碼並貼入 ZAP 的被動掃描腳本（Passive Rules）中：</p>
                    <pre><code class="language-javascript">// ZAP被動掃描自訂腳本 - 偵測台灣身分證與手機號碼
function scan(ps, msg, src) {
    // 取得網頁回應的 HTML 內容
    var body = msg.getResponseBody().toString();
    
    // 1. 偵測台灣身分證字號 (字母 + 1或2 + 8位數字)
    var idRegex = /[A-Z][12]\d{8}/g;
    var idMatch = idRegex.exec(body);
    if (idMatch !== null) {
        raiseZapAlert(ps, msg, 10091, "自訂個資洩漏：偵測到身分證字號", 
                      "網頁回應中洩漏了明文身分證字號：" + idMatch[0], idMatch[0]);
    }
    
    // 2. 偵測台灣手機號碼 (09開頭 + 8位數字，或帶有連字號)
    var phoneRegex = /09\d{2}-\d{3}-\d{3}|09\d{8}/g;
    var phoneMatch = phoneRegex.exec(body);
    if (phoneMatch !== null) {
        raiseZapAlert(ps, msg, 10092, "自訂個資洩漏：偵測到手機號碼", 
                      "網頁回應中洩漏了明文手機號碼：" + phoneMatch[0], phoneMatch[0]);
    }
}

// 建立警報輔助函式
function raiseZapAlert(ps, msg, id, name, desc, evidence) {
    var alert = new org.parosproxy.paros.core.scanner.Alert(
        id,
        org.parosproxy.paros.core.scanner.Alert.RISK_INFO, // 資訊等級
        org.parosproxy.paros.core.scanner.Alert.CONFIDENCE_MEDIUM,
        name
    );
    alert.setDescription(desc);
    alert.setEvidence(evidence);
    alert.setUri(msg.getRequestHeader().getURI().toString());
    ps.parent.raiseAlert(alert);
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
