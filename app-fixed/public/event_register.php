<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 檢查登入
check_auth();

$user_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// ==========================================
// 1. 處理取消報名 (修補重點：IDOR 水平越權與 CSRF 防護)
// ==========================================
if (isset($_GET['action']) && $_GET['action'] === 'cancel') {
    $registration_id = intval($_GET['registration_id'] ?? 0);
    $csrf_token = $_GET['csrf_token'] ?? '';
    
    // 驗證 CSRF Token
    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        die("安全防護拒絕：無效的 CSRF Token");
    }

    try {
        // 先查出這筆報名紀錄，確認擁有者
        $stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE id = :id");
        $stmt->execute(['id' => $registration_id]);
        $reg = $stmt->fetch();
        
        if ($reg) {
            // 修補重點：嚴格檢查擁有權，一般用戶只能取消自己的報名
            if ($reg['user_id'] !== $user_id && $_SESSION['user']['role'] !== 'admin') {
                write_audit_log($pdo, "越權取消嘗試：試圖取消報名編號 $registration_id");
                http_response_code(403);
                die("權限不足：您無權取消他人的報名");
            }
            
            // 開始事務以返還名額與更新狀態
            $pdo->beginTransaction();
            
            // 設為已取消
            $update_stmt = $pdo->prepare("UPDATE event_registrations SET status = 'cancelled' WHERE id = :id");
            $update_stmt->execute(['id' => $registration_id]);
            
            // 返還名額
            if ($reg['status'] !== 'cancelled') {
                $return_stmt = $pdo->prepare("UPDATE events SET quota = quota + :qty WHERE id = :event_id");
                $return_stmt->execute([
                    'qty' => $reg['quantity'],
                    'event_id' => $reg['event_id']
                ]);
            }
            
            $pdo->commit();
            write_audit_log($pdo, "取消報名成功 (報名編號: $registration_id)");
            
            header("Location: /events.php");
            exit;
        } else {
            $error = '找不到該筆報名紀錄。';
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Cancel registration failed: " . $e->getMessage());
        $error = '取消報名失敗，請聯絡管理員。';
    }
}

// ==========================================
// 2. 處理新增報名
// ==========================================
$event_id = intval($_GET['event_id'] ?? ($_POST['event_id'] ?? 0));
if ($event_id <= 0) {
    header("Location: /events.php");
    exit;
}

// 獲取活動詳情
try {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :id");
    $stmt->execute(['id' => $event_id]);
    $event = $stmt->fetch();
    if (!$event) {
        header("Location: /events.php");
        exit;
    }
} catch (PDOException $e) {
    error_log("Get event detail failed: " . $e->getMessage());
    header("Location: /events.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 驗證 CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        http_response_code(403);
        $error = '安全防護拒絕：無效的 CSRF Token';
    } else {
        // 修補重點：校驗報名數量必須為「大於 0 的正整數」，防堵輸入負數
        $quantity = intval($_POST['quantity'] ?? 1);
        $coupon_code = trim($_POST['coupon_code'] ?? '');

        if ($quantity <= 0) {
            $error = '報名數量必須大於 0！';
        } else {
            try {
                // 修補重點 4：使用資料庫事務鎖防範 Race Condition (併發超額報名)
                $pdo->beginTransaction();
                
                // 使用 FOR UPDATE 鎖定 events 該行資料，防止併發讀取造成超賣
                $lock_stmt = $pdo->prepare("SELECT quota, price FROM events WHERE id = :id FOR UPDATE");
                $lock_stmt->execute(['id' => $event_id]);
                $locked_event = $lock_stmt->fetch();
                
                if ($locked_event['quota'] < $quantity) {
                    throw new Exception('報名失敗：賸餘名額不足！');
                }
                
                // 修補重點：單價一律以資料庫查出之金額為準 ($locked_event['price'])，不信任前端傳送之 price 參數
                $db_price = $locked_event['price'];
                
                // 校驗優惠券
                $discount = 0;
                if ($coupon_code !== '') {
                    // 使用預處理查詢優惠券
                    $coupon_stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = :code FOR UPDATE");
                    $coupon_stmt->execute(['code' => $coupon_code]);
                    $coupon = $coupon_stmt->fetch();
                    
                    if ($coupon) {
                        // 檢查優惠券使用上限
                        if ($coupon['used_count'] >= $coupon['max_uses']) {
                            throw new Exception('優惠券已達使用次數上限！');
                        }
                        $discount = $coupon['discount_amount'];
                        
                        // 累加優惠券使用次數
                        $up_coupon_stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = :id");
                        $up_coupon_stmt->execute(['id' => $coupon['id']]);
                    } else {
                        throw new Exception('無效的優惠券代碼！');
                    }
                }
                
                // 後端計算總金額，不接受前端傳入的金額，且若折扣完為負值則設為 0
                $final_price = ($db_price * $quantity) - $discount;
                if ($final_price < 0) {
                    $final_price = 0;
                }
                
                // 扣減名額
                $sub_quota_stmt = $pdo->prepare("UPDATE events SET quota = quota - :qty WHERE id = :id");
                $sub_quota_stmt->execute([
                    'qty' => $quantity,
                    'id' => $event_id
                ]);
                
                // 寫入報名紀錄
                $ins_stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, quantity, coupon_code, final_price, status) 
                                           VALUES (:event_id, :user_id, :quantity, :coupon_code, :final_price, 'registered')");
                $ins_stmt->execute([
                    'event_id' => $event_id,
                    'user_id' => $user_id,
                    'quantity' => $quantity,
                    'coupon_code' => $coupon_code !== '' ? $coupon_code : null,
                    'final_price' => $final_price
                ]);
                
                $pdo->commit();
                write_audit_log($pdo, "活動報名成功 (活動ID: $event_id, 數量: $quantity, 折抵後價格: $final_price)");
                
                header("Location: /events.php");
                exit;
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>活動報名確認 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">確認活動報名</h2>
        <a href="/events.php" class="btn btn-secondary">返回活動列表</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>

    <div class="card col-md-8 mx-auto shadow-sm border-0">
        <div class="card-header bg-success text-white font-weight-bold py-3">
            報名活動：<?= h($event['title']) ?>
        </div>
        <div class="card-body p-4">
            <p class="text-muted"><?= h($event['description']) ?></p>
            <hr>
            
            <form method="POST" action="event_register.php">
                <!-- 帶入 CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= get_csrf_token() ?>">
                <input type="hidden" name="event_id" value="<?= h($event['id']) ?>">
                
                <!-- 安全版：移除 hidden price 傳值，所有價格依據後端查詢 -->

                <div class="form-group row mb-3">
                    <label class="col-sm-4 col-form-label font-weight-bold">活動單價：</label>
                    <div class="col-sm-8">
                        <span class="text-danger font-weight-bold"><?= h($event['price']) ?> 元</span>
                    </div>
                </div>

                <div class="form-group row mb-3">
                    <label class="col-sm-4 col-form-label font-weight-bold">當前賸餘名額：</label>
                    <div class="col-sm-8">
                        <span><?= h($event['quota']) ?> 人</span>
                    </div>
                </div>

                <div class="form-group row mb-3">
                    <label for="quantity" class="col-sm-4 col-form-label font-weight-bold">報名數量：</label>
                    <div class="col-sm-8">
                        <input type="number" name="quantity" id="quantity" class="form-control col-md-4" value="1" min="1" required>
                    </div>
                </div>

                <div class="form-group row mb-4">
                    <label for="coupon_code" class="col-sm-4 col-form-label font-weight-bold">折扣優惠券：</label>
                    <div class="col-sm-8">
                        <input type="text" name="coupon_code" id="coupon_code" class="form-control col-md-6" placeholder="如 CAMPUS100">
                        <small class="form-text text-muted">可填入：<code>CAMPUS100</code> (折抵 100 元) 或 <code>FREE999</code> (折抵 999 元)</small>
                    </div>
                </div>

                <div class="text-end border-top pt-3">
                    <button type="submit" class="btn btn-lg btn-success font-weight-bold">確認送出報名</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
