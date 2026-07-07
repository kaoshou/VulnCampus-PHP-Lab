<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

check_login();

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// ==========================================
// 1. 處理取消報名 (弱點版：越權取消 + 無 CSRF 防護)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    $registration_id = $_GET['registration_id'] ?? '';
    if ($registration_id !== '') {
        try {
            // 教學用弱點 1：Broken Access Control / IDOR。後端未校驗該報名紀錄是否屬於 $user_id 本人
            // 只要修改 URL 中的 registration_id 便可惡意取消其他同學的報名
            $pdo->exec("UPDATE event_registrations SET status = 'cancelled' WHERE id = " . $registration_id);
            
            header("Location: /events.php");
            exit;
        } catch (PDOException $e) {
            $error = '取消失敗：' . $e->getMessage();
        }
    }
}

// ==========================================
// 2. 處理新增報名
// ==========================================
$event_id = $_GET['event_id'] ?? ($_POST['event_id'] ?? '');
if (!$event_id) {
    header("Location: /events.php");
    exit;
}

// 獲取活動詳情
try {
    $stmt = $pdo->query("SELECT * FROM events WHERE id = " . $event_id);
    $event = $stmt->fetch();
    if (!$event) {
        header("Location: /events.php");
        exit;
    }
} catch (PDOException $e) {
    $error = $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 教學用弱點 2：表單參數竄改。直接接收前端傳入的 price
    $price = $_POST['price'] ?? 0;
    
    // 教學用弱點 3：安全設計缺陷 / Insecure Design。未校驗數量為正數。可輸入負數報名以扣減應付金額
    $quantity = intval($_POST['quantity'] ?? 1); 
    
    $coupon_code = $_POST['coupon_code'] ?? '';

    // 計算優惠券折扣
    $discount = 0;
    if ($coupon_code !== '') {
        try {
            // 弱點版：直接字串拼接，且沒有校驗優惠券的使用限制與次數
            $c_stmt = $pdo->query("SELECT * FROM coupons WHERE code = '$coupon_code'");
            $coupon = $c_stmt->fetch();
            if ($coupon) {
                $discount = $coupon['discount_amount'];
                // 弱點版：沒有更新 used_count，也沒有檢查 max_uses 限制
            } else {
                $error = '優惠券代碼無效！';
            }
        } catch (PDOException $e) {
            $error = '優惠券查詢出錯';
        }
    }

    if (!$error) {
        // 教學用弱點 4：後端直接相信前端傳入的 price 與無限制的數量進行總價計算，並允許金額為負
        $final_price = ($price * $quantity) - $discount;

        try {
            // 教學用弱點 5：Race Condition / 併發漏洞。後端在檢查剩餘名額時，未做任何資料庫交易鎖定 (行鎖)
            // 高併發下，會導致報名人數超出活動 quota 上限
            $current_quota = $event['quota'];
            
            if ($current_quota < $quantity && $quantity > 0) {
                $error = '報名失敗：剩餘名額不足！';
            } else {
                // 寫入報名紀錄 (弱點版：未防範 SQLi)
                $sql = "INSERT INTO event_registrations (event_id, user_id, quantity, coupon_code, final_price, status) VALUES ($event_id, $user_id, $quantity, '$coupon_code', $final_price, 'registered')";
                $pdo->exec($sql);

                // 更新活動剩餘名額 (若數量為負數，反而會增加名額，這也是邏輯缺陷)
                $pdo->exec("UPDATE events SET quota = quota - $quantity WHERE id = $event_id");

                header("Location: /events.php");
                exit;
            }
        } catch (PDOException $e) {
            $error = '報名失敗：' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>活動報名確認 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>確認活動報名</h2>
        <a href="/events.php" class="btn btn-secondary">返回活動列表</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <div class="card col-md-8 mx-auto shadow-sm">
        <div class="card-header bg-success text-white font-weight-bold">
            報名活動：<?= $event['title'] ?>
        </div>
        <div class="card-body">
            <p class="text-muted"><?= $event['description'] ?></p>
            <hr>
            
            <form method="POST" action="event_register.php">
                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                
                <!-- 教學用弱點 6：前端傳遞單價 price，後端未校驗是否與 DB 中的單價相符 -->
                <input type="hidden" name="price" value="<?= $event['price'] ?>">

                <div class="form-group row">
                    <label class="col-sm-4 col-form-label font-weight-bold">活動單價：</label>
                    <div class="col-sm-8">
                        <span class="text-danger font-weight-bold"><?= $event['price'] ?> 元</span>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-sm-4 col-form-label font-weight-bold">當前賸餘名額：</label>
                    <div class="col-sm-8">
                        <span><?= $event['quota'] ?> 人</span>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="quantity" class="col-sm-4 col-form-label font-weight-bold">報名數量：</label>
                    <div class="col-sm-8">
                        <input type="number" name="quantity" id="quantity" class="form-control col-md-4" value="1">
                        <small class="form-text text-muted">提示：試著輸入 <code>-1</code> 或是用 ZAP Breakpoint 修改參數！</small>
                    </div>
                </div>

                <div class="form-group row">
                    <label for="coupon_code" class="col-sm-4 col-form-label font-weight-bold">折扣優惠券：</label>
                    <div class="col-sm-8">
                        <input type="text" name="coupon_code" id="coupon_code" class="form-control col-md-6" placeholder="如 CAMPUS100">
                        <small class="form-text text-muted">可填入：<code>CAMPUS100</code> (折抵 100 元) 或 <code>FREE999</code> (折抵 999 元)</small>
                    </div>
                </div>

                <div class="text-right border-top pt-3">
                    <button type="submit" class="btn btn-lg btn-success">確認送出報名</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
