<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

// 檢查是否登入
check_auth();

// 修補重點 1：IDOR 越權防範。確認查詢的 ID 是否為用戶本人，除非是 admin 角色
$id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user']['id'];

if ($id !== $_SESSION['user']['id'] && $_SESSION['user']['role'] !== 'admin') {
    // 寫入越權嘗試稽核日誌
    write_audit_log($pdo, "越權存取嘗試：試圖讀取 ID $id 的個人檔案");
    
    http_response_code(403);
    echo '<div style="font-family: sans-serif; text-align: center; margin-top: 50px;">';
    echo '  <h2>權限不足 (403 Forbidden)</h2>';
    echo '  <p>您無權檢視或修改其他使用者的個人資料。</p>';
    echo '  <p><a href="/profile.php">回我自己的資料頁</a> | <a href="/index.php">回首頁</a></p>';
    echo '</div>';
    exit;
}

$error = '';
$success = '';

// 處理修改資料
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $student_no = trim($_POST['student_no'] ?? '');
    $national_id_fake = trim($_POST['national_id_fake'] ?? '');
    
    // 修補重點 2：Mass Assignment 防禦。後端完全忽略前端 POST 傳入的 role 隱藏欄位，由後端邏輯決定角色
    // 此處不從 $_POST['role'] 讀取值寫入 SQL

    if ($name === '' || $email === '') {
        $error = '姓名與電子郵件為必填欄位。';
    } else {
        try {
            // 修補重點 3：參數化查詢，並排除 role 欄位更新，防範 Mass Assignment 越權升權
            $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email, phone = :phone, student_no = :student_no, national_id_fake = :national_id_fake WHERE id = :id");
            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'student_no' => $student_no,
                'national_id_fake' => $national_id_fake,
                'id' => $id
            ]);
            
            $success = '個人資料更新成功！';
            
            // 同步更新當前 Session 名稱 (若是修改自己的話)
            if ($id === $_SESSION['user']['id']) {
                $_SESSION['user']['name'] = $name;
            }
            
            write_audit_log($pdo, "更新個人資料 (標的 ID: $id)");
        } catch (PDOException $e) {
            error_log("Update profile failed: " . $e->getMessage());
            $error = '資料更新失敗，請稍後再試。';
        }
    }
}

// 獲取使用者資料
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        $error = '找不到該使用者。';
    }
} catch (PDOException $e) {
    error_log("Select profile error: " . $e->getMessage());
    $error = '系統發生異常，請稍後再試。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>個人資料修改 (安全版) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">👤 個人資料修改 (安全版)</h2>
        <div>
            <span class="mr-3">您好，<strong><?= h($_SESSION['user']['name']) ?></strong> (身分: <span class="badge bg-primary"><?= h($_SESSION['user']['role']) ?></span>)</span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= h($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success py-2"><?= h($success) ?></div>
    <?php endif; ?>

    <?php if (!empty($profile)): ?>
        <div class="card col-md-8 mx-auto shadow-sm border-0">
            <div class="card-header bg-primary text-white font-weight-bold py-3">
                修改個人資料 (當前檢視 ID: <?= h($profile['id']) ?> 的檔案)
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <!-- 修補重點：移除隱藏欄位 role 傳值，所有角色更新完全在後端隔離 -->

                    <div class="form-group row mb-3">
                        <label class="col-sm-3 col-form-label font-weight-bold">帳號名稱：</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control-plaintext" readonly value="<?= h($profile['username']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-group row mb-3">
                        <label for="name" class="col-sm-3 col-form-label font-weight-bold">姓名：</label>
                        <div class="col-sm-9">
                            <input type="text" name="name" id="name" class="form-control" value="<?= h($profile['name']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="email" class="col-sm-3 col-form-label font-weight-bold">電子郵件：</label>
                        <div class="col-sm-9">
                            <input type="email" name="email" id="email" class="form-control" value="<?= h($profile['email']) ?>" required>
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="phone" class="col-sm-3 col-form-label font-weight-bold">聯絡電話：</label>
                        <div class="col-sm-9">
                            <!-- 修補重點 4：敏感資料在網頁表單上只在預設顯示時以遮罩函數 mask_data 處理，傳輸防護 -->
                            <input type="text" name="phone" id="phone" class="form-control" value="<?= h($profile['phone']) ?>">
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="student_no" class="col-sm-3 col-form-label font-weight-bold">學號/教工號：</label>
                        <div class="col-sm-9">
                            <input type="text" name="student_no" id="student_no" class="form-control" value="<?= h($profile['student_no']) ?>">
                        </div>
                    </div>

                    <div class="form-group row mb-3">
                        <label for="national_id_fake" class="col-sm-3 col-form-label font-weight-bold">身分證字號：</label>
                        <div class="col-sm-9">
                            <input type="text" name="national_id_fake" id="national_id_fake" class="form-control" value="<?= h($profile['national_id_fake']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-group row mb-3">
                        <label class="col-sm-3 col-form-label font-weight-bold">目前系統角色：</label>
                        <div class="col-sm-9">
                            <span class="badge bg-secondary p-2"><?= h($profile['role']) ?></span>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success font-weight-bold">確認修改資料</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="text-center mt-3">
            <!-- 指向安全 API -->
            <a href="/api/profile.php?id=<?= h($profile['id']) ?>&token=student01-api-token-fixed-abcde" target="_blank" class="btn btn-link text-decoration-none">🔍 安全版 API 連結 (帶有 Token 認證驗證)</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
