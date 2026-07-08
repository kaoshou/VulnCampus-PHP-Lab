<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

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
    <title>🛡️ 自訂個資遮罩防禦驗證 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background-color: #f8fafc; color: #1e293b; font-family: 'Noto Sans TC', sans-serif; padding-top: 50px; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-top: 30px; }
        pre { background-color: #0f172a !important; border: 1px solid #e2e8f0; color: #38bdf8 !important; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">🛡️ 自訂個資遮罩防禦驗證 (PII Data Masking Check)</h2>
        <div>
            <span class="mr-3 text-muted">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 個資清單展示 -->
        <div class="col-md-6">
            <div class="card shadow-sm border border-success">
                <div class="card-header bg-success text-white py-3 font-weight-bold">
                    🛡️ 學生敏感個資清單 (已安全遮罩去識別化)
                </div>
                <div class="card-body">
                    <p class="text-muted">本安全修正版網頁對身分證、電話等高危敏感個資進行了去識別化星號掩碼（Data Masking）：</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-sm">
                            <thead>
                                <tr class="text-primary">
                                    <th>姓名</th>
                                    <th>學號</th>
                                    <th>身分證字號</th>
                                    <th>聯絡電話</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $s): ?>
                                    <tr>
                                        <td><?= h(substr($s['name'], 0, 3) . '*' . substr($s['name'], -3)) ?></td>
                                        <td><code><?= h($s['sid']) ?></code></td>
                                        <td><code class="text-success"><?= h(mask_data('national_id', $s['id_card'])) ?></code></td>
                                        <td><code class="text-success"><?= h(mask_data('phone', $s['phone'])) ?></code></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>安全防禦與驗證原理：</strong><br>
                        1. **資料去識別化**：後端程式碼在輸出至 HTML 渲染前，使用遮罩函式 <code>mask_data</code> 對身分證號 (`A123***789`) 與手機號 (`0912-***-678`) 的敏感段落替換為星號。<br>
                        2. **阻斷 ZAP 被動腳本匹配**：當您在安全版（Port 8081）執行掃描或瀏覽時，ZAP 的自訂被動腳本 `PII_Detector` 由於無法匹配到真實的個資特徵規則（`[A-Z][12]\d{8}`），將**完全不會觸發警報**，驗證了個資遮罩防禦機制的卓越效果！
                    </div>
                </div>
            </div>
        </div>

        <!-- ZAP 腳本說明 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header py-3 font-weight-bold">
                    💻 ZAP 被動防護規則腳本 (對比測試)
                </div>
                <div class="card-body">
                    <p class="text-muted">安全版亦提供與弱點版相同的 ZAP 被動腳本代碼供學員下載，以在 ZAP 內同時加入並對比兩個站點的掃描警報差異：</p>
                    <pre><code class="language-javascript">// ZAP被動掃描自訂腳本 - 偵測台灣身分證與手機號碼
function scan(ps, msg, src) {
    var body = msg.getResponseBody().toString();
    
    // 1. 偵測台灣身分證字號
    var idRegex = /[A-Z][12]\d{8}/g;
    var idMatch = idRegex.exec(body);
    if (idMatch !== null) {
        raiseZapAlert(ps, msg, 10091, "自訂個資洩漏：偵測到身分證字號", 
                      "網頁回應中洩漏了明文身分證字號：" + idMatch[0], idMatch[0]);
    }
    
    // 2. 偵測台灣手機號碼
    var phoneRegex = /09\d{2}-\d{3}-\d{3}|09\d{8}/g;
    var phoneMatch = phoneRegex.exec(body);
    if (phoneMatch !== null) {
        raiseZapAlert(ps, msg, 10092, "自訂個資洩漏：偵測到手機號碼", 
                      "網頁回應中洩漏了明文手機號碼：" + phoneMatch[0], phoneMatch[0]);
    }
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
