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
| 10 | **任意檔案上傳 Webshell** | `/upload.php`<br>`/upload_bypass_html.php`<br>`/upload_bypass_js.php`<br>`/upload_bypass_backend.php` | 「📤 大頭貼上傳」卡片 | A01:2025-權限控制缺失 | 拆分為三個關卡：純 HTML accept 限制繞過、前端 JS 限制繞過與不安全後端 Content-Type 標頭檢查繞過。 |
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
| 40 | **底層緩衝區溢位 (Buffer Overflow)** | `/buffer_overflow.php?input=X` | 「💥 底層緩衝區溢位...」卡片 | A02:2025-安全設定缺陷 (CWE-120) | 呼叫底層 C 程式且未限制字串長度，輸入超長字串會覆寫堆疊造成系統崩潰（Segment Fault）。修正版進行長度驗證與採用安全複製。 |
| 41 | **EXIF 中繼資料注入 (EXIF Injection)** | `/exif_vulnerability.php` | 「📷 EXIF 中繼資料注入...」卡片 | A03:2025-注入攻擊 (CWE-79 / CWE-89) | 讀取圖片 EXIF 中繼資料寫入資料庫，但寫入時未參數化 (SQLi) 且展示時未轉義 (Stored XSS)。 |
| 42 | **日誌敏感資訊外洩 (CWE-532)** | `/login.php` (登入端點) | 右上角「前往登入」進入登入頁面 | A09:2025-安全記錄和監控失效 | 登入失敗與登入成功日誌將使用者輸入的明文密碼直接寫入稽核日誌資料庫中，造成密碼洩漏。 |
| 43 | **Switch 缺少 break 越權 (CWE-484)** | `/admin/logs.php` | 右下角管理員後台「稽核日誌」功能 | A01:2025-權限控制缺失 | 權限判斷 Switch 中漏寫 break 導致 Fall-through 授予低權限角色 (student/teacher) 越權存取管理員日誌。 |
| 44 | **缺少自訂錯誤頁面 (CWE-756)** | 全站頁面 (無效 URL) | 存取全站不存在的頁面 (例如 `/notexist`) | A02:2025-安全設定缺陷 | 弱點版使用 Apache 預設錯誤頁面，洩漏 Apache/PHP 詳細版本。修正版配置自訂安全錯誤頁。 |

---

## A01:2025 - 權限控制缺失 (Broken Access Control)

### 0. 對應 CWE
- [CWE-284 (Improper Access Control)](https://cwe.mitre.org/data/definitions/284.html) - 存取控制不當
- [CWE-639 (Authorization Bypass Through User-Controlled Key)](https://cwe.mitre.org/data/definitions/639.html) - 物件層級越權 (IDOR)
- [CWE-22 (Improper Limitation of a Pathname to a Restricted Directory)](https://cwe.mitre.org/data/definitions/22.html) - 路徑穿越 (Path Traversal)
- [CWE-434 (Unrestricted Upload of File with Dangerous Type)](https://cwe.mitre.org/data/definitions/434.html) - 任意檔案上傳 Webshell

### 1. 漏洞頁面與成因
- **頁面**：`/profile.php?id=X`、`/admin/export_registrations.php`、`/api/profile.php`、`/upload.php`（導引大廳）、`/upload_bypass_html.php`、`/upload_bypass_js.php`、`/upload_bypass_backend.php`
- **成因**：
  - 前端以參數 `id` 載入個人檔案，但後端未校驗該 ID 是否為當前登入者本人，造成 **IDOR 水平越權**。
  - 後台敏感功能（名冊下載與審核 API）未在後端程式碼檢驗 Session/Token 的 `role` 角色是否為 `admin`，造成 **垂直越權**。
  - 大頭貼上傳關卡漏洞成因：
    - **第一關 (HTML 驗證缺陷 - `/upload_bypass_html.php`)**：僅使用 HTML 的 `accept` 屬性進行檔案類型提示篩選，可在檔案選擇框中手動切換為「所有檔案 (*.*)」或 F12 移除屬性直接繞過。
    - **第二關 (前端 JS 驗證缺陷 - `/upload_bypass_js.php`)**：僅在瀏覽器端使用 JavaScript 在 `onsubmit` 時檢查副檔名。能被攔截工具在中途輕易竄改檔名繞過。
    - **第三關 (後端弱驗證缺陷 - `/upload_bypass_backend.php`)**：只校驗用戶端傳送的 `Content-Type` 標頭 (如 `image/png`)，一旦被攻擊者在傳輸中竄改標頭，後端即會放行 `.php` Webshell 檔案上傳。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 的 **Active Scan** 可以針對這三個上傳分頁進行主動掃描，測試不同副檔名與 Content-Type 的惡意程式碼。
- **手動驗證與繞過步驟**：
  1. **第一關測試**：進入 `/upload_bypass_html.php`。點選上傳，彈出對話框預設過濾非圖片。將檔案選擇視窗右下角過濾切換為「所有檔案 (*.*)」即可選取並上傳 <code>shell.php</code>；或者使用瀏覽器 F12 審查元素將 <code>accept="..."</code> 屬性直接刪除。
  2. **第二關測試**：進入 `/upload_bypass_js.php`。直接選取 `shell.php` 會觸發 JS 彈窗警告。請先選取一個合法的 `image.jpg`，開啟 ZAP 的 **Breakpoint (中斷點)**。點選送出表單，在 ZAP 攔截到請求時，將該區段的 `filename="image.jpg"` 修改為 `filename="shell.php"` 送出。
  3. **第三關測試**：進入 `/upload_bypass_backend.php`。若直接送出 PHP 檔名，後端會因為 `Content-Type: application/x-php` 擋下。請於 ZAP 攔截到請求時，同時將 `Content-Type: application/x-php` 修改為 `Content-Type: image/png` 後放行，即可繞過後端對 MIME 標頭的信任。

### 3. 程式修補對照 (Vulnerable vs. Fixed)
- **弱點版** (`app-vulnerable/public/profile.php` 與 `upload.php`)：
  * **個人資料 IDOR**：
  ```php
  $id = $_GET['id'] ?? $_SESSION['user']['id'];
  // 後端直接相信 ID 並從資料庫載入
  ```
  * **大頭貼弱驗證上傳**：
  ```php
  $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
  // 脆弱點：信任了可被篡改的客戶端標頭 type
  if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
      die("格式不符！");
  }
  $destination = $upload_dir . $_FILES['avatar']['name'];
  move_uploaded_file($file_tmp, $destination);
  ```
- **修正版** (`app-fixed/public/profile.php` 與 `upload.php`)：
  * **個人資料 IDOR 防禦**：
  ```php
  $id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user']['id'];
  if ($id !== $_SESSION['user']['id'] && $_SESSION['user']['role'] !== 'admin') {
      http_response_code(403);
      die("權限不足！");
  }
  ```
  * **大頭貼安全上傳 (雙重驗證與 MIME 真實檢測)**：
  ```php
  $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
  $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
  
  // 防護核心：利用 finfo 讀取檔案二進位特徵，不相信 Content-Type 標頭
  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime_type = $finfo->file($file_tmp);
  $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif'];
  
  if (in_array($file_ext, $allowed_extensions) && in_array($mime_type, $allowed_mimes)) {
      // 隨機命名並寫入
      $new_file_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
      move_uploaded_file($file_tmp, $upload_dir . $new_file_name);
  }
  ```

---

## A02:2025 - 安全設定錯誤 (Security Misconfiguration)

### 1. 漏洞頁面與成因
- **頁面**：全站 Header 標頭、Session Cookie 設定、`/debug.php`、`/cookie_stealer.php`
- **成因**：
  - 缺乏安全回應標頭（CSP, X-Frame-Options 等），無法防範 Clickjacking 與部分 XSS。
  - Session Cookie 未設定 `HttpOnly`（XSS 可讀取 Cookie）與 `SameSite=Lax`。
  - `/debug.php` 將系統環境變數與資料庫明文帳密直接公開給外部訪客。

### 2. ZAP 偵測與手動驗證
- **ZAP 檢測**：ZAP 的 **Passive Scan**（被動掃描）極易掃出全站 Missing Security Headers 警告，並能透過爬行掃描發現並解析 `/debug.php` 的敏感資訊。
- **手動驗證與 Cookie 竊取對照**：
  - 在瀏覽器中按 F12，檢查 Application 面板中的 Session Cookie（PHPSESSID），發現弱點版缺少 `HttpOnly` 勾選標記。
  - **竊取驗證**：在弱點版留言板（`/xss_stored.php`）中注入 `document.cookie` 竊取腳本，隨後進入 `/cookie_stealer.php` 收集箱，會發現當前造訪者的 Session ID 已經被成功記錄在竊取清單中。
  - **防禦對照**：在安全版（Port 8081）執行相同步驟，由於啟用了 `HttpOnly` 保護，JS 無法存取 PHPSESSID，收集箱中只能收到空字串。

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
  - XSS：在留言板輸入 <code>&lt;script&gt;document.write('&lt;img src="/cookie_stealer.php?cookie=' + encodeURIComponent(document.cookie) + '" style="display:none;"&gt;')&lt;/script&gt;</code>，隨後造訪 <code>/cookie_stealer.php</code> 即可在收集箱中看到被竊取的 Cookie。
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

---

## 補充演練 - 底層緩衝區溢位 (Buffer Overflow)

### 0. 對應 CWE
- [CWE-120 (Buffer Copy without Checking Size of Input)](https://cwe.mitre.org/data/definitions/120.html) - 緩衝區溢位

### 1. 漏洞頁面與成因
- **頁面**：`/buffer_overflow.php`
- **成因**：後端 PHP 使用 `exec()` 呼叫一個底層編譯的 C 語言程式，但並未限制傳入參數的長度。底層的 C 程式使用不安全的 `strcpy()`，將超長字串拷貝至固定長度的緩衝區 `char buffer[64]` 中，導致記憶體堆疊被覆寫破壞，程序觸發 `Segmentation fault` (SIGSEGV) 而崩潰。

### 2. ZAP 檢測性與手動驗證
- **ZAP 檢測**：ZAP 難以通過普通 Web 掃描得知底層記憶體溢位，但若在主動掃描中啟用 Buffer Overflow，ZAP 會發送大於 2048 字元的 Payload。若伺服器因 C 二進位崩潰回傳 HTTP 500 且網頁內容包含 `Segmentation fault` 等字眼，ZAP 即判定存有此漏洞。
- **手動驗證**：存取 `http://localhost:8080/buffer_overflow.php?input=AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA`（大於 64 字元），頁面將回傳 500 錯誤與系統崩潰訊息。

### 3. 程式修補對照
- **弱點版 C 原始碼** (`app-vulnerable/vuln_process.c`)：
  ```c
  char buffer[64];
  strcpy(buffer, argv[1]); // 不安全拷貝
  ```
- **修正版 C 原始碼** (`app-fixed/vuln_process.c` 與 `buffer_overflow.php`)：
  ```c
  char buffer[64];
  strncpy(buffer, argv[1], sizeof(buffer) - 1);
  buffer[sizeof(buffer) - 1] = '\0'; // 安全拷貝
  ```
  並且在 PHP 端進行長度限制阻截：
  ```php
  if (strlen($input) >= 64) {
      http_response_code(400);
      die("偵測到異常超長字串！");
  }
  ```

---

## 補充演練 - EXIF 中繼資料注入 (EXIF Injection)

### 0. 對應 CWE
- [CWE-79 (Improper Neutralization of Input During Web Page Generation)](https://cwe.mitre.org/data/definitions/79.html) - 儲存型 XSS
- [CWE-89 (Improper Neutralization of Special Elements used in an SQL Command)](https://cwe.mitre.org/data/definitions/89.html) - SQL 注入 (SQLi)

### 1. 漏洞頁面與成因
- **頁面**：`/exif_vulnerability.php`
- **成因**：使用者上傳 JPG 圖片後，PHP 後端使用 `exif_read_data()` 讀取相片內嵌的 EXIF 中繼資料（如 Artist、Model）。
  - **SQL 注入**：後端直接以字串拼接方式將 EXIF 資料寫入資料庫，一旦 Model 欄位含有單引號（例如 `' OR 1=1 -- `），便會破壞 SQL 結構導致 SQL 注入。
  - **儲存型 XSS**：網頁讀出這些 EXIF 欄位並直接輸出於表格中時未進行安全轉義，如果 Artist 被注入了 HTML/JS 腳本，所有瀏覽該圖片清單的訪客都會遭受 XSS 攻擊。

### 2. ZAP 偵測性與手動驗證
- **ZAP 檢測**：ZAP 難以直接推測 EXIF 這類二進位文件中的注入點。需手動在 EXIF 中埋入 XSS Payload（如 `exiftool -Artist="<script>alert(1)</script>" test.jpg`）上傳後觀察。
- **手動驗證**：
  1. 造訪頁面，下載預置的 `exif-xss-test.jpg` 檔案並上傳，確認網頁隨即跳出預存型 XSS 彈窗。
  2. 下載預置的 `exif-sqli-test.jpg` 檔案並上傳，確認引發資料庫寫入 SQL 語法報錯。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/exif_vulnerability.php`)：
  ```php
  // 直接字串拼接寫入
  $sql = "INSERT INTO exif_photos (filename, artist, model) VALUES ('$new_name', '$artist', '$model')";
  $pdo->exec($sql);
  
  // 前端直接輸出
  echo $row['artist'];
  ```
- **修正版** (`app-fixed/public/exif_vulnerability.php`)：
  ```php
  // 安全修補 1：使用 Prepared Statement 防範 SQLi
  $stmt = $pdo->prepare("INSERT INTO exif_photos (filename, artist, model) VALUES (:filename, :artist, :model)");
  $stmt->execute([':filename' => $new_name, ':artist' => $artist, ':model' => $model]);
  
  // 安全修補 2：前端使用 htmlspecialchars 轉義防範 XSS
  echo htmlspecialchars($row['artist'], ENT_QUOTES, 'UTF-8');
  ```

---

## 補充演練 - 日誌敏感資訊外洩 (CWE-532)

### 1. 漏洞頁面與成因
- **頁面**：`/login.php` (將明文寫入後端) 與 `/admin/logs.php` (管理後台稽核日誌)
- **成因**：系統記錄登入成功活動時，未將密碼等敏感資料排除，直接將密碼明文記錄在審計日誌中；同時，對於登入失敗 (爆破攻擊) 則完全不予記錄（維持 A09:2025 記錄缺失的漏洞設計）。一旦日誌資料庫或檔案外洩，成功登入者的密碼都將直接被管理員或攻擊者竊取。

### 2. 手動驗證與 ZAP 檢驗
- **手動驗證**：
  1. 使用正確帳密（如 `student01` / `password123`）登入弱點版網站。
  2. 存取管理員日誌區 `http://localhost:8080/admin/logs.php`，會看到剛才成功的記錄中赫然寫著 `Username: student01, Password: password123`。
  3. 嘗試使用錯誤帳密登入，並重新整理日誌，會發現完全沒有產生任何登入失敗日誌，維持了日誌記錄缺失的防禦缺陷。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/login.php`)：
  ```php
  // 不安全：登入成功日誌內容含有明文密碼變數 $password
  write_audit_log($pdo, "登入成功 (Username: $username, Password: $password)");
  ```
- **修正版** (`app-fixed/public/login.php`)：
  ```php
  // 安全：排除密碼敏感欄位，僅記錄事件
  write_audit_log($pdo, "登入成功");
  ```

---

## 補充演練 - Switch 缺少 break 越權 (CWE-484)

### 1. 漏洞頁面與成因
- **頁面**：`/admin/logs.php`（後端調用 `get_user_permissions()` 函數）
- **成因**：在 Switch 分配角色權限時，漏寫了 `break` 語句。這導致代碼執行時產生 Fall-through（直通現象）。當一個 `student` 登入時，程式匹配到 `case 'student'` 並賦予對應權限後，會一路往下執行，最終在 `case 'admin'` 被覆寫並多拿到了 `admin_access` 權限。

### 2. 手動驗證與 ZAP 檢驗
- **手動驗證**：
  1. 以普通學生 `student01` 登入弱點版。
  2. 直接存取稽核日誌頁面 `http://localhost:8080/admin/logs.php`。
  3. 發現非管理員身分居然可以繞過防禦成功進入，這代表 `check_login` 呼叫的 `get_user_permissions` 發生了直通越權 Bug。而在安全版 `http://localhost:8081/admin/logs.php` 則會被彈出拒絕。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/src/helpers.php`)：
  ```php
  switch ($role) {
      case 'student':
          $permissions[] = 'view_courses';
          // 漏洞點：缺少 break!
      case 'teacher':
          $permissions[] = 'view_registrations';
          // 漏洞點：缺少 break!
      case 'admin':
          $permissions[] = 'admin_access';
          break;
  }
  ```
- **修正版** (`app-fixed/src/helpers.php`)：
  ```php
  switch ($role) {
      case 'student':
          $permissions[] = 'view_courses';
          break; // 安全修補
      case 'teacher':
          $permissions[] = 'view_registrations';
          break; // 安全修補
      case 'admin':
          $permissions[] = 'admin_access';
          break;
  }
  ```

---

## 補充演練 - 缺少自訂錯誤頁面 (CWE-756)

### 1. 漏洞頁面與成因
- **頁面**：全站無效網址，例如 `/doesnotexist.php`
- **成因**：網站未配置 `ErrorDocument` 自訂錯誤處理機制。當使用者存取不存在的頁面時，Apache 會直接回傳預設的 HTML 錯誤頁面，其底部通常會自動輸出伺服器精確的 OS、Apache 與 PHP 版本資訊（Version Signature），增加攻擊者進行定向漏洞分析的風險。

### 2. 手動驗證與 ZAP 檢驗
- **ZAP 檢測**：ZAP 爬蟲在遇到 404 回應時，被動掃描會自動解析其 Server 標頭與錯誤網頁內容，一旦偵測到含有版本號特徵，會觸發 `Information Disclosure` 警告。
- **手動驗證**：存取 `http://localhost:8080/nonexistent_page`，檢視網頁底部，會看到 Apache 與 PHP 的詳細版本資訊。

### 3. 程式修補對照
- **弱點版**：無配置 ErrorDocument，任由預設 404 頁面輸出伺服器簽章。
- **修正版** (`app-fixed/public/.htaccess` 與 `error_404.php`)：
  在 `.htaccess` 中定義：
  ```apache
  ErrorDocument 404 /error_404.php
  ErrorDocument 500 /error_500.php
  ```
  並在 `error_404.php` 中呈現不含任何 Server 版本資訊的精美自訂錯誤畫面。

---

## 補充演練 - 圖片檢視器路徑穿越 (CWE-22)

### 1. 漏洞頁面與成因
- **頁面**：`/show_image.php`
- **成因**：後端圖片檢視器直接將使用者傳入的 `file` 參數拼接在 uploads 目錄後，未做任何 `basename()` 或安全防禦。這導致攻擊者能藉由 `../` 目錄遍歷字元跳出 uploads 目錄，直接讀取伺服器內部機密檔案。

### 2. 手動驗證與 ZAP 檢驗
- **ZAP 檢測**：ZAP 的 **Active Scan**（主動掃描）在掃描到 `file` 參數時，會發送各種 `../` 的路徑遍歷 Payload。當發現回傳的 HTTP 內容與本地檔案（如 `/etc/passwd`）特徵吻合時，會發出 **High Risk - Path Traversal** 警告。
- **手動驗證**：
  1. 登入後造訪：`http://localhost:8080/show_image.php?file=../../src/db.php`，驗證是否能直接讀取到資料庫的明文連接帳密。
  2. 修改參數為：`http://localhost:8080/show_image.php?file=../../../../../../../../etc/passwd`，驗證是否能成功讀取 Linux 的使用者帳號清單。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/show_image.php`)：
  ```php
  $filepath = __DIR__ . '/uploads/' . $_GET['file'];
  readfile($filepath);
  ```
- **修正版** (`app-fixed/public/show_image.php`)：
  ```php
  // 安全修補 1：使用 basename() 強制僅提取純檔名，破壞目錄穿越路徑
  $file = basename($_GET['file'] ?? '');
  $filepath = __DIR__ . '/uploads/' . $file;
  
  // 安全修補 2：嚴格限制 MIME 必須是圖片，否則拒絕讀取
  $mime = mime_content_type($filepath);
  if (strpos($mime, 'image/') !== 0) {
      die('存取拒絕');
  }
  ```

---

## 補充演練 - 繞過圖像特徵檢測 (CWE-434 / 圖片木馬)

### 1. 漏洞頁面與成因
- **頁面**：`/upload_bypass_polyglot.php` (大頭貼上傳第四關)
- **成因**：後端只對上傳檔案進行 `getimagesize()` 來檢驗其是否為真實圖片，但在寫入檔案時**卻保留了使用者提供的原始副檔名**（例如 `.php`）。這使得攻擊者可以在一個合法 GIF 圖片二進位數據末尾拼接 PHP 木馬代碼（即圖片木馬/Polyglot），成功繞過內容特徵檢測，並將其當成 PHP 腳本在伺服器端執行取得 RCE。

### 2. 手動驗證與 ZAP 檢驗
- **手動驗證**：
  1. 前往大頭貼上傳第四關，點擊「下載 GIF 圖片木馬 PoC」，會取得檔案 `polyglot-image-webshell.php`。
  2. 在第四關直接上傳該檔案，由於其開頭為 GIF 圖片簽章 `GIF89a`，`getimagesize()` 判定其為合法圖片。
  3. 上傳成功後，存取該連結並帶入系統命令：`http://localhost:8080/uploads/polyglot-image-webshell.php?cmd=whoami`，確認成功執行命令取得 RCE。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/upload_bypass_polyglot.php`)：
  ```php
  // 雖然檢查了圖片特徵，但依然使用原始上傳檔名
  $img_info = getimagesize($file_tmp);
  $destination = '/uploads/' . $_FILES['avatar']['name'];
  move_uploaded_file($file_tmp, $destination);
  ```
- **修正版** (`app-fixed/public/upload_bypass_polyglot.php`)：
  ```php
  // 安全修補 1：副檔名白名單驗證
  $file_ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
  if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
      die('副檔名錯誤');
  }
  
  // 安全修補 2：對上傳檔案進行隨機安全性重新命名，不保留原始 .php 檔名
  $new_name = bin2hex(random_bytes(16)) . '.' . $file_ext;
  
  // 安全修補 3：上傳目錄搭配 .htaccess 禁止執行 PHP
  ```

---

## 補充演練 - 明文密碼外洩與密碼強度政策缺陷 (CWE-319 / CWE-521 / CWE-522)

### 1. 漏洞頁面與成因
- **頁面**：`/profile.php`
- **成因**：
  1. **敏感資訊明文外洩 (CWE-319 / CWE-522)**：後端將使用者的敏感密碼以明文存放在資料庫中，且在個人資料修改表單呈現時，**直接將該明文密碼帶入 <code>&lt;input type="password"&gt;</code> 的 <code>value</code> 屬性中**。學員只需在 F12 審查元素中將 <code>type="password"</code> 改成 <code>type="text"</code> 即可直接看穿密碼。
  2. **密碼強度要求缺陷 (CWE-521)**：弱點版完全沒有限制密碼強度，也沒有提供二次密碼輸入確認（防錯機制），導致使用者可以設定極為簡單的密碼（如 12345），容易遭受密碼爆破。

### 2. 手動驗證與 ZAP 檢驗
- **手動驗證**：
  1. 使用任意帳號登入弱點版網站（如學生 `student01` / `password123`）。
  2. 造訪個人資料頁：`http://localhost:8080/profile.php`。
  3. 按 F12 打開開發者工具，找到密碼輸入框 `<input type="password" id="password" ...>`。
  4. 雙擊修改其屬性，將 `type="password"` 改為 `type="text"`，會發現密碼欄位直接以明文顯現為 `password123`，證明存在嚴重敏感個資洩漏。
  5. 試圖隨意輸入單個字母 `a` 並點擊更新，弱點版會直接放行並更新，完全沒有複雜度校驗。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/profile.php`)：
  ```html
  <!-- 不安全：在 value 屬性中硬編碼直接帶出使用者的明文密碼 -->
  <input type="password" name="password" value="<?= $profile['password_hash'] ?>">
  ```
- **修正版** (`app-fixed/public/profile.php`)：
  1. **雙重輸入防錯 (UX 安全)**：在前端放置兩個密碼框以防輸入錯誤：
     ```html
     <input type="password" name="password" placeholder="留空代表不修改密碼">
     <input type="password" name="confirm_password" placeholder="請再次輸入新密碼">
     ```
  2. **密碼強度複雜度驗證 (CWE-521)**：後端以 Regex 強制校驗新密碼強度，並使用安全 Bcrypt 雜湊加密：
     ```php
     if ($password !== '' && $password !== $confirm_password) {
         $error = '🚫 更新失敗：新密碼與確認密碼不一致！';
     } elseif ($password !== '' && (
         strlen($password) < 8 ||
         !preg_match('/[A-Z]/', $password) ||
         !preg_match('/[a-z]/', $password) ||
         !preg_match('/[0-9]/', $password) ||
         !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
     )) {
         $error = '🚫 更新失敗：密碼強度不足！長度必須至少 8 個字元，且同時包含大小寫字母、數字及特殊符號。';
     } else {
         // 使用 password_hash 進行 bcrypt 雜湊更新...
     }
     ```

---

## 補充演練 - PDF 嵌入跨站腳本攻擊 (PDF-based XSS - CWE-79)

### 1. 漏洞頁面與成因
- **頁面**：`/pdf_xss_demo.php`
- **成因**：
  許多開發者誤以為 PDF 是純靜態圖文格式，但其實 PDF 支援嵌入並執行 JavaScript (OpenAction)。
  1. **同源腳本執行 (Stored XSS / CWE-79)**：如果系統允許上傳 PDF 檔案，並在前端使用普通 `<iframe>` 或 `<embed>` 直接渲染該檔案，惡意 PDF 內的 JS 就會與本站共享相同的同源（Same-Origin）權限。
  2. **隱私數據外洩**：這會導致攻擊者能藉由 PDF JavaScript 跨框架存取父網域的 `localStorage` / `document.cookie`，進而竊取敏感的會話 Token 或個資。

### 2. 手動驗證與 ZAP 檢驗
- **手動驗證**：
  1. 登入弱點版網址 `http://localhost:8080/pdf_xss_demo.php`。
  2. 點擊「下載 PDF XSS PoC 檔案」，下載一個內嵌 JS 的特製檔案 `xss-poc.pdf`。
  3. 將 `xss-poc.pdf` 檔案上傳。
  4. 上傳成功後，瀏覽器預覽該 PDF 時會執行其中的 OpenAction 腳本，**自動跳出彈窗並顯示您在父視窗 localStorage 中的敏感 API Token**，表示 Stored XSS 攻擊成功。

### 3. 程式修補對照
- **弱點版** (`app-vulnerable/public/pdf_xss_demo.php`)：
  ```html
  <!-- 漏洞點：未加 sandbox 屬性，允許 PDF 執行 JavaScript 跨來源操作父框架 -->
  <iframe src="/uploads/your_file.pdf" width="100%" height="500px"></iframe>
  ```
- **修正版** (`app-fixed/public/pdf_xss_demo.php`)：
  1. **前端沙盒化 (Sandbox iframe)**：iframe 強制加上 `sandbox="allow-same-origin"` 屬性且不啟用 `allow-scripts`。這允許正常預覽 PDF 的視覺內容，但瀏覽器會強制禁用該 PDF 內部一切 JS 的執行：
     ```html
     <iframe src="/uploads/your_file.pdf" sandbox="allow-same-origin" width="100%" height="500px"></iframe>
     ```
  2. **下載標頭隔離 (Attachment Header)**：對於敏感的 PoC PDF 檔案下載點，後端強制輸出附件標頭，消除瀏覽器同源解析的攻擊面：
     ```php
     header('Content-Type: application/pdf');
     header('Content-Disposition: attachment; filename="document.pdf"');
     ```
