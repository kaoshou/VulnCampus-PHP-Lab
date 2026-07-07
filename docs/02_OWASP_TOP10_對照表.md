# 02 OWASP Top 10:2025 對照表

本文件詳細列出 **VulnCampus PHP Lab** 靶場中，針對 **OWASP Top 10:2025** 十大網站安全風險的具體漏洞設計、ZAP 檢測性、手動驗證方式與安全修補防護對照。

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
  - 資料庫密碼使用 MD5 雜湊，在現今已被視為不安全加密。
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
  $md5_password = md5($password);
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

