<?php
require_once __DIR__ . '/../src/helpers.php';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>VulnCampus 校園活動與課程報名平台 (弱點版)</title>
    <!-- 教學用弱點：引入過舊版 Bootstrap (4.0.0) 具備已知漏洞 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .hero { background: linear-gradient(135deg, #e3342f 0%, #f6993f 100%); color: white; padding: 40px 20px; border-radius: 10px; margin-bottom: 30px; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="hero text-center">
        <h1>VulnCampus 校園活動與課程報名平台</h1>
        <p class="lead">⚠️ 本站為【弱點版網站】，僅供 OWASP ZAP 安全檢測與教學演示使用，請勿部署於公開網路！</p>
        <?php if (isset($_SESSION['user'])): ?>
            <div class="alert alert-info d-inline-block">
                目前登入身分：<strong><?= $_SESSION['user']['username'] ?></strong> (角色: <?= $_SESSION['user']['role'] ?>) 
                <a href="/logout.php" class="btn btn-sm btn-danger ml-2">登出</a>
            </div>
        <?php else: ?>
            <a href="/login.php" class="btn btn-light btn-lg font-weight-bold">前往登入</a>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- 系統功能選單 -->
        <div class="col-md-8">
            <h3 class="mb-4">系統功能測試頁面</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📖 課程查詢 (SQLi / XSS)</h5>
                            <p class="card-text">提供課程關鍵字搜尋功能，可用於測試 SQL 注入與反射型 XSS 漏洞。</p>
                            <a href="/courses.php" class="btn btn-primary">進入課程查詢</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">💬 校園留言板 (Stored XSS)</h5>
                            <p class="card-text">留言板功能未過濾 HTML，可用於測試儲存型 XSS 漏洞及 CSRF 攻擊。</p>
                            <a href="/messages.php" class="btn btn-primary">進入留言板</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📅 活動報名 (安全設計缺陷)</h5>
                            <p class="card-text">可在此測試商業邏輯漏洞，例如數量輸入負數、前端價格竄改等缺陷。</p>
                            <a href="/events.php" class="btn btn-primary">進入活動列表</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">👤 個人資料 (IDOR / 越權)</h5>
                            <p class="card-text">修改或查詢個人資訊，可用於測試 IDOR 水平越權與 Mass Assignment 漏洞。</p>
                            <a href="/profile.php" class="btn btn-primary">進入個人資料</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📤 大頭貼上傳 (任意檔案上傳)</h5>
                            <p class="card-text">提供使用者上傳大頭貼的功能，允許上傳 .php 網頁後門執行 Command。</p>
                            <a href="/upload.php" class="btn btn-primary">前往上傳大頭貼</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📥 檔案下載 (Path Traversal)</h5>
                            <p class="card-text">透過檔案路徑下載公開或私密檔案，可用於測試路徑遍歷漏洞。</p>
                            <a href="/download.php" class="btn btn-primary">前往檔案下載</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🔑 密碼重設 (邏輯缺陷)</h5>
                            <p class="card-text">測試密碼重設流程的安全性缺陷，了解如何免驗證重設他人密碼。</p>
                            <a href="/reset_password.php" class="btn btn-primary">前往密碼重設</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🔗 網址轉向 (Open Redirect)</h5>
                            <p class="card-text">系統內部跳轉功能未做過濾，可用於進行釣魚攻擊與 Open Redirect 測試。</p>
                            <a href="/redirect.php?url=courses.php" class="btn btn-primary">測試網址轉向</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 側邊欄：測試帳號與系統資訊 -->
        <div class="col-md-4">
            <div class="card bg-warning text-dark mb-4">
                <div class="card-header font-weight-bold">🔑 測試帳號密碼對照表</div>
                <div class="card-body">
                    <table class="table table-sm table-bordered mb-0" style="background: rgba(255,255,255,0.7);">
                        <thead>
                            <tr><th>身分</th><th>帳號</th><th>密碼</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>管理員</td><td><code>admin</code></td><td><code>admin</code></td></tr>
                            <tr><td>學生01</td><td><code>student01</code></td><td><code>password123</code></td></tr>
                            <tr><td>學生02</td><td><code>student02</code></td><td><code>password123</code></td></tr>
                            <tr><td>教師01</td><td><code>teacher01</code></td><td><code>password123</code></td></tr>
                        </tbody>
                    </table>
                    <small class="form-text mt-2 text-danger font-weight-bold">
                        * 註：弱點版預設密碼極弱，且全部使用 MD5 雜湊儲存於資料庫中。
                    </small>
                </div>
            </div>

            <div class="card">
                <div class="card-header font-weight-bold">🛠️ 系統診斷與管理</div>
                <div class="card-body">
                    <a href="/debug.php" class="btn btn-outline-danger btn-block mb-2">🔍 系統偵錯頁 (debug.php)</a>
                    <a href="/admin/index.php" class="btn btn-outline-dark btn-block">🔒 管理員後台 (admin/)</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="my-5 pt-5 text-muted text-center text-small border-top">
        <p class="mb-1">© 2026 VulnCampus PHP Lab - 網站弱點檢測安全改善教學靶場</p>
        <p class="mb-1">課程規劃與靶場設計：<strong>鄭郁翰 老師</strong></p>
        <p class="mb-1 text-secondary">本平台專供資訊安全教育訓練課程教學演練使用</p>
        <p class="text-danger font-weight-bold">⚠️ 警告：本站為「弱點版網站」，內含多項嚴重安全漏洞，僅限本機安全研究與授權演練，嚴禁部署於公開網路或用於非法用途！</p>
    </footer>
</div>

<!-- 教學用弱點：引入過舊版 jQuery (1.12.4) 具備已知漏洞 -->
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
</body>
</html>
