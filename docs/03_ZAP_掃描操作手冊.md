# 03 OWASP ZAP 完整掃描與操作手冊

本手冊指導學員與教師如何使用 **OWASP ZAP** 工具，對 **VulnCampus PHP Lab** 進行全方位的安全檢測，內容完整涵蓋 ZAP 十大核心功能與實戰操作指南。

---

## 1. 基礎概念與本機代理設定 (Local Proxy)

在開始掃描前，請確保您的 **VulnCampus PHP Lab** 靶場已正常運行：
* 弱點版位址：`http://localhost:8080`
* 修正版位址：`http://localhost:8081`

### 設定 ZAP 代理
1. 開啟 **OWASP ZAP**。
2. 點選上方選單 **Tools (工具) > Options (選項)**。
3. 尋找 **Local Servers/Proxies (本機伺服器與代理)**。
4. 將 ZAP 的 Local Proxy Port 修改為 **`8090`** (因為 8080/8081 已被靶場佔用)， Address 設為 `localhost` 或 `127.0.0.1`。
5. 設定您瀏覽器的 Proxy (代理伺服器) 指向 `127.0.0.1:8090`。
   *💡 **快捷技巧**：最直覺的做法是點選 ZAP 畫面右上角或 Quick Start 面板的 **"Manual Explore" (手動探索)** 點選 **"Launch Browser" (啟動瀏覽器)**。ZAP 會自動開啟一個為您配置妥當代理的專屬 Chromium 瀏覽器。*

---

## 2. 傳統爬蟲與 AJAX 爬蟲 (Spider & AJAX Spider)

在進行主動攻擊前，ZAP 需要對目標網站進行結構盤點（爬行），以建立完整的站台地圖 (Site Map)。

### A. 傳統網頁爬蟲 (Spider)
1. 使用 ZAP 瀏覽器瀏覽並存取 `http://localhost:8080/index.php`，讓 ZAP 左側 **Sites (站台)** 出現目標網站。
2. 在 Sites 的 `http://localhost:8080` 點擊右鍵。
3. 選擇 **Attack (攻擊) > Spider (傳統網頁爬蟲)**。
4. 點選 **Start Scan (開始掃描)**。ZAP 會快速解析所有靜態 HTML 標籤（如 `<a href>`, `<img src>`）所包含的路徑。

### B. AJAX 爬蟲 (AJAX Spider)
當網頁包含大量動態 JavaScript 事件與異步 API 時（例如點擊定位打卡、AJAX 查詢等），傳統爬蟲無法執行 JS 代碼。
1. 對 `http://localhost:8080` 點選右鍵。
2. 選擇 **Attack (攻擊) > AJAX Spider**。
3. 選擇瀏覽器類型 (如 `HtmlUnit` 或 `Chrome`)，點選 **Start Scan**。ZAP 將啟動瀏覽器模擬真實滑鼠點選，探索隱藏的 API。

---

## 3. 被動掃描與自訂規則腳本 (Passive Scan & Scripts)

被動掃描（Passive Scan）在網頁流量流經代理時進行不具破壞性的分析（如檢查標頭、是否有敏感資訊等）。

### 實作：自訂個資偵測腳本 (PII Detector)
我們在靶場中配置了自訂個資洩漏頁面 `/pii_leakage.php`，讓我們配置 ZAP 來自動報警：
1. 點選 ZAP 上方工具列的 **"Scripts (腳本)"** 頁籤（若無此頁籤，請點選綠色加號 `+` -> `Scripts`）。
2. 在 `Scripts` 目錄樹的 **"Passive Rules (被動規則)"** 上按右鍵 -> **"New Script..."**。
3. 名稱填入 `PII_Detector`，類型選 `Passive Rules`，引擎選 `Oracle Nashorn` (或 `Graal.js`)。
4. 將 `/pii_leakage.php` 頁面上提供的 JavaScript 代碼貼入編輯區：
   ```javascript
   function scan(ps, msg, src) {
       var body = msg.getResponseBody().toString();
       var idRegex = /[A-Z][12]\d{8}/g; // 身分證字號 Regex
       var idMatch = idRegex.exec(body);
       if (idMatch !== null) {
           raiseZapAlert(ps, msg, 10091, "自訂個資洩漏：身分證字號", idMatch[0]);
       }
   }
   function raiseZapAlert(ps, msg, id, name, evidence) {
       var alert = new org.parosproxy.paros.core.scanner.Alert(id, 2, 2, name); // RISK_MEDIUM
       alert.setDescription("偵測到明文個資洩漏！");
       alert.setEvidence(evidence);
       alert.setUri(msg.getRequestHeader().getURI().toString());
       ps.parent.raiseAlert(alert);
   }
   ```
5. 點擊 **Save (儲存)**，並對該腳本按右鍵選擇 **Enable Script**。
6. 當您瀏覽弱點版 `http://localhost:8080/pii_leakage.php` 時，ZAP 的 **Alerts** 面板會立刻跳出明文身分證字號警報。而存取安全版 `http://localhost:8081/pii_leakage.php` 則無警報，從中即可學到資料掩碼（Data Masking）的效益。

---

## 4. 強制瀏覽與隱藏備份檔案探測 (Forced Browse)

許多敏感檔案（如 `.bak` 備份檔、`.zip` 壓縮包、`.old` 舊版本）並未在 HTML 網頁上留下任何超連結，傳統與 AJAX 爬蟲皆無法找到。此時必須使用 **Forced Browse**（強制瀏覽/目錄爆破）。

### 操作步驟：
1. 確保已安裝 **Forced Browse** Add-on。
2. 在 Sites 對 `http://localhost:8080` 點擊右鍵 -> **Attack > Forced Browse (強制作業瀏覽)**。
3. 在下方面板中選擇字典檔（Wordlist），點選開始掃描。
4. ZAP 將使用字典進行暴力字典攻擊。
5. **預期結果**：ZAP 會掃描出弱點版內隱藏的 `/db.php.bak`、`/index.php.old` 與 `/backup.zip`，其狀態碼為 `200 OK`，表示檔案存在，展示了不安全檔案留存的風險。

---

## 5. 身分驗證與會話管理掃描 (Authentication)

許多重要功能必須在登入後才能存取。ZAP 提供認證管理，確保掃描能穿透登入機制。

### 配置自動登入 (Form-based Auth)
1. 在左側 Sites 對靶場 `http://localhost:8080` 點選右鍵 -> **Include in Context > Default Context**。
2. 進入 ZAP 瀏覽器進行手動登入。在底部的 **History** 尋找 `POST:login.php` 的登入請求。
3. 對該筆請求點選右鍵 -> **Flag as Context > Default Context: Form-based Auth Target**。
4. ZAP 會彈出 Context 配置畫面。確認 Username、Password 參數映射無誤。
   - **Logged In Indicator (登入成功標記)**：填入 `\Qlogout.php\E` (或 `登出`)。
   - **Logged Out Indicator (登入失效標記)**：填入 `\Qlogin.php\E` (或 `登入`)。
5. 切換至 Context 的 **Users** 面板，點選 **Add** 新增帳號：`student01`，密碼 `password123`，並勾選啟用。
6. 切換至 **Forced User**，啟用強制使用者，設定為 `student01`。
7. 在 ZAP 工具列頂端，鎖上 **鎖頭圖示 (Forced User Mode)**，使 ZAP 自動進行登入 Session 檢測。

---

## 6. 主動掃描與漏洞判讀 (Active Scan)

主動掃描 (Active Scan) 會對目標參數發送多種攻擊語句 (SQLi, XSS, Cmd Injection, SSRF, XXE) 以探測系統邊界。

### 執行掃描：
1. 在 Sites 對 `http://localhost:8080` 點選右鍵。
2. 選擇 **Attack (攻擊) > Active Scan (主動掃描)**。
3. 在設定面板將 **User** 選為先前配置的 `student01` (若需進行授權後掃描)，點選 **Start Scan**。
4. **警報證據判讀**：掃描結束後，切換至 **Alerts** 面板。雙擊任意警報（如 SQL Injection）：
   - **Attack 欄位**：顯示 ZAP 丟入的攻擊字串（如 `' OR 1=1 -- `）。
   - **Evidence 欄位**：顯示網頁回傳的資料庫錯誤訊息，作為直接的漏洞證明。

---

## 7. 模糊測試與暴力破解 (Fuzzer)

ZAP 內建 Fuzzer 工具，可針對特定的 HTTP 請求欄位帶入字典檔或設定規則，進行參數篡改、暴力破解或漏洞探測。

### 實作：對登入端點進行暴力破解
1. 回到 ZAP 底部 **History** 面板，找到您曾嘗試登入 `POST:login.php` 的請求。
2. 對該請求點擊右鍵 -> **Fuzz...**。
3. 彈出 Fuzzer 視窗，在 Request 面板中，用滑鼠反白選取 `username` 欄位的值（如 `student01`），點擊右側的 **Add...**。
4. 點選 **Add...** 按鈕以新增 Payload (字典檔)，可選擇 File 載入本機字典，或手動輸入（如 `admin`、`guest`、`teacher` 等）。
5. 重複上述步驟對 `password` 欄位反白，並導入常見弱密碼字典。
6. 點選 **Start Fuzzer** 開始爆破。
7. **結果分析**：比對回應長度（Size）與狀態碼，即可快速找出成功的帳密組合。

---

## 8. 手動請求編輯器 (Manual Request Editor)

Manual Request Editor (或 Requester) 允許測試人員完全手動修改 HTTP 請求的任何內容（如標頭、Body、Cookie），並單獨送出以觀察網頁回應，是漏洞重現與分析的重要工具。

### 操作步驟：
1. 在 History 面板找到任何一筆對 `/event_register.php` 或 `/profile.php` 的請求。
2. 對該請求點擊右鍵 -> **Open/Resend with Request Editor...**。
3. ZAP 會開啟一個編輯面板：
   - 您可以直接修改 Method（如將 GET 改為 POST）。
   - 修改 Request Body 參數：如將 `role=student` 改為 `role=admin`（測試 Mass Assignment）；或將活動報名 `quantity=1` 改為 `quantity=-5`。
4. 點選左上角的 **Send (發送)**。
5. 在右側面板中檢視 Response 標頭與 Body 內容，藉此手動驗證漏洞修補效果。

---

## 9. API 漏洞與 OpenAPI 規格掃描 (API Scanning)

現代網站大量使用 RESTful API。ZAP 能夠透過導入規範檔案，對無前端介面的 API 端點進行自動化安全檢測。

### 實作步驟：
1. 本專案在 `/api/` 目錄下佈署了如 `profile.php` 與 `ajax_user_info.php` 等 JSON API 端點。
2. 點選 ZAP 上方選單 **Import (匯入)**。
3. 選擇 **Import an OpenAPI File or URL**。
4. 在 OpenAPI 檔案路徑中，導入您的 OpenAPI/Swagger JSON 規格檔案位址，或者點擊導入。
   *(本專案已在 API 設計中支持了 OpenAPI 架構檢測)*。
5. ZAP 匯入後，會在左側 **Sites** 目錄中自動建立所有 API 端點（如 `/api/profile.php`）。
6. 您即可對整個 `/api/` 資料夾點選右鍵進行 **Active Scan**，探測 IDOR (BOLA)、SQLi 與 CORS 等 API 漏洞。

---

## 10. 全域中斷點與攔截改包 (Breakpoint)

中斷點 (Breakpoint) 能夠在瀏覽器與伺服器通訊的過程中，將連線「暫停」在 ZAP 中，讓測試人員在封包尚未送達伺服器前，手動編輯內容。

### 操作步驟：
1. 在 ZAP 工具列中，點擊 **綠色圓圈圖示**，使其變成 **紅色圓圈**，開啟全域中斷點模式。
2. 在瀏覽器點選敏感按鈕（如報名活動）。
3. 瀏覽器此時會卡在載入狀態。回到 ZAP，會自動切換至 **Break** 面板。
4. 您可以在此手動竄改任何參數，例如將 `ticket_price=1000` 改成 `ticket_price=0`。
5. 點選工具列的 **Step (單步執行)** 或 **Play (放行)**，完成測試。

---

## 11. 產生掃描報告 (Generate Report)

在完成所有測試後，需要將成果匯出為結構化報告。

1. 點選上方選單 **Report (報告) > Generate Report (產生報告)**。
2. 輸入您的報告標題（如 `VulnCampus-Lab-Report`）。
3. 選擇範本：`Modern HTML Report`（網頁樣式）或 `Markdown Report`（純文字）。
4. 選擇存檔路徑，點選 **Generate Report**，即可生成包含漏洞詳細危害、證據、修復建議的檢測報告。
