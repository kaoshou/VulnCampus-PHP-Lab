<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$union_result = null;
$union_error = '';
$boolean_result = null;
$boolean_error = '';
$time_result = null;
$time_error = '';
$sp_result = null;
$sp_error = '';

$tab = $_GET['tab'] ?? 'union';

// 1. UNION SQL Injection (Fixed: Prepared Statements)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'union') {
    $title = $_POST['title'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT id, title, classroom, credit FROM courses WHERE title LIKE :title");
        $stmt->execute(['title' => '%' . $title . '%']);
        $union_result = $stmt->fetchAll();
    } catch (PDOException $e) {
        // 安全版：隱藏詳細資料庫錯誤，只記錄到 error.log
        error_log("UNION SQLi variant error: " . $e->getMessage());
        $union_error = '系統發生異常，請聯絡管理員。';
    }
}

// 2. Boolean-based Blind SQL Injection (Fixed: Prepared Statements)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'boolean') {
    $username = $_POST['username'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->execute(['username' => $username]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $boolean_result = "🟢 該帳號名稱已被註冊！";
        } else {
            $boolean_result = "🔴 該帳號名稱尚未使用，您可以使用它！";
        }
    } catch (PDOException $e) {
        error_log("Boolean SQLi variant error: " . $e->getMessage());
        $boolean_error = '系統發生異常，請聯絡管理員。';
    }
}

// 3. Time-based Blind SQL Injection (Fixed: Prepared Statements & Integer Validation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'time') {
    $event_id = $_POST['event_id'] ?? '';
    try {
        if (!filter_var($event_id, FILTER_VALIDATE_INT)) {
            $time_error = '無效的活動 ID 格式！';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM events WHERE id = :event_id");
            $stmt->execute(['event_id' => (int)$event_id]);
            $time_result = "🟢 查詢已送出！(此系統無回顯，僅回傳通用成功狀態)";
        }
    } catch (PDOException $e) {
        error_log("Time SQLi variant error: " . $e->getMessage());
        $time_error = '系統發生異常，請聯絡管理員。';
    }
}

// 4. Stored Procedure SQL Injection (Fixed: Prepared Call Statements)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'sp') {
    $course_id = $_POST['course_id'] ?? '';
    try {
        // 安全防護關鍵：即使呼叫 Stored Procedure，也必須使用 Prepared Call
        $stmt = $pdo->prepare("CALL get_course_details(:course_id)");
        $stmt->execute(['course_id' => $course_id]);
        $sp_result = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Stored Procedure SQLi variant error: " . $e->getMessage());
        $sp_error = '系統發生異常，請聯絡管理員。';
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🛡️ 進階 SQL 注入防範驗證 - VulnCampus</title>
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
        <h2>🛡️ 進階 SQL 注入 (SQLi) 防範驗證中心</h2>
        <div>
            <span class="mr-3 text-muted">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="hero shadow">
        <h1>SQL 注入安全防護對照</h1>
        <p class="lead">🟢 此頁面已完全套用參數化查詢 (Prepared Statements) 防禦，包含 Stored Procedure 的呼叫亦受保護！</p>
    </div>

    <!-- 頁籤導覽 -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'union' ? 'active' : '' ?>" href="?tab=union">📊 UNION SQLi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'boolean' ? 'active' : '' ?>" href="?tab=boolean">🔍 Boolean-based Blind SQLi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'time' ? 'active' : '' ?>" href="?tab=time">⏱️ Time-based SQLi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'sp' ? 'active' : '' ?>" href="?tab=sp">📦 Stored Procedure SQLi</a>
        </li>
    </ul>

    <div class="row">
        <!-- 演練表單與說明 -->
        <div class="col-md-7">
            
            <!-- UNION SQLi -->
            <?php if ($tab === 'union'): ?>
            <div class="card shadow-sm">
                <div class="card-header">📊 聯集查詢注入 (UNION-based SQL Injection)</div>
                <div class="card-body">
                    <p class="text-muted">當頁面會將查詢結果「直接渲染在頁面」上時使用。攻擊者可以利用 <code>UNION SELECT</code> 追加資料行並全部撈出。</p>
                    <form method="POST" action="?tab=union">
                        <div class="form-group">
                            <label for="title" class="font-weight-bold">搜尋課程名稱：</label>
                            <input type="text" name="title" id="title" class="form-control border-secondary" placeholder="輸入課程關鍵字" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">發送查詢</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        使用 <code>$pdo->prepare()</code> 預編譯語句，傳入的參數會作為純文字處理，無法干擾原本的 SQL 語意。
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Boolean-based Blind SQLi -->
            <?php if ($tab === 'boolean'): ?>
            <div class="card shadow-sm">
                <div class="card-header">🔍 布林盲注 (Boolean-based Blind SQL Injection)</div>
                <div class="card-body">
                    <p class="text-muted">當頁面「不顯示資料庫內容」，僅根據查詢的 True/False 顯示不同的回顯狀態（例如帳號是否存在）時，攻擊者利用此二元狀態猜解資料庫。</p>
                    <form method="POST" action="?tab=boolean">
                        <div class="form-group">
                            <label for="username" class="font-weight-bold">檢查帳號是否已被註冊：</label>
                            <input type="text" name="username" id="username" class="form-control border-secondary" placeholder="輸入要檢查的帳號 (如 student01)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">檢查可用性</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        同樣採用參數化查詢，縱使攻擊者拼接布林語法，也會被完整轉義作為字串值進行對比，絕不執行任何 SQL 條件分支。
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Time-based SQLi -->
            <?php if ($tab === 'time'): ?>
            <div class="card shadow-sm">
                <div class="card-header">⏱️ 時間延遲盲注 (Time-based SQL Injection)</div>
                <div class="card-body">
                    <p class="text-muted">當頁面完全不顯示任何動態回顯、也不顯示真假狀態，只回傳通用成功狀態時。攻擊者必須藉由觸發資料庫延遲函數（如 <code>SLEEP(5)</code>）藉由響應時間差來進行猜解。</p>
                    <form method="POST" action="?tab=time">
                        <div class="form-group">
                            <label for="event_id" class="font-weight-bold">載入活動編號：</label>
                            <input type="text" name="event_id" id="event_id" class="form-control border-secondary" placeholder="輸入活動 ID (如 1)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">加載活動</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        使用 <code>FILTER_VALIDATE_INT</code> 進行後端強型態驗證，非整數的輸入（例如包含 SLEEP 函數的代碼）將直接被攔截，並結合參數化查詢徹底阻斷時間盲注。
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Stored Procedure SQLi -->
            <?php if ($tab === 'sp'): ?>
            <div class="card shadow-sm">
                <div class="card-header">📦 預存程序注入 (Stored Procedure SQL Injection)</div>
                <div class="card-body">
                    <p class="text-muted">許多開發者認為使用預存程序 (Stored Procedure) 可以完全杜絕 SQLi，然而如果在呼叫預存程序時採用了不安全的字串拼接而非綁定參數，依然會引起 SQLi 漏洞。</p>
                    <form method="POST" action="?tab=sp">
                        <div class="form-group">
                            <label for="course_id" class="font-weight-bold">呼叫預存程序查詢課程 (ID)：</label>
                            <input type="text" name="course_id" id="course_id" class="form-control border-secondary" placeholder="輸入課程 ID (如 1)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">執行 CALL 預存程序</button>
                    </form>
                    
                    <div class="alert alert-success mt-4">
                        🛡️ <strong>防護原理：</strong><br>
                        呼叫預存程序時，不可將參數直接拼接在 <code>CALL get_course_details($id)</code> 內，必須像一般 SQL 一樣使用預編譯預處理：<code>CALL get_course_details(:id)</code> 進行安全參數綁定。
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- 結果展示區 -->
        <div class="col-md-5">
            <div class="card shadow-sm" style="min-height: 380px;">
                <div class="card-header bg-dark text-white">📊 執行與響應結果</div>
                <div class="card-body bg-light text-dark" style="max-height: 500px; overflow-y: auto;">
                    
                    <!-- UNION 結果 -->
                    <?php if ($tab === 'union'): ?>
                        <?php if ($union_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ 錯誤：<?= htmlspecialchars($union_error) ?></div>
                        <?php elseif ($union_result !== null): ?>
                            <div class="alert alert-success">成功獲取 <?= count($union_result) ?> 筆課程！</div>
                            <table class="table table-bordered table-sm bg-white">
                                <thead><tr><th>ID</th><th>名稱</th><th>教室</th><th>學分</th></tr></thead>
                                <tbody>
                                    <?php foreach ($union_result as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['title'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['classroom'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['credit'] ?? '') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待發送聯集查詢...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Boolean 結果 -->
                    <?php if ($tab === 'boolean'): ?>
                        <?php if ($boolean_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ 錯誤：<?= htmlspecialchars($boolean_error) ?></div>
                        <?php elseif ($boolean_result !== null): ?>
                            <div class="p-3 border rounded bg-white text-center font-weight-bold text-primary">
                                <?= htmlspecialchars($boolean_result) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待發送可用性檢查...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Time 結果 -->
                    <?php if ($tab === 'time'): ?>
                        <?php if ($time_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ 錯誤：<?= htmlspecialchars($time_error) ?></div>
                        <?php elseif ($time_result !== null): ?>
                            <div class="p-3 border rounded bg-white text-center font-weight-bold text-success">
                                <?= htmlspecialchars($time_result) ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待發送時間盲注測試...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Stored Procedure 結果 -->
                    <?php if ($tab === 'sp'): ?>
                        <?php if ($sp_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ 錯誤：<?= htmlspecialchars($sp_error) ?></div>
                        <?php elseif ($sp_result !== null): ?>
                            <div class="alert alert-success">預存程序回傳 <?= count($sp_result) ?> 筆結果！</div>
                            <table class="table table-bordered table-sm bg-white">
                                <thead><tr><th>ID</th><th>課程名稱</th><th>學分</th></tr></thead>
                                <tbody>
                                    <?php foreach ($sp_result as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['title'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['credit'] ?? '') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待呼叫預存程序...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
