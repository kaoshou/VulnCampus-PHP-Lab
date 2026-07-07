<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 檢查是否登入
check_login();

// 獲取欲檢視的用戶 ID。若未提供，則預設為當前登入者 ID
// 教學用弱點 1：IDOR (水平越權)。後端直接相信 URL 傳入的 id，且未校驗是否為當前登入者本人
$id = $_GET['id'] ?? $_SESSION['user']['id'];

$error = '';
$success = '';

// 處理修改資料
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $student_no = $_POST['student_no'] ?? '';
    $national_id_fake = $_POST['national_id_fake'] ?? '';
    // 教學用弱點 2：表單參數竄改 / Mass Assignment。後端直接接收前端傳入的 role 欄位並更新到資料庫
    $role = $_POST['role'] ?? 'student'; 

    try {
        // 教學用弱點 3：SQL 注入。此處直接拼接更新
        $sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', student_no = '$student_no', national_id_fake = '$national_id_fake', role = '$role' WHERE id = $id";
        $pdo->exec($sql);
        
        $success = '資料修改成功！';
        
        // 如果更新的是自己，同步更新 Session
        if ($id == $_SESSION['user']['id']) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['role'] = $role;
        }
    } catch (PDOException $e) {
        $error = '更新失敗：' . $e->getMessage();
    }
}

// 獲取該 ID 的使用者資料
try {
    $stmt = $pdo->query("SELECT * FROM users WHERE id = $id");
    $profile = $stmt->fetch();
    
    if (!$profile) {
        $error = '找不到該使用者。';
    }
} catch (PDOException $e) {
    $error = '資料庫錯誤：' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>個人資料修改 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>👤 個人資料修改 (IDOR 越權與參數竄改測試)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= $_SESSION['user']['name'] ?></strong> (身分: <?= $_SESSION['user']['role'] ?>)</span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if (!empty($profile)): ?>
        <div class="card col-md-8 mx-auto shadow-sm">
            <div class="card-header bg-primary text-white font-weight-bold">
                修改個人資料 (當前正在檢視 ID: <?= $profile['id'] ?> 的檔案)
            </div>
            <div class="card-body">
                <form method="POST">
                    <!-- 教學用弱點 4：將 role 當作 hidden 欄位傳遞，攻擊者可透過修改 role=admin 獲得系統管理員權限 -->
                    <input type="hidden" name="role" value="<?= $profile['role'] ?>">

                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">帳號名稱：</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" readonly value="<?= $profile['username'] ?>">
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label for="name" class="col-sm-3 col-form-label font-weight-bold">姓名：</label>
                        <div class="col-sm-9">
                            <input type="text" name="name" id="name" class="form-control" value="<?= $profile['name'] ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="email" class="col-sm-3 col-form-label font-weight-bold">電子郵件：</label>
                        <div class="col-sm-9">
                            <!-- 教學用弱點 5：未做敏感個資遮罩，且可輕易被 IDOR 窺探 -->
                            <input type="email" name="email" id="email" class="form-control" value="<?= $profile['email'] ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="phone" class="col-sm-3 col-form-label font-weight-bold">聯絡電話：</label>
                        <div class="col-sm-9">
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= $profile['phone'] ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="student_no" class="col-sm-3 col-form-label font-weight-bold">學號/教工號：</label>
                        <div class="col-sm-9">
                            <input type="text" name="student_no" id="student_no" class="form-control" value="<?= $profile['student_no'] ?>">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label for="national_id_fake" class="col-sm-3 col-form-label font-weight-bold">身分證字號：</label>
                        <div class="col-sm-9">
                            <input type="text" name="national_id_fake" id="national_id_fake" class="form-control" value="<?= $profile['national_id_fake'] ?>">
                        </div>
                    </div>
                    
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">目前系統角色：</label>
                        <div class="col-sm-9">
                            <span class="badge badge-warning p-2"><?= $profile['role'] ?></span>
                            <small class="form-text text-muted">提示：利用 ZAP 攔截並修改隱藏欄位 <code>role</code> 的值為 <code>admin</code> 試試看！</small>
                        </div>
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-success">更新基本資料</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="/api/profile.php?id=<?= $profile['id'] ?>" target="_blank" class="btn btn-link">🔍 查看本頁的 API 資料暴露 (api/profile.php)</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
