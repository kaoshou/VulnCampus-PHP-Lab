<?php
require_once __DIR__ . '/../src/helpers.php';
// 權限檢查：確保登入
check_auth();

$submitted = false;
$error = '';
$success = '';
$username = '';
$card_no = '';
$national_id = '';
$email = '';
$ticket_type = '';
$quantity = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 安全防禦 1：驗證 CSRF Token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $error = 'CSRF Token 驗證失敗，請求已被安全阻斷！';
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $national_id = $_POST['national_id'] ?? '';
        $card_no = $_POST['card_no'] ?? '';
        $email = $_POST['email'] ?? '';
        $ticket_type = $_POST['ticket_type'] ?? '';
        $quantity = $_POST['quantity'] ?? '';
        $discount_rate = $_POST['discount_rate'] ?? '1.0';

        // 安全防禦 2：後端嚴格校驗欄位長度、型態與格式 (防範任何參數竄改)
        $valid_tickets = ['general', 'student', 'vip'];
        $allowed_discounts = ['1.0', '0.9', '0.8'];
        
        if (strlen($username) > 30) {
            $error = '註冊失敗：帳號長度不得大於 30 字元';
        } else if (strlen($password) < 6 || strlen($password) > 50) {
            $error = '註冊失敗：密碼長度必須介於 6 到 50 字元';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Email 格式校驗
            $error = '註冊失敗：電子郵件格式錯誤';
        } else if (!in_array($ticket_type, $valid_tickets)) {
            // 下拉選單白名單校驗 (防止參數竄改)
            $error = '註冊失敗：無效的票券種類選擇';
        } else if (!ctype_digit($quantity) || (int)$quantity < 1 || (int)$quantity > 10) {
            // 數量型態與範圍校驗 (必須為正整數)
            $error = '註冊失敗：購票數量必須為 1 到 10 之間的整數數字';
        } else if (!in_array($discount_rate, $allowed_discounts)) {
            // 折扣下拉選單白名單校驗 (防止折扣率參數竄改)
            $error = '註冊失敗：無效的折扣率選擇';
        } else if (!preg_match('/^[A-Z][12][0-9]{8}$/', $national_id)) {
            // 身分證字號格式檢驗
            $error = '註冊失敗：身分證字號格式錯誤';
        } else if (!preg_match('/^[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{4}$/', $card_no)) {
            // 信用卡格式檢驗
            $error = '註冊失敗：信用卡卡號格式錯誤 (必須為 xxxx-xxxx-xxxx-xxxx)';
        } else {
            $submitted = true;
            $success = '表單提交成功！資料已通過安全校驗。';
            
            // 安全計算邏輯
            $ticket_price = 1000;
            if ($ticket_type === 'student') {
                $ticket_price = 500;
            } elseif ($ticket_type === 'vip') {
                $ticket_price = 5000;
            }
            $subtotal = $ticket_price * intval($quantity);
            $total_price = $subtotal * floatval($discount_rate);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>📋 HTML 表單安全防禦 - VulnCampus</title>
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
        <h2 class="text-primary">📋 HTML 表單安全配置與防禦</h2>
        <div>
            <span class="me-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= h($success) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 表單輸入區 -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white font-weight-bold">
                    活動報名繳費註冊表單 (安全防禦版)
                </div>
                <div class="card-body">
                    <!-- 安全防禦 3：使用安全的 POST 方法進行敏感資料提交 -->
                    <form method="POST" action="">
                        <!-- 安全防禦 4：加入 Anti-CSRF Token，防止跨站請求偽造 -->
                        <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">

                        <div class="mb-3">
                            <label for="username" class="form-label font-weight-bold">自訂使用者帳號：</label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="輸入帳號..." maxlength="30" required value="<?= h($username) ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label font-weight-bold">設定登入密碼：</label>
                            <!-- 安全防禦 5：使用 autocomplete="new-password" 限制瀏覽器儲存或自動填入密碼，並設定 maxlength -->
                            <input type="password" name="password" id="password" autocomplete="new-password" class="form-control" placeholder="輸入密碼..." maxlength="50" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label font-weight-bold">備用聯絡信箱 (Email)：</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="example@mail.com" required value="<?= h($email) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="ticket_type" class="form-label font-weight-bold">票券種類 (下拉選單限制)：</label>
                            <select name="ticket_type" id="ticket_type" class="form-select">
                                <option value="general" <?= $ticket_type === 'general' ? 'selected' : '' ?>>一般票 (NT$1,000)</option>
                                <option value="student" <?= $ticket_type === 'student' ? 'selected' : '' ?>>學生票 (NT$500)</option>
                                <option value="vip" <?= $ticket_type === 'vip' ? 'selected' : '' ?>>貴賓票 (限額) (NT$5,000)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="quantity" class="form-label font-weight-bold">購票數量 (數值限制)：</label>
                            <input type="number" name="quantity" id="quantity" class="form-control" min="1" max="10" placeholder="1" required value="<?= h($quantity) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="discount_rate" class="form-label font-weight-bold">身分折抵優惠 (下拉選單限制)：</label>
                            <!-- 安全防禦：後端配合白名單限制，即使前端 F12 修改，後端亦會直接拒絕無效的折扣率 -->
                            <select name="discount_rate" id="discount_rate" class="form-select">
                                <option value="1.0" <?= $discount_rate === '1.0' ? 'selected' : '' ?>>一般身分 (無折抵)</option>
                                <option value="0.9" <?= $discount_rate === '0.9' ? 'selected' : '' ?>>本校校友 (9折)</option>
                                <option value="0.8" <?= $discount_rate === '0.8' ? 'selected' : '' ?>>在校學生 (8折)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="national_id" class="form-label font-weight-bold">身分證字號：</label>
                            <!-- 安全防禦 6：使用 autocomplete="off" 關閉敏感身份自動完成，並設定 maxlength 限制輸入長度 -->
                            <input type="text" name="national_id" id="national_id" autocomplete="off" class="form-control" placeholder="A123456789" maxlength="10" required value="<?= h($national_id) ?>">
                        </div>

                        <div class="mb-3">
                            <label for="card_no" class="form-label font-weight-bold">活動信用卡付費卡號：</label>
                            <!-- 安全防禦 7：使用 autocomplete="off" 停用敏感財務欄位自動完成，並設定 maxlength 限制格式長度 -->
                            <input type="text" name="card_no" id="card_no" autocomplete="off" class="form-control" placeholder="4111-2222-3333-4444" maxlength="19" required value="<?= h($card_no) ?>">
                        </div>

                        <button type="submit" class="btn btn-success w-100">🚀 傳送繳費與註冊 (POST)</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 說明與漏洞對照 -->
        <div class="col-md-6">
            <div class="card shadow-sm border-success border-2">
                <div class="card-header bg-success text-white font-weight-bold">
                    🛡️ HTML 表單防禦機制說明
                </div>
                <div class="card-body">
                    <p>在安全修正版中，我們對表單進行了以下安全優化與修補：</p>
                    <ol class="mb-0">
                        <li class="mb-2"><strong>改用 POST 方法</strong>：確保敏感資料不會暴露在 URL 網址列中。</li>
                        <li class="mb-2"><strong>後端嚴格校驗 (Email, 選項, 型態)</strong>：後端主動調用 <code>filter_var</code> 驗證郵件格式；對下拉選單選項使用白名單陣列核對（拒絕參數竄改）；對數量使用 <code>ctype_digit()</code> 確認其為正整數數字型態，阻止非數值注入。</li>
                        <li class="mb-2"><strong>停用 Autocomplete</strong>：限制瀏覽器個資快取。</li>
                        <li class="mb-2"><strong>實施 Anti-CSRF Token</strong>：阻止跨站請求偽造。</li>
                    </ol>
                </div>
            </div>

            <?php if ($submitted): ?>
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-dark text-white font-weight-bold">
                        🟢 提交資料確認 (個資安全遮蔽)
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success">
                            資料傳送成功！且 URL 網址列沒有殘留任何敏感數據。
                        </div>
                        <table class="table table-bordered table-sm mb-0">
                            <!-- 顯示遮蔽後的個資 -->
                            <tr><th>使用者帳號</th><td><?= h($username) ?></td></tr>
                            <tr><th>備用信箱</th><td><code><?= h($email) ?></code></td></tr>
                            <tr><th>票券類型</th><td><code><?= h($ticket_type) ?></code></td></tr>
                            <tr><th>購買數量</th><td><code><?= h($quantity) ?></code></td></tr>
                            <tr><th>折扣比率</th><td><code><?= h($discount_rate) ?></code></td></tr>
                            <tr><th>身分證字號</th><td><?= h(mask_data('national_id', $national_id)) ?></td></tr>
                            <tr><th>付款信用卡號</th><td><?= h(substr($card_no, 0, 5) . '****-****-' . substr($card_no, -4)) ?></td></tr>
                            <tr class="table-success font-weight-bold">
                                <th>計算總額</th>
                                <td>NT$ <?= number_format($total_price) ?> 元 (已安全核算)</td>
                            </tr>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
