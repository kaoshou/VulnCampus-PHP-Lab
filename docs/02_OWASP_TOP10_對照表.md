# 02 OWASP Top 10:2025 對照表

本文件詳細列出 **VulnCampus PHP Lab** 靶場中，針對 **OWASP Top 10:2025** 十大網站安全風險的具體漏洞設計、ZAP 檢測性、手動驗證方式與安全修補防護對照。

---


## 🔍 全站漏洞快速導覽與彙整表 (Vulnerability Map)

以下為本靶場中所有設計的漏洞點彙整，包含那些包裹在其他複雜頁面中的子漏洞（如 CSRF、CORS 等），便於教師上課教學演示與學員進行檢測對照：

| # | 漏洞名稱 (Vulnerability) | 弱點版檔案 / URL | 首頁進入路徑/連結位置 | 所屬分類 (OWASP 2025) | 測試驗證說明 |
|---|---|---|---|---|---|
| 1 | **SQL 注入 (SQLi) 綜合演練** | `/login.php`<br>`/courses.php?q=X`<br>`/course_detail.php?id=X`<br>`/api/ajax_user_info.php`<br>`/sqli_variants.php` | 「📖 課程查詢系統」或「🔥 SQL 注入...」卡片；或右上角「前往登入」 | A03:2025-注入攻擊 | 包括 UNION 查詢、布林盲注、時間延遲盲注，以及預存程序 (Stored Procedure) 注入等。 |
| 2 | **反射型 XSS (Reflected)** | `/xss_reflected.php?keyword=X` | 「📖 專屬：反射型 XSS 測試頁」卡片 | A03:2025-注入攻擊 | 輸入 `<script>alert(1)</script>` 觸發彈窗。 |
| 3 | **預存型 XSS (Stored)** | `/xss_stored.php` | 「💬 專屬：預存型 XSS 測試頁」卡片 | A03:2025-注入攻擊 | 在留言板輸入 `<script>alert(document.cookie)</script>`，使所有造訪者皆被竊取 Cookie。 |
| 4 | **DOM-based XSS** | `/xss_dom.php#name=X`<br>`/checkin.php`<br>`/ajax_vulnerability.php` | 「🌐 專屬：DOM-based XSS 測試頁」或「📍 行動定位打卡」等卡片 | A03:2025-注入攻擊 | 透過 URL hash、不安全的 JSON 渲染至 `innerHTML` 來執行惡意 Script。 |
| 5 | **跨站請求偽造 (CSRF)** | `/form_risks.php` (信用卡表單)<br>`/event_register.php` (活動報名)<br>`/api/admin_approve.php` (審核 API) | 「📋 不安全 HTML 表單配置」與「📅 活動報名」卡片中之表單 | A02:2025-安全設定缺陷 | 敏感功能缺少 Anti-CSRF Token，可被惡意網站跨站發起敏感請求。 |
| 6 | **物件層級越權 (BOLA/IDOR)** | `/profile.php?id=X`<br>`/api/profile.php`<br>`/api/checkin_history.php`<br>`/api/ajax_user_info.php` | 「👤 個人資料 (IDOR / 越權)」或「⚡ AJAX 查詢」等卡片 | A01:2025-權限控制缺失 | 修改 URL 或 API 的 ID 參數（如將自己的 ID 改為管理員 ID）直接存取他人資料。 |
| 7 | **隱藏欄位參數竄改 (Mass Assignment)** | `/profile.php` | 「👤 個人資料 (IDOR / 越權)」卡片 | A01:2025-權限控制缺失 | 修改 HTML 中的隱藏欄位 `name="role"` 值為 `admin`，送出修改後直接升權。 |
| 8 | **任意檔案下載 (Path Traversal)** | `/download.php?file=X` | 「📥 檔案下載 (Path Traversal)」卡片 | A01:2025-權限控制缺失 | 參數輸入 `../src/db.php` 可穿越目錄下載伺服器敏感原始碼。 |
| 9 | **系統命令注入 (Command Injection)** | `/admin/ping.php` (後台) | 右下角「🔒 管理員後台 (admin/)」內之 Ping 測試功能 | A03:2025-注入攻擊 | 網頁 Ping 測試功能輸入 `127.0.0.1; whoami` 執行系統任意指令。 |
| 10 | **任意檔案上傳 Webshell** | `/upload.php` | 「📤 大頭貼上傳」卡片 | A01:2025-權限控制缺失 | 上傳包含 `<?php system($_GET['cmd']); ?>` 的 `shell.php` 取得伺服器控制權。 |
| 11 | **SSRF (伺服器端請求偽造)** | `/ssrf_demo.php` | 「🌐 SSRF 預覽 (內網探測)」卡片 | A01:2025-權限控制缺失 | 圖片預覽功能輸入 `file:///etc/passwd` 或 `http://db:3306` 探測內網。 |
| 12 | **XML 外部實體注入 (XXE)** | `/xxe_demo.php` | 「📁 XXE 匯入 (外部實體解析)」卡片 | A01:2025-權限控制缺失 | XML 匯入處提交包含外部實體定義的 XML，讀取伺服器內部敏感檔案。 |
| 13 | **CORS 跨來源資源共用漏洞** | `/api/ajax_user_info.php` | 「⚡ AJAX 查詢」卡片（異步調用 API） | A02:2025-安全設定缺陷 | 伺服器配置 Access-Control-Allow-Origin 反射 Origin 且 Credentials 為 true，致敏感資料可被跨域讀取。 |
| 14 | **會話固定漏洞 (Session Fixation)** | `/login.php` | 右上角「前往登入」進入登入頁面 | A01:2025-權限控制缺失 | 登入前後的 Session ID 未重置更新，可被攻擊者利用固定會話進行劫持。 |
| 15 | **密碼重設繞過 (Weak Reset Token)** | `/reset_password.php` | 「🔑 密碼重設 (邏輯缺陷)」卡片 | A07:2025-識別與身分驗證失效 | 密碼重設連結使用可預測或弱強度的 Token，且後端未驗證 Token 時效。 |
| 16 | **日誌紀錄缺失 (Logging Failure)** | `/admin/logs.php` | 右下角「🔒 管理員後台 (admin/)」內之系統日誌區 | A09:2025-安全記錄和監控失效 | 系統僅記錄正常登入，被動忽略登入失敗、越權嘗試等關鍵防禦日誌。 |
| 17 | **點擊劫持 (Clickjacking)** | `/clickjacking_poc.php` (測試) | 右下角側邊欄「🖼️ 點擊劫持 PoC (clickjacking_poc.php)」按鈕 | A02:2025-安全設定缺陷 | 網站未配置 X-Frame-Options，可被第三方 iframe 嵌入並重疊按鈕進行劫持。 |
| 18 | **不安全瀏覽器快取** | `/profile.php` | 「👤 個人資料 (IDOR / 越權)」卡片 | A02:2025-安全設定缺陷 | 機敏個人資料頁未設定 Cache-Control 標頭，使敏感頁面留存於公用電腦快取。 |
| 19 | **CSS 注入 (CSS Injection)** | `/css_injection.php` | 「🎨 版面主題色自訂 (CSS Injection)」卡片 | A03:2025-注入攻擊 | 允許使用者自訂 CSS 樣式，攻擊者可藉此載入外部惡意字型或背景圖外洩資料。 |
| 20 | **Upload DoS (上傳拒絕服務)** | `/upload.php` | 「📤 大頭貼上傳」卡片之檔案上傳表單 | A01:2025-權限控制缺失 | 上傳功能未檢查檔案大小，攻擊者可上傳大量大檔案耗盡磁碟空間引發 DoS。 |
| 21 | **商業邏輯漏洞 (負數報名與超賣)** | `/events.php`<br>`/event_register.php` | 「📅 活動報名」或「進入活動列表」卡片 | A01:2025-權限控制缺失 | 報名人數傳入負數可扣減金額，或高併發下發生超賣現象（缺少行鎖）。 |
| 22 | **敏感資訊洩漏 (Debug/LocalStorage/Backups)** | `/debug.php`<br>`/login.php`<br>`/db.php.bak`<br>`/index.php.old`<br>`/backup.zip` | 右下角側邊欄「🔍 系統偵錯頁 (debug.php)」或直存備份路徑 | A02:2025-安全設定缺陷 | 洩漏系統配置，或留存 `.bak`、`.old`、`.zip` 等敏感原始碼備份檔案在 Web 公開目錄中被惡意下載。 |
| 23 | **自動化防護缺失 (Lack of Anti-Automation)** | `/login.php` | 右上角「前往登入」登入端點 | A07:2025-識別與身分驗證失效 | 登入端點無失敗次數限制與驗證碼防護，易遭暴力破解。修正版加入帳號失敗鎖定與 SVG 圖形驗證碼防護。 |
| 24 | **異常條件處理不當 (Stack Trace 洩漏)** | `/courses.php?q[]=X`<br>`/xss_stored.php` 等 | 「📖 課程查詢系統」或「💬 專屬：預存型 XSS 測試頁」卡片 | A10:2025-異常條件處理不當 | 輸入單引號 `'` 爆出 SQL 結構；或傳入陣列型態參數觸發 unhandled TypeError 爆出 Stack Trace。 |
| 25 | **HSTS 標頭缺失 (Missing HSTS)** | 全站頁面 (HTTP Headers) | 存取全站任一網頁 (F12 檢查 Response Headers) | A02:2025-安全設定缺陷 | 弱點版未傳送 `Strict-Transport-Security`，允許降級為不安全連線。修正版強制防護並開啟預載。 |
| 26 | **Referrer Policy 標頭缺失** | 全站頁面 (HTTP Headers) | 存取全站任一網頁 (F12 檢查 Response Headers) | A02:2025-安全設定缺陷 | 弱點版未設定 Referrer 策略，易在外聯時洩漏 URL 中的 Session/Token。修正版開啟安全過濾。 |
| 27 | **會話閒置逾時缺失 (Session Lifetime)** | 全站頁面 (Session Lifecycle) | 登入後在全站任一頁面閒置超過 10 分鐘以上 | A07:2025-識別與身分驗證失效 | 弱點版會話無限期保持。修正版限制會話閒置逾時為 10 分鐘，過期自動重置會話並重導至登入。 |
| 28 | **憑證永不過期 (Token Never Expires)** | `/reset_password.php` | 「🔑 密碼重設 (邏輯缺陷)」卡片 | A07:2025-識別與身分驗證失效 | 弱點版密碼重設 Token 永久有效且缺少防重放。修正版 Token 設定 15 分鐘時效，使用後即作廢。 |
| 29 | **不安全元件漏洞 (Vulnerable Components)** | `/component_vulnerabilities.php` | 「📦 第三方套件與經典元件漏洞」卡片 | A06:2025-安全漏洞與過時元件 | 包含 PHP 不安全反序列化、PHPMailer (CVE-2016-10033) 命令注入、Log4Shell (CVE-2021-44228) 與 OpenSSL Heartbleed (CVE-2014-0160) 模擬對照。 |
| 30 | **敏感目錄與路徑洩漏 (robots/sitemap)** | `/robots.txt`<br>`/sitemap.xml` | 存取 `http://localhost:8080/robots.txt` 或 `/sitemap.xml` | A02:2025-安全設定缺陷 | 將敏感管理目錄或偵錯檔案暴露在 robots.txt 或 sitemap.xml 中，無意間向攻擊者提供系統架構地圖。 |
| 31 | **金鑰暴露於前端 (API Key on Frontend)** | `/checkin.php` | 點擊「📍 行動定位打卡」卡片，View Source 檢視前端程式碼 | A02:2025-安全設定缺陷 | 將敏感第三方 API 金鑰（如 Google Maps API 金鑰）直接硬編碼於前端 JavaScript 程式碼中，極易被自動化探測或洩漏。 |
| 32 | **反向分頁劫持 (Reverse Tabnabbing)** | `/index.php` | 造訪首頁底端「設計與學術合作」下的「機構連結」 | A02:2025-安全設定缺陷 | `target="_blank"` 的外部連結缺少 `rel="noopener noreferrer"` 屬性，允許被開啟的惡意分頁透過 `window.opener` 劫持並修改父頁面的網址。 |
| 33 | **伺服器版本資訊洩漏 (Version Disclosure)** | 全站頁面 | 存取全站任一網頁 (F12 檢查 Response Headers) | A02:2025-安全設定缺陷 | 弱點版在 HTTP 響應標頭中回傳詳細的 Apache、OpenSSL 與 PHP 版本編號，讓攻擊者輕易進行定向漏洞分析。 |
| 34 | **電子郵件地址洩漏 (Email Address Disclosure)** | `/profile.php`<br>`/api/profile.php`<br>`/api/ajax_user_info.php`<br>`/admin/export_registrations.php` | 登入後存取個人資料、API 查詢或管理員後台匯出名冊 | A02:2025-安全設定缺陷 | 弱點版直接將使用者的明文電子郵件地址輸出於網頁原始碼及 API 回應中，極易被自動化爬蟲搜集。修正版對信箱主體進行星號遮罩。 |
| 35 | **檔案引入漏洞 (LFI / RFI)** | `/file_inclusion.php` | 「📂 檔案引入漏洞 (LFI / RFI)」卡片 | A03:2025-注入攻擊 | 弱點版動態加載模組且未做任何路徑過濾，攻擊者可藉此引入本地敏感檔案（LFI）或外部惡意網址（RFI）執行任意程式碼。修正版採用硬編碼白名單限制。 |
| 36 | **明文密碼儲存 (Plaintext Password Storage)** | `/login.php`<br>`/reset_password.php` | 右上角「前往登入」進入登入或重設密碼頁 | A04:2025-加密機制失效 | 弱點版將使用者密碼直接以明文 (Plaintext) 形式儲存於資料庫中，一旦資料庫洩漏即完全暴露。修正版使用強雜湊算法 Bcrypt。 |
| 37 | **子資源完整性缺失 (Missing SRI)** | 全站頁面 (CDN script/link 標籤) | 存取全站任一網頁，點選右鍵「檢視網頁原始碼」 | A02:2025-安全設定缺陷 | 弱點版引入外部 CDN 的 jQuery 與 Bootstrap 時缺少 `integrity` 與 `crossorigin` 屬性，易遭 CDN 投毒劫持。修正版配置了標準 SRI 雜湊值。 |
| 38 | **目錄清單洩漏 (Directory Browsing)** | `/uploads/` | 直接在網址列存取 `http://localhost:8080/uploads/` | A02:2025-安全設定缺陷 | 弱點版開啟了 Apache 目錄瀏覽功能且無 index 檔案，訪客可列出所有已上傳的檔案與 webshell。修正版使用 `Options -Indexes` 封鎖。 |
| 39 | **HTTP 標頭 SQL 注入 (Header SQLi)** | `/login.php` (日誌寫入端點) | 存取登入端點，修改 HTTP Request 中的 `User-Agent` 標頭 | A03:2025-注入攻擊 | 弱點版日誌記錄將 `User-Agent` 標頭內容直接字串拼接寫入 SQL 語句中，ZAP 主動掃描可在此觸發 SQLi 注入。修正版使用參數化查詢。 |

---

## A01:2025 - 權限控制缺失 (Broken Access Control)

### 1. 漏洞頁面與成因
- **頁面**：`/profile.php?id=X`、`/admin/export_registrations.php`、`/api/profile.php`、`/api/admin_approve.php`
- **成因**：
  - 前端以參數 `id` 載入個人檔案，但後端未校驗該 ID 是否為當前登入者本人，造成 **IDOR 水平越權**。
  - 後台敏感功能（名冊下載與審核 API）未在後端程式碼檢驗 Session/Token 的 `role` 角色是否為 `admin`，造成 **垂直越權**。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 難以透過自動化主動掃描識別越權邏輯，但可藉由爬行（Spider）發現未授權可下載的名冊 `/admin/export_registrations.php`。
- **手動驗證**：
  1. 使用 `student01` 帳號登入。
  2. 嘗試存取 `http://localhost:8080/profile.php?id=3` (檢視 student02 的個資)。
  3. 嘗試存取 `http://localhost:8080/admin/index.php`，驗證低權限帳號能否直接進入後台。

### 3. 程式修補對照 (Vulnerable vs. Fixed)
- **弱點版** (`app-vulnerable/public/profile.php`)：
  ```php
  $id = $_GET['id'] ?? $_SESSION['user']['id'];
  // 後端直接相信 ID 並從資料庫載入
  ```
- **修正版** (`app-fixed/public/profile.php`)：
  ```php
  $id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user']['id'];
  if ($id !== $_SESSION['user']['id'] && $_SESSION['user']['role'] !== 'admin') {
      http_response_code(403);
      die("權限不足！");
  }
  ```

---

## A02:2025 - 安全設定錯誤 (Security Misconfiguration)

### 1. 漏洞頁面與成因
- **頁面**：全站 Header 標頭、Session Cookie 設定、`/debug.php`
- **成因**：
  - 缺乏安全回應標頭（CSP, X-Frame-Options 等），無法防範 Clickjacking 與部分 XSS。
  - Session Cookie 未設定 `HttpOnly` 與 `SameSite=Lax`。
  - `/debug.php` 將系統環境變數與資料庫明文帳密直接公開給外部訪客。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 的 **Passive Scan**（被動掃描）極易掃出全站 Missing Security Headers 警告，並能透過爬行掃描發現並解析 `/debug.php` 的敏感資訊。
- **手動驗證**：在瀏覽器中按 F12 打開開發者工具，檢視 Network 面板中的回應 Headers，或檢查 Application 中的 Session Cookie 是否缺少 HttpOnly 勾選標記。

### 3. 程式修補對照
- **弱點版**：直接調用 `session_start()`，且未輸出安全標頭。
- **修正版** (`app-fixed/src/helpers.php`)：
  ```php
  session_set_cookie_params([
      'httponly' => true,
      'samesite' => 'Lax'
  ]);
  session_start();
  header("X-Frame-Options: DENY");
  header("X-Content-Type-Options: nosniff");
  // 輸出 CSP 標頭...
  ```
  並在修正版中移除或將 `/debug.php` 回傳 404 封鎖。

---

## A03:2025 - 軟體與供應鏈漏洞 (Supply Chain Failures)

### 1. 漏洞頁面與成因
- **頁面**：全站 `/index.php` 原始碼中的 JavaScript 引入。
- **成因**：引入了漏洞已知的舊版 jQuery v1.12.4 與 Bootstrap v4.0.0。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 的 **Retire.js** 外掛模組會自動分析頁面中引入的 JS 版本，並列出已知漏洞 (CVE) 警報。
- **手動驗證**：檢視網頁原始碼，尋找導入 CDN 檔案的版號。

### 3. 程式修補對照
- **弱點版**：
  ```html
  <script src="https://code.jquery.com/jquery-1.12.4.min.js"></script>
  ```
- **修正版**：
  ```html
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  ```

---

## A04:2025 - 加密機制失效 (Cryptographic Failures)

### 1. 漏洞頁面與成因
- **頁面**：`/login.php`、`/reset_password.php`、`/admin/export_registrations.php`
- **成因**：
  - 資料庫密碼直接以明文儲存，存在嚴重資安風險。
  - 重設密碼 Token 使用可預測的 `md5(username)` 規則。
  - 個資（如假身分證字號）在 API 與資料匯出時以明文顯示，無任何加密或遮罩。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 能在主動/被動掃描中，分析傳輸的敏感個資（如身分證字號特徵）並發出警告。
- **手動驗證**：
  - 在弱點版中重設密碼，觀察產生的 Token 連結，發現 student01 的 token 永遠是 `5f4dcc3b5aa765d61d8327deb882cf99` (即 MD5 of student01)。
  - 查看 API 輸出結果，發現包含未遮蔽的身分證字號與手機。

### 3. 程式修補對照
- **弱點版**：
  ```php
  
  $predictable_token = md5($username);
  ```
- **修正版**：
  ```php
  // 密碼 Bcrypt 雜湊與驗證
  $password_hash = password_hash($password, PASSWORD_DEFAULT);
  password_verify($password, $user['password_hash']);
  // 強隨機 Token
  $secure_token = bin2hex(random_bytes(32));
  ```

---

## A05:2025 - 注入式攻擊 (Injection)

### 1. 漏洞頁面與成因
- **頁面**：
  - SQLi：`/courses.php?q=X`、`/course_detail.php?id=X`
  - XSS：反射型（`/courses.php?q=X`）、儲存型（`/messages.php`）
  - Path Traversal：`/download.php?file=X`
  - Command Injection：`/admin/ping.php`
- **成因**：將使用者輸入直接串接到 SQL 語句、網頁輸出、系統命令或檔案讀取路徑中。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 的 **Active Scan** (主動掃描) 極易偵測出 SQL Injection、反射型 XSS、路徑遍歷與命令注入，會發出 **High Risk** 警報並附帶攻擊 Payload 證據。
- **手動驗證**：
  - SQLi：在課程詳細網址輸入 `course_detail.php?id=1 UNION SELECT 1,username,password_hash,role,name,email,7 FROM users`，即可在網頁上看到全校密碼雜湊。
  - XSS：在留言板輸入 `<script>alert(document.cookie)</script>`。
  - Path Traversal：下載 `/download.php?file=../src/db.php`。
  - Command Injection：在診斷頁面輸入 `127.0.0.1; whoami`。

### 3. 程式修補對照
- **SQLi 修補**：
  - 弱：`$pdo->query("SELECT * FROM courses WHERE id = " . $id)`
  - 修正：使用 `prepare` 與 `execute` 參數化查詢。
- **XSS 修補**：
  - 弱：`echo $q;`
  - 修正：`echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8');`
- **Path Traversal 修補**：
  - 弱：`readfile("uploads/" . $file)`
  - 修正：使用 `basename($file)` 限制只能讀取檔名，或使用 ID 查表。
- **Command Injection 修補**：
  - 弱：`shell_exec("ping " . $ip)`
  - 修正：使用 `filter_var($ip, FILTER_VALIDATE_IP)` 限定只能輸入 IP。

---

## A06:2025 - 安全設計缺陷 (Insecure Design)

### 1. 漏洞頁面與成因
- **頁面**：`/event_register.php`
- **成因**：
  - 數量未檢驗正整數，允許輸入負值（例如報名數量 `-1` 件），結帳金額會變負值（抵扣購物車總額）。
  - 單價與最終總價直接相信前端 POST 傳過來的 `price` 隱藏欄位值，容易被改包竄改價格。
  - 活動名額扣減缺乏併發控制與交易行鎖，會造成 **Race Condition (競態條件) 超額報名**。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：自動化工具一般無法掃描此漏洞。必須使用 ZAP 的 **Breakpoint (中斷點)** 修改發送的 HTTP 參數進行手動驗證。
- **手動驗證**：
  - 在報名確認頁面，開啟 ZAP Breakpoint。
  - 點選確認報名，在 ZAP 中攔截請求。
  - 修改 `quantity` 為 `-2`，或修改 `price` 為 `1`，放行請求，觀察是否能以極低金額或負數金額完成報名。

### 3. 程式修補對照
- **弱點版**：直接採用 `$_POST['price'] * $_POST['quantity']` 寫入。
- **修正版** (`app-fixed/public/event_register.php`)：
  ```php
  $quantity = intval($_POST['quantity']);
  if ($quantity <= 0) die("數量錯誤");
  // 價格一律自資料庫 events 查出：
  $final_price = ($db_event_price * $quantity) - $discount;
  // 使用資料庫事務鎖：
  $pdo->beginTransaction();
  $stmt = $pdo->prepare("SELECT quota FROM events WHERE id = :id FOR UPDATE");
  // 名額扣減與插入...
  $pdo->commit();
  ```

---

## A07:2025 - 認證失效 (Authentication Failures)

### 1. 漏洞頁面與成因
- **頁面**：`/login.php`、`/logout.php`
- **成因**：
  - 預設弱密碼 `admin/admin` 容易被爆破。
  - 登入錯誤訊息直接洩漏「帳號不存在」或「密碼錯誤」的細節，提供黑客進行帳號列舉。
  - 登入後未重新產生 Session ID。
  - 登入失敗無次數限制。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 可利用 Fuzzer 進行密碼爆破。
- **手動驗證**：輸入隨機的帳號（如 `notexistuser`），系統若提示「此帳號不存在」，則表示有帳號列舉漏洞。

### 3. 程式修補對照
- **弱點版**：回應「帳號不存在」或「密碼錯誤」。
- **修正版**：
  - 統一回應「帳號或密碼錯誤」。
  - 登入成功加入 `session_regenerate_id(true)`。
  - 資料庫加入失敗登入計數器，連續失敗 5 次鎖定 15 分鐘。

---

## A08:2025 - 軟體與資料完整性失效 (Data Integrity Failures)

### 1. 漏洞頁面與成因
- **頁面**：`/profile.php` (修改個資)
- **成因**：修改個資的 POST 表單中帶有隱藏欄位 `role=student`，後端照單全收並直接更新資料庫中的角色欄位，造成 **Mass Assignment (批量分配) 升權漏洞**。

### 2. ZAP 偵測與手動驗證
- **手動驗證**：
  1. 使用 `student01` 登入。
  2. 前往 `profile.php`，在瀏覽器按 F12，將 `<input type="hidden" name="role" value="student">` 的 value 改成 `admin`。
  3. 按下更新，重新登入，確認角色是否升級為 `admin`，並能否存取管理後台。

### 3. 程式修補對照
- **弱點版**：
  ```php
  $role = $_POST['role'];
  $sql = "UPDATE users SET name = '$name', role = '$role' ...";
  ```
- **修正版**：
  ```php
  // 後端只提取並更新允許的白名單欄位，排除 role 的直接更新
  $stmt = $pdo->prepare("UPDATE users SET name = :name, email = :email ... WHERE id = :id");
  ```

---

## A09:2025 - 紀錄與監控失效 (Logging Failures)

### 1. 漏洞頁面與成因
- **頁面**：後台日誌 `/admin/logs.php`
- **成因**：
  - 系統日誌記錄不完整。弱點版雖然實作了日誌功能並會記錄「登入成功」與「使用者登出」，但卻**故意忽略不記錄**登入失敗 (爆破攻擊)、敏感名冊匯出、越權存取嘗試等關鍵安全防禦事件。
  - 這會給管理者帶來安全假象（Logs 裡都是正常的登入成功，卻看不見正在發生的密碼爆破或資料竊取）。

### 2. 手動驗證
- **手動驗證**：
  1. 在弱點版中，嘗試登入 `admin` 帳號並輸入幾次錯誤密碼，或前往後台匯出名冊。
  2. 以 `admin/admin` 成功登入後，進入後台日誌 `/admin/logs.php` 檢視。
  3. 您會發現日誌裡有「登入成功」紀錄，但剛才的「登入失敗」與「匯出名冊」等惡意嘗試在 Logs 中完全是一片空白！

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/src/helpers.php`)：
  ```php
  // 限制日誌僅記錄登入/登出，過濾並忽略其他特定安全性交易
  $allowed_actions = ["登入成功", "使用者登出"];
  if (!in_array($action, $allowed_actions)) return;
  ```
- **修正版** (`app-fixed/src/helpers.php`)：
  ```php
  // 不設限日誌事件，確實將登入失敗、越權嘗試、名冊下載等安全性交易完整寫入庫中
  $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, ip_address, user_agent) VALUES (?, ?, ?, ?)");
  ```


---

## A10:2025 - 異常條件處理不當 (Mishandling Exceptions)

### 1. 漏洞頁面與成因
- **頁面**：全站資料庫查詢出錯時。
- **成因**：未對資料庫 Exception 做 Catch 處理，或出錯時直接印出詳細 Exception，將資料表結構、參數甚至資料庫密碼暴露出現在前端。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 執行 Active Scan 時，會刻意發送異常符號觸發報錯，檢測回應內容是否包含資料庫異常軌跡 (Database Error Disclosure)。
- **手動驗證**：在 `courses.php` 搜尋欄位輸入單引號 `'`，觀察網頁是否爆出 SQL 語法錯誤的細節。

### 3. 程式修補對照
- **弱點版**：直接讓 PDOException 拋出至前端。
- **修正版**：使用 try-catch 捕獲 Exception，詳細錯誤記錄在內部伺服器 log，向前端使用者僅顯示「系統目前無法完成查詢，請稍後再試。」的安全模糊訊息。

---

## 補充演練 - 網址轉向漏洞 (Open Redirect)

### 1. 漏洞頁面與成因
- **頁面**：`/redirect.php?url=X` 或是 `/login.php?redirect=X`
- **成因**：系統未對 GET 參數中帶入的跳轉網址（url 或 redirect）進行白名單檢查。後端直接調用跳轉（或透過前端 HTML 重新整理跳轉），導致攻擊者可以修改 url 參數為外部釣魚網站，誘騙使用者。

### 2. ZAP 偵測與手動驗證
- **ZAP 偵測**：ZAP 的 **Active Scan** 會對跳轉參數進行測試，並發出 `Open Redirect` 警報 (🟡 Medium Risk)。
- **手動驗證**：
  1. 在弱點版中存取連結：`http://localhost:8080/redirect.php?url=https://www.google.com`。
  2. 觀察頁面，系統顯示即將跳轉的目標為 `https://www.google.com`，並在 2 秒後直接跳轉出站，毫無阻攔。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/redirect.php`)：
  ```php
  $url = $_GET['url'] ?? '/index.php';
  // 直接置入跳轉目標，無過濾
  ```
- **修正版** (`app-fixed/public/redirect.php`)：
  ```php
  // 檢查是否為外部網址，若非本機信任主機則判定不安全
  if (preg_match('/^(https?:)?\/\//i', $url)) {
      $host = parse_url($url, PHP_URL_HOST);
      if ($host !== 'localhost' && $host !== $_SERVER['HTTP_HOST']) {
          $url = '/index.php'; // 強制導向回首頁安全區
      }
  }
  ```



---

## 補充演練 - AJAX 漏洞與 API 安全 (BOLA / IDOR / DOM XSS / CORS)

### 1. 漏洞頁面與成因
- **頁面**：`/ajax_vulnerability.php` 與 `/api/ajax_user_info.php`
- **成因**：
  - 後端 API 端點 `/api/ajax_user_info.php` 未對當前用戶進行權限驗證 (BOLA/IDOR)，任何人修改 `id` 即可查詢任意用戶的個資。
  - API 直接回傳整行包含敏感欄位 (如 MD5 密碼雜湊與身分證) 的 JSON，造成敏感資訊過度暴露。
  - CORS 設定過寬，允許任何來源並附帶憑證讀取個資。
  - 前端 JavaScript 在取得 AJAX 的 JSON 響應後，直接使用 `innerHTML` 渲染至 DOM 中，若資料庫中包含惡意腳本會引發 DOM-based XSS 攻擊。

### 2. ZAP 檢測性與手動驗證
- **ZAP 檢測**：ZAP 的 Active Scan 會偵測到 API 中的 SQL Injection 漏洞，AJAX Spider 亦會報出 DOM-based XSS 與 CORS 錯誤配置。
- **手動驗證**：
  - 登入 `student01`，修改輸入框為 ID `1` (管理員)，點選查詢，可成功異步加載出管理員的學號、身分證字號、電話、信箱及密碼 MD5。
  - 在輸入框輸入 `2 UNION SELECT 1,username,password_hash,role,name,email,7,8,9,10,11,12 FROM users` 測試 SQLi。
  - 以 SQL 注入拼接 script 標籤測試 DOM XSS。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/api/ajax_user_info.php`)：
  ```php
  // 缺乏 Session 檢查且直接拼接 SQL
  $sql = "SELECT * FROM users WHERE id = " . $id;
  ```
- **修正版** (`app-fixed/public/api/ajax_user_info.php` 與前端)：
  ```php
  // 後端驗證權限與使用 Prepared Statement 且遮蔽個資
  if ($_SESSION['user']['role'] !== 'admin' && $_SESSION['user']['id'] != $id) {
      http_response_code(403);
      exit;
  }
  // 前端渲染使用安全的 textContent
  td.textContent = val;
  ```

---

## 補充演練 - SSRF (伺服器端請求偽造)

### 1. 漏洞頁面與成因
- **頁面**：`/ssrf_demo.php`
- **成因**：後端程式接收使用者傳入的 URL 去獲取圖片預覽時，未使用白名單限制且未過濾私有 IP 位址，直接使用 `file_get_contents` 對該網址發起 HTTP 請求，導致攻擊者可利用伺服器作為跳板，探測內網服務與本地檔案。

### 2. ZAP 檢測性與手動驗證
- **ZAP 檢測**：ZAP Active Scan 會發送內網與外部 DNS 探測以偵測 SSRF。
- **手動驗證**：輸入 `file:///etc/passwd` 讀取本地檔案，或輸入 `http://db:3306` / `http://localhost:8082` 探測 Docker 內部網絡服務。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/ssrf_demo.php`)：
  ```php
  $response = file_get_contents($preview_url);
  ```
- **修正版** (`app-fixed/public/ssrf_demo.php`)：
  ```php
  // 解析 URL 並限制僅能使用 http/https，解析 DNS 後過濾 RFC1918 私有 IP 段 (如 127.0.0.1 等)
  $ip = gethostbyname($parts['host']);
  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
      die('不安全的網址');
  }
  ```

---

## 補充演練 - XXE (XML 外部實體注入)

### 1. 漏洞頁面與成因
- **頁面**：`/xxe_demo.php`
- **成因**：後端接收 XML 格式上傳匯入學生資料時，開啟了外部實體解析與外部 DTD 載入，導致攻擊者可藉由定義 XML 外部實體 (Entity)，讀取伺服器內部檔案 (LFI) 或進行內部探測。

### 2. ZAP 檢測性與手動驗證
- **ZAP 檢測**：ZAP 主動掃描會發送含 external entity 的 XML，根據回顯內容判斷 XXE 漏洞。
- **手動驗證**：上傳包含 `SYSTEM "file:///etc/passwd"` 實體的 XML 進行匯入，驗證結果是否包含系統檔案內容。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/xxe_demo.php`)：
  ```php
  // 顯式開啟 LIBXML_NOENT | LIBXML_DTDLOAD 以載入外部實體
  $dom->loadXML($xml_input, LIBXML_NOENT | LIBXML_DTDLOAD);
  ```
- **修正版** (`app-fixed/public/xxe_demo.php`)：
  ```php
  // 保持預設安全解析 (不帶 LIBXML_NOENT 參數)，啟用 libxml_use_internal_errors 避免錯誤洩漏
  libxml_use_internal_errors(true);
  $dom->loadXML($xml_input);
  ```

---

## 補充演練 - Git Repository 外洩

### 1. 漏洞頁面與成因
- **頁面**：`/public/.git/config`
- **成因**：網站部署時未將開發階段的 `.git` 隱藏目錄移除，且 Web 伺服器並未配置阻斷存取規則，導致攻擊者可直接下載 `.git/config` 或透過工具還原整站原始碼。

### 2. ZAP 檢測性與手動驗證
- **ZAP 檢測**：ZAP 被動掃描會嘗試請求 `/.git/config`，並在成功獲取時發出 `Git Disclosure` 警報。
- **手動驗證**：直接存取 `http://localhost:8080/.git/config`。

### 3. 程式修補對照
- **弱點版**：無任何攔截配置，`.git` 直接暴露於網頁目錄。
- **修正版** (`app-fixed/public/.htaccess`)：
  ```apache
  # 配置全域攔截以點 (.) 開頭的隱藏目錄
  RedirectMatch 403 /\..*$
  ```

---

## 補充演練 - Upload DoS (上傳拒絕服務)

### 1. 漏洞頁面與成因
- **頁面**：`/upload.php`
- **成因**：上傳端點未限制檔案大小，也未限制單一會話上傳容量，容易被攻擊者高頻率上傳超大垃圾檔案，塞爆硬碟造成 Disk Exhaustion 拒絕服務。

### 2. ZAP 檢測性與手動驗證
- **手動驗證**：分析原始碼，發現並未校驗 `$_FILES['avatar']['size']` 參數。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/upload.php`)：
  ```php
  // 直接搬移檔案，無 size 限制
  move_uploaded_file($file_tmp, $destination);
  ```
- **修正版** (`app-fixed/public/upload.php`)：
  ```php
  // 限制最大 2MB
  $max_size = 2 * 1024 * 1024;
  if ($_FILES['avatar']['size'] > $max_size) {
      die('檔案過大');
  }
  ```
