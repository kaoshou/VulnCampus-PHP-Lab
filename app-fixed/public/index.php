<?php
require_once __DIR__ . '/../src/helpers.php';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>VulnCampus 校園活動與課程報名平台 (修正版)</title>
    <!-- 修補重點：引入最新穩定版 Bootstrap 5 並且避免使用已知漏洞之版本 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        body { background-color: #f4f7f6; padding-top: 50px; }
        .hero { background: linear-gradient(135deg, #1b4f72 0%, #2e86c1 100%); color: white; padding: 40px 20px; border-radius: 10px; margin-bottom: 30px; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>

<div class="container">
    <div class="hero text-center shadow">
        <h1>VulnCampus 校園活動與課程報名平台</h1>
        <p class="lead">🟢 本站為【安全修正版網站】，已進行全面資安防禦修補，適合作為對照驗證與安全掃描比對！</p>
        <?php if (isset($_SESSION['user'])): ?>
            <div class="alert alert-light d-inline-block py-1 px-3">
                目前登入身分：<strong><?= h($_SESSION['user']['username']) ?></strong> 
                <span class="badge bg-secondary ms-2"><?= h($_SESSION['user']['role']) ?></span>
                <a href="/logout.php" class="btn btn-sm btn-outline-danger ms-3">安全登出</a>
            </div>
        <?php else: ?>
            <a href="/login.php" class="btn btn-light btn-lg font-weight-bold text-primary shadow-sm">安全登入</a>
        <?php endif; ?>
    </div>

    <div class="row">
        <!-- 系統功能選單 -->
        <div class="col-md-8">
            <h3 class="mb-4 text-dark font-weight-bold">功能列表 (已修補防禦)</h3>
            <div class="row">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📖 課程查詢 (Prepared Statements)</h5>
                            <p class="card-text text-muted flex-grow-1">使用 PDO 預處理阻擋 SQL 注入，對搜尋關鍵字做 HTML 編碼以防 XSS。</p>
                            <a href="/courses.php" class="btn btn-outline-primary mt-2">進入課程查詢</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">💬 校園留言板 (XSS & CSRF 防護)</h5>
                            <p class="card-text text-muted flex-grow-1">對所有留言內容進行輸出編碼，並引入 CSRF Token 來防範跨站請求偽造。</p>
                            <a href="/messages.php" class="btn btn-outline-primary mt-2">進入留言板</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📅 活動報名 (後端計算與行鎖)</h5>
                            <p class="card-text text-muted flex-grow-1">後端重算金額、校驗數量為正整數、並使用 SELECT FOR UPDATE 阻擋超賣。</p>
                            <a href="/events.php" class="btn btn-outline-primary mt-2">進入活動列表</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">👤 個人資料 (越權防護)</h5>
                            <p class="card-text text-muted flex-grow-1">強制比對登入者與請求 ID，敏感資料遮蔽，後端採欄位白名單防升權。</p>
                            <a href="/profile.php" class="btn btn-outline-primary mt-2">進入個人資料</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📤 大頭貼上傳 (檔案安全性過濾)</h5>
                            <p class="card-text text-muted flex-grow-1">限制副檔名 (僅限圖片)，重新生成亂數檔名，並禁止執行上傳目錄內的腳本。</p>
                            <a href="/upload.php" class="btn btn-outline-primary mt-2">前往上傳大頭貼</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📥 檔案下載 (File ID 查表)</h5>
                            <p class="card-text text-muted flex-grow-1">使用資料庫 File ID 查表下載，完全移除使用者對硬碟路徑的直接控制權。</p>
                            <a href="/download.php" class="btn btn-outline-primary mt-2">前往檔案下載</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">🔑 密碼重設 (強隨機 Token 流程)</h5>
                            <p class="card-text text-muted flex-grow-1">重設連結包含高強度一次性 Token，且後端嚴格校驗 Token 的有效性與時效。</p>
                            <a href="/reset_password.php" class="btn btn-outline-primary mt-2">前往密碼重設</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">🔗 網址轉向 (白名單過濾)</h5>
                            <p class="card-text text-muted flex-grow-1">跳轉功能加入同源與相對路徑檢查，防止惡意外部 Open Redirect 導向。</p>
                            <a href="/redirect.php?url=courses.php" class="btn btn-outline-primary mt-2">測試網址轉向</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 側邊欄 -->
        <div class="col-md-4">
            <div class="card bg-success text-white mb-4">
                <div class="card-header font-weight-bold">🛡️ 安全防禦狀態</div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li>已部署 Content-Security-Policy</li>
                        <li>Session Cookie 已啟用 HttpOnly</li>
                        <li>密碼採用 Bcrypt 雜湊存取</li>
                        <li>已移除所有敏感備份檔案</li>
                        <li>已部署安全防爆破與次數鎖定</li>
                    </ul>
                </div>
            </div>

            <div class="card">
                <div class="card-header font-weight-bold bg-dark text-white">⚙️ 管理功能</div>
                <div class="card-body">
                    <a href="/admin/index.php" class="btn btn-dark w-100 mb-2">🔒 管理員後台 (限 Admin 存取)</a>
                    <p class="text-muted text-center text-small mb-0 mt-2">
                        * 提示：修正版無 debug.php 頁面以防配置外洩。
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="my-5 pt-5 text-muted text-center text-small border-top">
        <p class="mb-1">© 2026 VulnCampus PHP Lab - 網站弱點檢測安全改善教學靶場 (修正版)</p>
        <p class="mb-1">課程規劃與靶場設計：<strong>鄭郁翰 老師</strong></p>
        <p class="mb-1 text-secondary">本平台專供資訊安全教育訓練課程教學演練使用</p>
        <p class="text-success font-weight-bold">🟢 提示：本站已完成全站安全修補防禦，適用於防護驗證與安全掃描複掃比對。</p>
    </footer>
</div>

<!-- 修補重點：引入最新穩定版 jQuery 與 Bootstrap 5 JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
