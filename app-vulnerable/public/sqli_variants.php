<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';
check_login();

$union_result = null;
$union_error = '';
$boolean_result = null;
$boolean_error = '';
$time_result = null;
$time_error = '';
$sp_result = null;
$sp_error = '';

$tab = $_GET['tab'] ?? 'union';

// 1. UNION SQL Injection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'union') {
    $title = $_POST['title'] ?? '';
    try {
        // 漏洞點：UNION SQLi。字串直接拼接，且輸出所有結果
        $sql = "SELECT id, title, classroom, credit FROM courses WHERE title LIKE '%" . $title . "%'";
        $stmt = $pdo->query($sql);
        $union_result = $stmt->fetchAll();
    } catch (PDOException $e) {
        $union_error = $e->getMessage();
    }
}

// 2. Boolean-based Blind SQL Injection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'boolean') {
    $username = $_POST['username'] ?? '';
    try {
        // 漏洞點：Boolean Blind SQLi。僅回傳 True/False (帳號是否存在)
        $sql = "SELECT COUNT(*) FROM users WHERE username = '" . $username . "'";
        $stmt = $pdo->query($sql);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $boolean_result = "🟢 該帳號名稱已被註冊！";
        } else {
            $boolean_result = "🔴 該帳號名稱尚未使用，您可以使用它！";
        }
    } catch (PDOException $e) {
        // 即使出錯，也直接把 SQL 錯誤噴出來 (Error-based Blind)
        $boolean_error = $e->getMessage();
    }
}

// 3. Time-based Blind SQL Injection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'time') {
    $event_id = $_POST['event_id'] ?? '';
    try {
        // 漏洞點：Time-based Blind SQLi。頁面無論如何都不回傳查詢資料，只回傳通用成功訊息，但查詢會被執行。
        // 攻擊者需要利用 SLEEP(5) 延遲判定
        $sql = "SELECT * FROM events WHERE id = " . $event_id;
        $pdo->query($sql);
        $time_result = "🟢 查詢已送出！(此系統無回顯，僅回傳通用成功狀態)";
    } catch (PDOException $e) {
        // 盲注通常不回顯錯誤，但弱點版依然洩露了錯誤
        $time_error = $e->getMessage();
    }
}

// 4. Stored Procedure SQL Injection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'sp') {
    $course_id = $_POST['course_id'] ?? '';
    try {
        // 漏洞點：Stored Procedure SQLi。
        // 即使開發者使用了資料庫內部的 Stored Procedure (get_course_details)，
        // 但如果在呼叫 CALL 時採用字串拼接，依然會產生 SQL 注入漏洞！
        // 攻擊者可輸入：1) OR 1=1 -- 
        $sql = "CALL get_course_details(" . $course_id . ")";
        $stmt = $pdo->query($sql);
        $sp_result = $stmt->fetchAll();
    } catch (PDOException $e) {
        $sp_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>🔥 進階 SQL 注入變體演練 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #0b0f19; color: #e2e8f0; font-family: 'Noto Sans TC', sans-serif; padding-top: 50px; }
        .hero { background: linear-gradient(135deg, #7f1d1d 0%, #dc2626 50%, #ea580c 100%); color: white; padding: 45px 30px; border-radius: 16px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(185, 28, 28, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); }
        .card { background-color: #111827; border: 1px solid #1f2937; border-radius: 14px; margin-bottom: 24px; }
        .card-header { background-color: #1f2937; border-bottom: 1px solid #374151; color: #fb923c; font-weight: 700; }
        .nav-tabs .nav-link { color: #9ca3af; border: none; }
        .nav-tabs .nav-link.active { background-color: #111827; color: #fb923c; border: 1px solid #1f2937; border-bottom-color: #111827; border-radius: 8px 8px 0 0; }
        .btn-primary { background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%); border: none; border-radius: 8px; font-weight: 600; }
        .btn-primary:hover { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>🔥 進階 SQL 注入 (SQLi) 變體演練中心</h2>
        <div>
            <span class="mr-3 text-muted">您好，<strong><?= $_SESSION['user']['name'] ?></strong></span>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <div class="hero shadow">
        <h1>SQL 注入進階演練 (UNION / Blind / Time / Stored Procedure)</h1>
        <p class="lead">⚠️ 本頁面展示多種不同 SQLi 攻擊變體，可用於手工 SQLi 測試或工具（Sqlmap）的高級掃描測試！</p>
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
                            <label for="title" class="font-weight-bold text-light">搜尋課程名稱：</label>
                            <input type="text" name="title" id="title" class="form-control bg-dark text-white border-secondary" placeholder="輸入課程關鍵字" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">發送查詢</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>UNION 演練指引：</strong><br>
                        試著輸入：<code>' UNION SELECT 1, username, password_hash, role FROM users -- </code><br>
                        觀察是否能在課程搜尋表格中，直接列印出系統所有帳號與密碼 MD5！
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
                            <label for="username" class="font-weight-bold text-light">檢查帳號是否已被註冊：</label>
                            <input type="text" name="username" id="username" class="form-control bg-dark text-white border-secondary" placeholder="輸入要檢查的帳號 (如 student01)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">檢查可用性</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>布林盲注演練指引：</strong><br>
                        試著輸入：<code>student01' AND 1=1 -- </code> (頁面將顯示已註冊)<br>
                        輸入：<code>student01' AND 1=2 -- </code> (頁面將顯示未使用，說明您可以透過真假判斷猜解資料庫長度與字元！)
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
                            <label for="event_id" class="font-weight-bold text-light">載入活動編號：</label>
                            <input type="text" name="event_id" id="event_id" class="form-control bg-dark text-white border-secondary" placeholder="輸入活動 ID (如 1)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">加載活動</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>時間盲注演練指引：</strong><br>
                        試著輸入：<code>1 AND SLEEP(5)</code><br>
                        觀察網頁是否會在剛好延遲 5 秒後才回傳「查詢已送出」的通用成功訊息！
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
                            <label for="course_id" class="font-weight-bold text-light">呼叫預存程序查詢課程 (ID)：</label>
                            <input type="text" name="course_id" id="course_id" class="form-control bg-dark text-white border-secondary" placeholder="輸入課程 ID (如 1)" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">執行 CALL 預存程序</button>
                    </form>
                    
                    <div class="alert alert-warning mt-4">
                        💡 <strong>預存程序注入指引：</strong><br>
                        試著輸入：<code>1) OR 1=1 -- </code><br>
                        這會閉合 CALL 預存程序括號，使其構造為 <code>CALL get_course_details(1) OR 1=1 -- )</code>，進而繞過 ID 限制撈出所有課程！
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
                            <div class="alert alert-danger font-weight-bold">❌ SQL 錯誤：<?= htmlspecialchars($union_error) ?></div>
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
                            <div class="alert alert-danger font-weight-bold">❌ SQL 錯誤：<?= htmlspecialchars($boolean_error) ?></div>
                        <?php elseif ($boolean_result !== null): ?>
                            <div class="p-3 border rounded bg-white text-center font-weight-bold text-primary">
                                <?= $boolean_result ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待發送可用性檢查...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Time 結果 -->
                    <?php if ($tab === 'time'): ?>
                        <?php if ($time_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ SQL 錯誤：<?= htmlspecialchars($time_error) ?></div>
                        <?php elseif ($time_result !== null): ?>
                            <div class="p-3 border rounded bg-white text-center font-weight-bold text-success">
                                <?= $time_result ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-5">等待發送時間盲注測試...</p>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Stored Procedure 結果 -->
                    <?php if ($tab === 'sp'): ?>
                        <?php if ($sp_error): ?>
                            <div class="alert alert-danger font-weight-bold">❌ SQL 錯誤：<?= htmlspecialchars($sp_error) ?></div>
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
