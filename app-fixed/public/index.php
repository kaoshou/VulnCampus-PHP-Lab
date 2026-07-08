<?php
require_once __DIR__ . '/../src/helpers.php';
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>VulnCampus 校園活動與課程報名平台 (修正版)</title>
    <!-- 修補重點：引入最新穩定版 Bootstrap 5 並且避免使用已知漏洞之版本 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Noto+Sans+TC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f8fafc; /* slate-50 */
            color: #0f172a; /* slate-900 */
            font-family: 'Plus Jakarta Sans', 'Noto Sans TC', sans-serif;
            padding-top: 50px;
        }
        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 50%, #0d9488 100%);
            color: white;
            padding: 50px 30px;
            border-radius: 16px;
            margin-bottom: 40px;
            box-shadow: 0 10px 30px rgba(13, 148, 136, 0.15);
            border: 1px solid rgba(13, 148, 136, 0.2);
            position: relative;
            overflow: hidden;
        }
        .hero::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: radial-gradient(circle at top right, rgba(13, 148, 136, 0.2) 0%, transparent 60%);
            pointer-events: none;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin-bottom: 24px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.03), 0 2px 4px -1px rgba(0,0,0,0.02);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #ffffff;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.02);
            border-color: #0d9488; /* Teal-600 */
        }
        .card-body {
            padding: 24px;
        }
        .card-title {
            color: #1e3a8a; /* Indigo-900 */
            font-weight: 700;
        }
        .card-text {
            color: #475569; /* Slate-600 */
            font-size: 0.92rem;
            line-height: 1.5;
        }
        .btn-outline-primary {
            color: #0d9488;
            border-color: #0d9488;
            border-radius: 8px;
            font-weight: 600;
            padding: 8px 16px;
            transition: all 0.2s;
        }
        .btn-outline-primary:hover {
            background-color: #0d9488;
            border-color: #0d9488;
            color: white;
            transform: scale(1.02);
        }
        .table {
            color: #1f2937;
        }
        .footer-logo {
            font-size: 1.4rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1e3a8a 0%, #0d9488 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            display: inline-block;
        }
        .footer-section {
            border-top: 1px solid #e2e8f0;
            padding: 60px 0 40px;
            margin-top: 80px;
            background-color: #0f172a;
        }
        .footer-section p, .footer-section li, .footer-section div, .footer-section span {
            color: #94a3b8 !important; /* Force clear light text */
        }
        .footer-section h5 {
            color: #ffffff !important; /* Pure white titles */
        }
        .footer-link {
            color: #38bdf8 !important; /* Light blue link */
            text-decoration: none;
            transition: color 0.2s;
        }
        .footer-link:hover {
            color: #7dd3fc !important;
            text-decoration: underline;
        }
        .badge-success-glow {
            background-color: rgba(13, 148, 136, 0.1) !important;
            color: #0d9488 !important;
            border: 1px solid rgba(13, 148, 136, 0.2) !important;
            font-weight: 600;
        }
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
                            <h5 class="card-title text-primary">🔥 SQL 注入 (UNION / 盲注 / 預存程序)</h5>
                            <p class="card-text text-muted flex-grow-1">使用 Prepared Statements 參數化查詢，並針對預存程序呼叫進行安全綁定。</p>
                            <a href="/sqli_variants.php" class="btn btn-outline-primary mt-2">進入 SQL 注入變體演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📦 第三方套件與經典元件漏洞</h5>
                            <p class="card-text text-muted flex-grow-1">使用反序列化白名單、日誌禁用解析與信箱命令跳脫，安全抵禦已知漏洞。</p>
                            <a href="/component_vulnerabilities.php" class="btn btn-outline-primary mt-2">進入不安全元件演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📖 專屬：反射型 XSS 安全頁</h5>
                            <p class="card-text text-muted flex-grow-1">使用 htmlspecialchars() 對輸出進行編碼，防止惡意 JavaScript 執行。</p>
                            <a href="/xss_reflected.php" class="btn btn-outline-primary mt-2">進入反射型 XSS</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">💬 專屬：預存型 XSS 安全頁</h5>
                            <p class="card-text text-muted flex-grow-1">對所有寫入與輸出的欄位執行輸出編碼，徹底消弭儲存型 XSS 威脅。</p>
                            <a href="/xss_stored.php" class="btn btn-outline-primary mt-2">進入預存型 XSS</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">🌐 專屬：DOM-based XSS 安全頁</h5>
                            <p class="card-text text-muted flex-grow-1">前端使用安全的 textContent 操作 DOM，防止任何惡意 HTML 特徵載入。</p>
                            <a href="/xss_dom.php" class="btn btn-outline-primary mt-2">進入 DOM-based XSS</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📋 HTML 表單安全配置與防禦</h5>
                            <p class="card-text text-muted flex-grow-1">使用 POST 提交、停用 Autocomplete，並實作 Email、選單與整數型態之後端嚴格校驗。</p>
                            <a href="/form_risks.php" class="btn btn-outline-primary mt-2">進入表單風險演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📖 課程查詢系統</h5>
                            <p class="card-text text-muted flex-grow-1">使用 PDO Prepared Statements 參數化查詢，徹底根除 SQL 注入漏洞。</p>
                            <a href="/courses.php" class="btn btn-outline-primary mt-2">進入課程查詢</a>
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
                            <h5 class="card-title text-primary">📂 檔案引入防禦 (LFI / RFI 白名單)</h5>
                            <p class="card-text text-muted flex-grow-1">使用硬編碼檔案白名單，徹底阻絕使用者控制 include 檔案路徑與網址的可能。</p>
                            <a href="/file_inclusion.php" class="btn btn-outline-primary mt-2">進入檔案引入演練</a>
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
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📍 行動定位打卡 (安全設計)</h5>
                            <p class="card-text text-muted flex-grow-1">使用參數化查詢寫入定位，安全渲染網頁以防 DOM XSS，並在後端強制校驗使用者權限以防止 IDOR 越權。</p>
                            <a href="/checkin.php" class="btn btn-outline-primary mt-2">進入定位打卡</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">🔍 自訂個資遮罩防禦驗證</h5>
                            <p class="card-text text-muted flex-grow-1">對所有敏感個資實施遮罩與去識別化，使 ZAP 被動腳本無法匹配，驗證防禦有效性。</p>
                            <a href="/pii_leakage.php" class="btn btn-outline-primary mt-2">進入個資檢測演練</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">🎨 版面主題色自訂 (CSS 注入防護)</h5>
                            <p class="card-text text-muted flex-grow-1">限制自訂 CSS 樣式只能輸入白名單格式，或完全轉義，防止任何 CSS Injection 危害。</p>
                            <a href="/css_injection.php" class="btn btn-outline-primary mt-2">進入主題自訂</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">⚡ AJAX 查詢 (安全防護)</h5>
                            <p class="card-text text-muted flex-grow-1">異步 AJAX 查詢，後端強制執行垂直/水平權限校驗，個資遮蔽，且前端以文字渲染防止 DOM XSS。</p>
                            <a href="/ajax_vulnerability.php" class="btn btn-outline-primary mt-2">進入 AJAX 測試</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">🌐 SSRF 預覽 (安全過濾)</h5>
                            <p class="card-text text-muted flex-grow-1">遠端圖片預覽，後端過濾私有 IP 段與非 HTTP/HTTPS 協定，避免內部伺服器被探測攻擊。</p>
                            <a href="/ssrf_demo.php" class="btn btn-outline-primary mt-2">進入 SSRF 測試</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title text-primary">📁 XXE 匯入 (關閉外部實體)</h5>
                            <p class="card-text text-muted flex-grow-1">上傳名單 XML 解析，後端強制關閉外部實體解析與 DTD 加載，杜絕惡意檔案讀取與 XXE 危害。</p>
                            <a href="/xxe_demo.php" class="btn btn-outline-primary mt-2">進入 XXE 測試</a>
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
                    <a href="/admin/index.php" class="btn btn-dark w-100 mb-2 text-wrap" style="white-space: normal; word-break: break-word;">🔒 管理員後台 (限 Admin 存取)</a>
                    <a href="/clickjacking_poc.php" class="btn btn-outline-warning w-100 mb-2 text-wrap" style="white-space: normal; word-break: break-word;">🖼️ 點擊劫持 PoC (clickjacking_poc.php)</a>
                    <p class="text-muted text-center text-small mb-0 mt-2">
                        * 提示：修正版無 debug.php 頁面以防配置外洩。
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer-section">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4">
                <span class="footer-logo">🛡️ VulnCampus PHP Lab (修正版)</span>
                <p class="text-muted small pr-md-4">
                    本教學靶場專為《網站弱點檢測與安全改善：OWASP ZAP 應用》設計，模擬「校園活動、課程查詢與報名平台」之常見漏洞架構，帶領學員實踐 OWASP Top 10 的檢測與修補技術。
                </p>
                <div class="mt-3">
                    <span class="badge badge-success-glow">🟢 安全修補完成</span>
                    <span class="badge bg-secondary text-white">Apache 8081</span>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <h5 class="text-white font-weight-bold mb-3">設計與學術合作</h5>
                <ul class="list-unstyled small">
                    <li class="mb-2"><strong>設計者：</strong> 鄭郁翰 老師 (Yu-Han Cheng)</li>
                    <li class="mb-2"><strong>機構：</strong> <a href="https://www.ksu.edu.tw" target="_blank" rel="noopener noreferrer" class="footer-link">崑山科技大學 (Kun Shan University)</a></li>
                    <li class="mb-2"><strong>聯絡方式：</strong> <a href="mailto:yhcheng@mail.ksu.edu.tw" class="footer-link">yhcheng@mail.ksu.edu.tw</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="text-white font-weight-bold mb-3">安全防禦說明</h5>
                <p class="small text-muted">
                    本站（修正版）已針對所有已知漏洞進行安全修補與防禦加固。學員可使用 ZAP 漏洞掃描器，與弱點版（Port 8080）進行對比，觀察漏洞修補前後之掃描結果差異，驗證安全修補的有效性。
                </p>
            </div>
        </div>
        <div class="border-top border-secondary mt-4 pt-4 text-center small text-muted">
            <p class="mb-0">© 2026 崑山科技大學 鄭郁翰老師 (Yu-Han Cheng). All Rights Reserved. 僅限學術與合法安全研究使用。</p>
        </div>
    </div>
</footer>

<!-- 修補重點：引入最新穩定版 jQuery 與 Bootstrap 5 JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<script>
    // 安全版防護：絕不在 localStorage 儲存敏感個資與 API Token
    // 同時在登出或載入時主動清除可能殘留的敏感快取
    <?php if (!isset($_SESSION['user'])): ?>
        localStorage.removeItem('user_session');
    <?php endif; ?>
</script>
</body>
</html>

