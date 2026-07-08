<?php
require_once __DIR__ . '/../src/helpers.php';
// 檢查是否登入
check_login();

$submitted = false;
$username = '';
$card_no = '';
$national_id = '';
$email = '';
$ticket_type = '';
$quantity = '';

// 當使用不安全的 GET 提交時，敏感資料會完全洩漏在 URL 的 Query String 中！
if (isset($_GET['username']) && isset($_GET['card_no'])) {
    $submitted = true;
    $username = $_GET['username'] ?? '';
    $card_no = $_GET['card_no'] ?? '';
    $national_id = $_GET['national_id'] ?? '';
    $email = $_GET['email'] ?? '';
    $ticket_type = $_GET['ticket_type'] ?? '';
    $quantity = $_GET['quantity'] ?? '';
    $discount_rate = $_GET['discount_rate'] ?? '1.0';
    
    // 弱點版完全沒有後端校驗：
    // 1. Email 未驗證格式
    // 2. 票券類型未限制在下拉選單範疇中 (可被參數竄改)
    // 3. 數量未驗證是否為正整數數字 (可輸入英文引發異常)
    // 4. 折扣率選單未與後端白名單核對，直接參與計算，可透過負值或極低值實施參數竄改攻擊
    
    // 計算邏輯
    $ticket_price = 1000;
    if ($ticket_type === 'student') {
        $ticket_price = 500;
    } elseif ($ticket_type === 'vip') {
        $ticket_price = 5000;
    }
    
    // 弱點版：直接使用乘法計算，如果 quantity 或 discount_rate 被竄改為負數或惡意數值，後端直接接受計算
    $subtotal = $ticket_price * floatval($quantity);
    $total_price = $subtotal * floatval($discount_rate);
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📋 不安全 HTML 表單測試 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .card { margin-top: 30px; }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📋 不安全 HTML 表單風險演練 (Insecure HTML Form)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="row">
        <!-- 表單輸入區 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white font-weight-bold">
                    活動報名繳費註冊表單
                </div>
                <div class="card-body">
                    <!-- 漏洞點 1：表單使用不安全的 GET 方法傳輸，敏感資料（密碼、卡號）將出現在 URL 歷史紀錄與記錄中 -->
                    <!-- 漏洞點 2：表單缺少防範跨站請求偽造的 Anti-CSRF Token -->
                    <form method="GET" action="">
                        <div class="form-group">
                            <label for="username" class="font-weight-bold">自訂使用者帳號：</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="輸入帳號..." required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="font-weight-bold">設定登入密碼：</label>
                            <!-- 漏洞點 3：包含敏感密碼的輸入框明確啟用了自動完成 (autocomplete="on")，且缺少長度限制 -->
                            <input type="password" name="password" id="password" autocomplete="on" class="form-control" placeholder="輸入密碼..." required>
                        </div>

                        <div class="form-group">
                            <label for="email" class="font-weight-bold">備用聯絡信箱 (Email)：</label>
                            <!-- 漏洞點 4：前端使用 email 類型，但後端完全未驗證格式，可用工具發送非 Email 的任意字串 -->
                            <input type="email" name="email" id="email" class="form-control" placeholder="example@mail.com" required>
                        </div>

                        <div class="form-group">
                            <label for="ticket_type" class="font-weight-bold">票券種類 (下拉選單限制)：</label>
                            <!-- 漏洞點 5：前端限制了三個選項，但後端沒有白名單比對。攻擊者可修改 option value 為任意超值/免費值 -->
                            <select name="ticket_type" id="ticket_type" class="form-control">
                                <option value="general">一般票 (NT$1,000)</option>
                                <option value="student">學生票 (NT$500)</option>
                                <option value="vip">貴賓票 (限額) (NT$5,000)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="quantity" class="font-weight-bold">購票數量 (數值限制)：</label>
                            <!-- 漏洞點 6：前端使用 type="number"，但後端未確認型態是否為整數，可用工具傳入 "abc" 英文 -->
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" max="10" placeholder="1" required>
                        </div>

                        <div class="form-group">
                            <label for="discount_rate" class="font-weight-bold">身分折抵優惠 (下拉選單限制)：</label>
                            <!-- 漏洞點 9：前端限制折扣方案，但後端未做白名單過濾。攻擊者可將 value 篡改為負數 (如 -2.0) 進行負值套利或極低折扣 -->
                            <select name="discount_rate" id="discount_rate" class="form-control">
                                <option value="1.0">一般身分 (無折抵)</option>
                                <option value="0.9">本校校友 (9折)</option>
                                <option value="0.8">在校學生 (8折)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="national_id" class="font-weight-bold">身分證字號：</label>
                            <!-- 漏洞點 7：機敏身份識別欄位未關閉自動完成，且無 maxlength 限制 -->
                            <input type="text" name="national_id" id="national_id" autocomplete="on" class="form-control" placeholder="A123456789" required>
                        </div>

                        <div class="form-group">
                            <label for="card_no" class="font-weight-bold">活動信用卡付費卡號：</label>
                            <!-- 漏洞點 8：財務機敏欄位未關閉自動完成，容易殘留於共用電腦快取 -->
                            <input type="text" name="card_no" id="card_no" autocomplete="on" class="form-control" placeholder="4111-2222-3333-4444" required>
                        </div>

                        <button type="submit" class="btn btn-danger btn-block">🚀 傳送繳費與註冊 (GET)</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 說明與漏洞對照 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark font-weight-bold">
                    💡 HTML 表單配置安全風險指南
                </div>
                <div class="card-body">
                    <p>HTML 表單常見的組態與校驗錯誤包含：</p>
                    <ul>
                        <li class="mb-2">
                            <strong>後端資料校驗缺失 (Server-side Validation Missing)</strong>：<br>
                            開發者僅在 HTML 中使用 <code>type="email"</code> 或 <code>&lt;select&gt;</code> 來約束使用者輸入，但<strong>後端卻完全不對輸入內容重新校驗</strong>。攻擊者可以輕易使用 F12 竄改選項（如把一般票改為免費票值）、或利用 Burp Suite 傳入非 Email 字串與英文數量。
                        </li>
                        <li class="mb-2">
                            <strong>GET 方法傳輸機敏資料</strong>：<br>
                            使用 GET 提交時，密碼與卡號會完全暴露在 URL 網址列中，並殘留在瀏覽器歷史紀錄與伺服器 Access Log 中。
                        </li>
                        <li class="mb-2">
                            <strong>表單自動完成啟用 (Autocomplete Enabled)</strong>：<br>
                            密碼、信用卡、身分證等機敏欄位未設定 <code>autocomplete="off"</code> 時，瀏覽器會記錄輸入，在共用電腦環境下極易引發洩漏。
                        </li>
                    </ul>
                </div>
            </div>

            <?php if ($submitted): ?>
                <div class="card shadow-sm border-danger mt-3">
                    <div class="card-header bg-dark text-white font-weight-bold">
                        ⚠️ 提交資料確認 (後端接收狀況)
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger mb-3">
                            <strong>後端未過濾！所有欄位皆被篡改接受：</strong>
                        </div>
                        <table class="table table-bordered table-sm mb-0">
                            <tr><th>使用者帳號</th><td><?= htmlspecialchars($username) ?></td></tr>
                            <tr><th>備用信箱</th><td><code><?= htmlspecialchars($email) ?></code></td></tr>
                            <tr><th>票券類型</th><td><code><?= htmlspecialchars($ticket_type) ?></code></td></tr>
                            <tr><th>購買數量</th><td><code><?= htmlspecialchars($quantity) ?></code></td></tr>
                            <tr><th>折扣方案 (折扣率)</th><td><code><?= htmlspecialchars($discount_rate) ?></code></td></tr>
                            <tr><th>身分證字號</th><td><?= htmlspecialchars($national_id) ?></td></tr>
                            <tr><th>付款信用卡號</th><td><?= htmlspecialchars($card_no) ?></td></tr>
                            <tr class="bg-warning text-dark font-weight-bold">
                                <th>後端計算總額</th>
                                <td>NT$ <?= number_format($total_price) ?> 元</td>
                            </tr>
                        </table>
                        <small class="text-muted mt-2 d-block">試著按 F12 修改 HTML 下拉選單折扣比率的 <code>value</code> (例如改為 <code>-2.0</code> 或 <code>0.01</code>)、或將 <code>quantity=abc</code> 發送，觀察後端是否接受並產生計算異常。</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
