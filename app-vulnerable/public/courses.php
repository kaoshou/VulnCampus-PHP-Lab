<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$q = $_GET['q'] ?? '';
$courses = [];
$error = '';

try {
    if ($q !== '') {
        // 教學用弱點 1：SQL 注入。直接拼接 $_GET['q'] 到 SQL 語句中
        $sql = "SELECT * FROM courses WHERE title LIKE '%" . $q . "%'";
        $stmt = $pdo->query($sql);
        $courses = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT * FROM courses");
        $courses = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // 教學用弱點 2：錯誤外洩。直接將資料庫底層 Exception 的錯誤訊息噴到頁面上，幫助黑客判讀 SQL 注入語法
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>課程查詢 - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>📖 課程查詢系統</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <!-- 搜尋表單 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="courses.php" class="form-inline">
                <input type="text" name="q" class="form-control mr-2 col-md-6" placeholder="輸入課程關鍵字..." value="<?= $q ?>">
                <button type="submit" class="btn btn-primary">查詢</button>
            </form>
        </div>
    </div>

    <!-- 教學用弱點 3：反射型 XSS (Reflected XSS)。直接將用戶輸入 q 無過濾地輸出到網頁中 -->
    <?php if ($q !== ''): ?>
        <div class="alert alert-warning">
            您搜尋的關鍵字是：<strong><?= $q ?></strong>
        </div>
    <?php endif; ?>

    <!-- 資料庫報錯顯示 -->
    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold">
            資料庫查詢錯誤 (SQL 錯誤外洩)：<br>
            <pre class="bg-dark text-warning p-3 mt-2 rounded"><?= $error ?></pre>
        </div>
    <?php endif; ?>

    <!-- 課程清單 -->
    <div class="card">
        <div class="card-header font-weight-bold">搜尋結果</div>
        <div class="card-body">
            <?php if (empty($courses) && !$error): ?>
                <p class="text-muted">找不到相符的課程。</p>
            <?php elseif (!empty($courses)): ?>
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>編號</th>
                            <th>課程名稱</th>
                            <th>學分</th>
                            <th>上課教室</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?= $course['id'] ?></td>
                                <td>
                                    <!-- 點選可檢視詳情 -->
                                    <a href="/course_detail.php?id=<?= $course['id'] ?>" class="font-weight-bold"><?= $course['title'] ?></a>
                                </td>
                                <td><?= $course['credit'] ?></td>
                                <td><?= $course['classroom'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
