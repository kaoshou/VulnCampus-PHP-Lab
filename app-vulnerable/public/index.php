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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #0b0f19; /* Deep Cyber Dark */
            color: #e2e8f0;
            font-family: 'Plus Jakarta Sans', 'Noto Sans TC', sans-serif;
            padding-top: 50px;
        }
        .hero {
            background: linear-gradient(135deg, #450a0a 0%, #991b1b 50%, #ea580c 100%);
            color: white;
            padding: 50px 30px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(185, 28, 28, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.2);
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at top right, rgba(234, 88, 12, 0.15) 0%, transparent 60%);
            pointer-events: none;
        }
        .card {
            background-color: #111827; /* Dark Grey */
            border: 1px solid #1f2937;
            border-radius: 14px;
            margin-bottom: 24px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(234, 88, 12, 0.1);
            border-color: #f97316;
        }
        .card-body {
            padding: 24px;
        }
        .card-title {
            color: #fb923c;
            font-weight: 700;
        }
        .card-text {
            color: #9ca3af;
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .btn-primary {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);
            border: none;
            border-radius: 8px;
            font-weight: 600;
            padding: 10px 20px;
            box-shadow: 0 4px 12px rgba(234, 88, 12, 0.2);
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            transform: scale(1.02);
            color: white;
        }
        .table {
            color: #e2e8f0;
        }
        .table-bordered th, .table-bordered td {
            border-color: #1f2937 !important;
        }
        .bg-warning .table {
            color: #0f172a !important;
        }
        .bg-warning .table th, .bg-warning .table td {
            border-color: rgba(15, 23, 42, 0.15) !important;
        }
        .bg-warning .table code {
            color: #b91c1c !important;
            background-color: rgba(0, 0, 0, 0.05);
            font-weight: 600;
        }
        .alert-warning {
            background-color: rgba(234, 88, 12, 0.1);
            border-color: rgba(234, 88, 12, 0.2);
            color: #fdba74;
        }
        .footer-logo {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ef4444 0%, #f97316 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            display: inline-block;
        }
        .footer-section {
            border-top: 1px solid #1f2937;
            padding: 60px 0 40px;
            margin-top: 80px;
            background-color: #030712;
        }
        .footer-section p, .footer-section li, .footer-section div, .footer-section span {
            color: #9ca3af !important; /* Force clear light text */
        }
        .footer-section h5 {
            color: #ffffff !important; /* Pure white titles */
        }
        .footer-link {
            color: #f97316 !important; /* Vibrant orange link */
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-link:hover {
            color: #fdba74 !important;
            text-decoration: underline;
        }
        .badge-danger-glow {
            background-color: rgba(239, 68, 68, 0.15) !important;
            color: #fca5a5 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
            font-weight: 600;
        }
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
                            <h5 class="card-title">🔥 SQL 注入 (UNION / 盲注 / 預存程序)</h5>
                            <p class="card-text">提供 UNION 查詢、布林盲注、時間延遲盲注，以及預存程序注入的綜合演練中心。</p>
                            <a href="/sqli_variants.php" class="btn btn-primary">進入 SQL 注入變體演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📦 第三方套件與經典元件漏洞</h5>
                            <p class="card-text">展示 PHP 反序列化漏洞 (Object Injection)、PHPMailer RCE，與 Log4Shell 漏洞之模擬與防禦對照。</p>
                            <a href="/component_vulnerabilities.php" class="btn btn-primary">進入不安全元件演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📖 專屬：反射型 XSS 測試頁</h5>
                            <p class="card-text">單純的搜尋關鍵字回顯頁面，用於測試反射型 XSS 漏洞的輸入與輸出。</p>
                            <a href="/xss_reflected.php" class="btn btn-primary">進入反射型 XSS</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">💬 專屬：預存型 XSS 測試頁</h5>
                            <p class="card-text">單純的訪客留言反饋頁面，內容寫入資料庫，用於測試儲存型 XSS 漏洞。</p>
                            <a href="/xss_stored.php" class="btn btn-primary">進入預存型 XSS</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🌐 專屬：DOM-based XSS 測試頁</h5>
                            <p class="card-text">利用 location.hash 及 innerHTML 渲染，用於專門測試前端 DOM-based XSS。</p>
                            <a href="/xss_dom.php" class="btn btn-primary">進入 DOM-based XSS</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📋 不安全 HTML 表單配置</h5>
                            <p class="card-text">敏感資料以 GET 傳送、自動完成啟用，且後端未校驗 Email、選單與型態等欄位風險。</p>
                            <a href="/form_risks.php" class="btn btn-primary">進入表單風險演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📖 課程查詢系統</h5>
                            <p class="card-text">提供全校課程的即時搜尋，可用於演練 SQL 注入 (SQLi) 拖出整個使用者資料庫。</p>
                            <a href="/courses.php" class="btn btn-primary">進入課程查詢</a>
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
                            <h5 class="card-title">📂 檔案引入漏洞 (LFI / RFI)</h5>
                            <p class="card-text">動態加載模組檔案，但未限制路徑。可用於測試本地檔案引入與遠端惡意程式碼執行。</p>
                            <a href="/file_inclusion.php" class="btn btn-primary">進入檔案引入演練</a>
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
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📍 行動定位打卡 (HTML5 / localStorage)</h5>
                            <p class="card-text">使用 HTML5 Geolocation 打卡，並將資訊存於 localStorage。可用於測試定位隱私洩漏 (IDOR) 與 DOM XSS。</p>
                            <a href="/checkin.php" class="btn btn-primary">前往打卡頁面</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🔍 自訂個資洩漏與 ZAP 腳本檢測</h5>
                            <p class="card-text">明文暴露身分證、電話等敏感個資，並提供 ZAP 被動腳本代碼讓學員實作自訂 Regex 規則報警。</p>
                            <a href="/pii_leakage.php" class="btn btn-primary">進入個資檢測演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🎨 版面主題色自訂 (CSS Injection)</h5>
                            <p class="card-text">允許使用者填入自訂 CSS 以調整個人版面樣式。可用於測試 CSS 注入漏洞與繞過。</p>
                            <a href="/css_injection.php" class="btn btn-primary">進入主題自訂</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">⚡ AJAX 查詢 (BOLA / IDOR / XSS)</h5>
                            <p class="card-text">異步 AJAX 獲取個資系統，可用於測試 API 權限缺陷、個資過度暴露與 DOM-based XSS 漏洞。</p>
                            <a href="/ajax_vulnerability.php" class="btn btn-primary">進入 AJAX 測試</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">🌐 SSRF 預覽 (內網探測)</h5>
                            <p class="card-text">遠端圖片預覽功能，未對網址進行過濾，可用於進行 SSRF 伺服器端請求偽造測試與內網埠口掃描。</p>
                            <a href="/ssrf_demo.php" class="btn btn-primary">進入 SSRF 測試</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">📁 XXE 匯入 (外部實體解析)</h5>
                            <p class="card-text">上傳名單 XML 解析功能，未關閉實體載入，可用於進行 XML 外部實體注入與本地任意檔案讀取測試。</p>
                            <a href="/xxe_demo.php" class="btn btn-primary">進入 XXE 測試</a>
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
                        * 註：弱點版預設密碼極弱，且直接以明文 (Plaintext) 儲存於資料庫中。
                    </small>
                </div>
            </div>

            <div class="card">
                <div class="card-header font-weight-bold">🛠️ 系統診斷與管理</div>
                <div class="card-body">
                    <a href="/debug.php" class="btn btn-outline-danger btn-block mb-2 text-wrap" style="white-space: normal; word-break: break-word;">🔍 系統偵錯頁 (debug.php)</a>
                    <a href="/clickjacking_poc.php" class="btn btn-outline-warning btn-block mb-2 text-wrap" style="white-space: normal; word-break: break-word;">🖼️ 點擊劫持 PoC (clickjacking_poc.php)</a>
                    <a href="/admin/index.php" class="btn btn-outline-light btn-block text-wrap" style="white-space: normal; word-break: break-word;">🔒 管理員後台 (admin/)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4">
                <span class="footer-logo">🔥 VulnCampus PHP Lab (弱點版)</span>
                <p class="text-muted small pr-md-4">
                    本教學靶場專為《網站弱點檢測與安全改善：OWASP ZAP 應用》設計，模擬「校園活動、課程查詢與報名平台」之常見漏洞架構，帶領學員實踐 OWASP Top 10 的檢測與修補技術。
                </p>
                <div class="mt-3">
                    <span class="badge badge-danger-glow">⚠️ 限本機演練使用</span>
                    <span class="badge badge-secondary text-white">Apache 8080</span>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <h5 class="text-white font-weight-bold mb-3">設計與學術合作</h5>
                <ul class="list-unstyled small">
                    <li class="mb-2"><strong>設計者：</strong> 鄭郁翰 老師 (Yu-Han Cheng)</li>
                    <li class="mb-2"><strong>機構：</strong> <a href="https://www.ksu.edu.tw" target="_blank" class="footer-link">崑山科技大學 (Kun Shan University)</a></li>
                    <li class="mb-2"><strong>聯絡方式：</strong> <a href="mailto:yhcheng@mail.ksu.edu.tw" class="footer-link">yhcheng@mail.ksu.edu.tw</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="text-white font-weight-bold mb-3">資安警語與免責聲明</h5>
                <p class="small text-muted">
                    本系統內含多項嚴重 Web 安全漏洞（包含 RCE、SQL 注入、XXE、SSRF 及 WebShell 上傳等）。嚴禁將此弱點版本部署於公網環境，或用於任何未經授權之滲透測試及攻擊活動。
                </p>
            </div>
        </div>
        <div class="border-top border-secondary mt-4 pt-4 text-center small text-muted">
            <p class="mb-0">© 2026 崑山科技大學 鄭郁翰老師 (Yu-Han Cheng). All Rights Reserved. 僅限學術與合法安全研究使用。</p>
        </div>
    </div>
</footer>

<!-- 教學用弱點：引入過舊版 jQuery (1.12.4) 具備已知漏洞 -->
<script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
<script>
<?php if (isset($_SESSION['user'])): ?>
    // 弱點版：登入成功後，前端將敏感的 API Token、個資儲存在 localStorage 中，使其在 XSS 下無所遁形
    localStorage.setItem('user_session', JSON.stringify({
        id: <?= json_encode($_SESSION['user']['id']) ?>,
        username: <?= json_encode($_SESSION['user']['username']) ?>,
        role: <?= json_encode($_SESSION['user']['role']) ?>,
        name: <?= json_encode($_SESSION['user']['name']) ?>,
        api_token: 'student01-api-token-vuln-12345'
    }));
<?php else: ?>
    // 未登入則清空
    localStorage.removeItem('user_session');
<?php endif; ?>
</script>
</body>
</html>

