<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

$q = trim($_GET['q'] ?? '');
$courses = [];
$error = '';

try {
    if ($q !== '') {
        // 修補重點 1：使用 Prepared Statement 代替 SQL 字串拼接，杜絕 SQL 注入
        $stmt = $pdo->prepare("SELECT * FROM courses WHERE title LIKE :q");
        $stmt->execute(['q' => '%' . $q . '%']);
        $courses = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT * FROM courses");
        $courses = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // 修補重點 2：捕獲例外，將 SQL 詳細錯誤記錄在伺服器端日誌，不外洩給使用者
    error_log("Database courses query error: " . $e->getMessage());
    $error = '系統目前無法完成查詢，請稍後再試。';
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>課程查詢 - VulnCampus (安全版)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">📖 課程查詢系統 (安全版)</h2>
        <a href="/index.php" class="btn btn-secondary">回首頁</a>
    </div>

    <!-- 搜尋表單 -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">
            <form method="GET" action="courses.php" class="row g-3">
                <div class="col-md-8">
                    <!-- 使用 h() 函數防範反射型 XSS 漏洞 -->
                    <input type="text" name="q" class="form-control" placeholder="輸入課程關鍵字..." value="<?= h($q) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100 font-weight-bold">查詢</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 修補重點 3：使用 h() 將搜尋關鍵字進行 HTML 輸出編碼，徹底防範反射型 XSS 漏洞 -->
    <?php if ($q !== ''): ?>
        <div class="alert alert-info">
            您搜尋的關鍵字是：<strong><?= h($q) ?></strong>
        </div>
    <?php endif; ?>

    <!-- 安全的錯誤訊息顯示 -->
    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold">
            <?= h($error) ?>
        </div>
    <?php endif; ?>

    <!-- 課程清單 -->
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white font-weight-bold">搜尋結果</div>
        <div class="card-body">
            <?php if (empty($courses) && !$error): ?>
                <p class="text-muted mb-0">找不到相符的課程。</p>
            <?php elseif (!empty($courses)): ?>
                <table class="table table-hover mb-0">
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
                                <td><?= h($course['id']) ?></td>
                                <td>
                                    <!-- 使用 h() 確保所有從資料庫撈出的動態資料輸出時皆經過編碼 -->
                                    <a href="/course_detail.php?id=<?= h($course['id']) ?>" class="font-weight-bold text-decoration-none"><?= h($course['title']) ?></a>
                                </td>
                                <td><?= h($course['credit']) ?></td>
                                <td><?= h($course['classroom']) ?></td>
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
