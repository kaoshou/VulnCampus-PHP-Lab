# 03 OWASP ZAP 掃描操作手冊

本手冊指導學員如何使用 **OWASP ZAP** 對 **VulnCampus PHP Lab** 進行完整的安全漏洞掃描，包含被動掃描、主動掃描以及登入認證掃描的設定流程。

---

## 1. 基礎概念與環境設定

在開始掃描前，請確保您的 **VulnCampus PHP Lab** 靶場已正常運行：
- 弱點版：`http://localhost:8080`
- 修正版：`http://localhost:8081`

### 設定 ZAP 代理 (Proxy)
ZAP 可以作為瀏覽器與網站之間的代理伺服器，藉此被動記錄並分析所有流經的網頁流量。
1. 開啟 **OWASP ZAP**。
2. 點選上方選單 **Tools (工具) > Options (選項)**。
3. 尋找 **Local Servers/Proxies (本機伺服器與代理)**。
4. 預設 Address 為 `localhost`，Port 為 `8080` (若 8080 與靶場衝突，ZAP 通常會自動切換為 `8090` 或 `8081`。注意：本專案弱點版使用了 `8080`，修正版使用了 `8081`，因此請將 ZAP 的 Local Proxy Port 修改為 **`8090`**，避免埠口衝突)。
5. 設定您瀏覽器的 Proxy (代理伺服器) 指向 `127.0.0.1:8090`。
   *💡 提示：最簡單的做法是點選 ZAP 主畫面右上方或 Quick Start 的 **"Manual Explore" (手動探索)** 按鈕，直接點擊 "Launch Browser" (啟動瀏覽器)，ZAP 會為您啟動一個已自動設定好代理的 Chrome/Firefox 瀏覽器，免去手動設定的繁瑣步驟。*

---

## 2. 爬行與目錄結構探索 (Spider & AJAX Spider)

在進行弱點掃描前，必須先建立站台地圖 (Site Map)。

### A. 一般爬蟲 (Spider)
1. 在瀏覽器中訪問 `http://localhost:8080/index.php`，讓 ZAP 捕獲流量。
2. 在左側 **Sites (站台)** 樹狀目錄中，對 `http://localhost:8080` 按右鍵。
3. 選擇 **Attack (攻擊) > Spider (傳統網頁爬蟲)**。
4. 點選 **Start Scan (開始掃描)**。ZAP 會快速爬梳所有 HTML 標籤（如 `href`, `src`）能觸及的頁面。

### B. AJAX 爬蟲 (AJAX Spider)
當網頁中有許多內容是透過 JavaScript 或 API 動態載入時（例如我們的 API profile/register 請求），傳統 Spider 無法執行 JS 程式碼。此時需要 AJAX Spider：
1. 對 `http://localhost:8080` 點選右鍵。
2. 選擇 **Attack (攻擊) > AJAX Spider**。
3. 選擇瀏覽器類型 (如 `HtmlUnit` 或 `Chrome`)，點選 **Start Scan**。ZAP 會啟動瀏覽器並模擬點擊頁面上的各個按鈕與動態事件，探索隱藏的 API。

---

## 3. Scope 與 Context 設定

為了防止 ZAP 的攻擊流量掃描到外部網站（如 Google CDN 等），必須將靶場設定在掃描範圍 (Scope) 內。
1. 在左側 Sites 中，對 `http://localhost:8080` 按右鍵。
2. 選擇 **Include in Context (包含在上下文中) > Default Context**。
3. 在彈出的 Context 面板中，您會看到 `\Qhttp://localhost:8080\E.*` 已被加入為包含範圍。
4. 勾選 **In Scope (在範圍內)** 選項。
5. 在左側 Sites 樹狀目錄上方，點選紅色的「目標目標標靶」圖示（或將下拉選單切換為 **Show only URLs in Scope**），此時 ZAP 的主動攻擊將只會針對本機靶場，防止對外網造成影響。

---

## 4. 主動掃描與弱點分析 (Active Scan)

主動掃描會對目標發送各種惡意 Payload，檢測是否有 SQL Injection、XSS 等安全漏洞。

### 執行掃描：
1. 確保已對靶場進行過 Spider 爬行。
2. 在 Sites 中對 `http://localhost:8080` 點選右鍵。
3. 選擇 **Attack (攻擊) > Active Scan (主動掃描)**。
4. 點選 **Start Scan**。您可以在下方的 "Active Scan" 面板觀察掃描進度。
5. 掃描完成後，切換至 **Alerts (警報)** 面板：
   - 🔴 **紅色旗幟**：High Risk (高風險)。如 SQL Injection、Path Traversal、Command Injection。
   - 🟡 **黃色旗幟**：Medium Risk (中風險)。如 XSS、Session Fixation、CORS 錯誤配置。
   - 🔵 **藍色旗幟**：Low Risk (低風險)。如 Cookie 缺失安全屬性。

### 證據判讀 (Request / Response)
點選 Alerts 中的任意警告項目（例如 SQL Injection），在右側面板會顯示 ZAP 發現此漏洞的詳細資訊：
- **URL**：受影響的網頁路徑與參數。
- **Attack (攻擊 Payload)**：ZAP 帶入的注入符號（例如 `1' UNION SELECT...`）。
- **Evidence (證據)**：在網頁 Response 中發現的資料庫報錯字串（例如 `syntax error`）。
- **Request / Response 欄位**：雙擊該警告，檢視 ZAP 實際發送的 raw HTTP 請求與伺服器的回應，這能作為撰寫滲透測試報告時的直接證據。

---

## 5. 攔截改包測試 (Breakpoint & Repeater)

對於邏輯型漏洞（如將活動報名數量改成負數、竄改 hidden 角色欄位等），自動化掃描無法理解業務邏輯，此時需要手動攔截與修改封包。

### 實作步驟：
1. 在 ZAP 的工具列中，尋找 **綠色圓圈圖示**（或按快捷鍵 `Ctrl + Alt + B`），點擊將其變成 **紅色圓圈**，此時開啟了 **全域中斷點 (Breakpoint)**。
2. 回到 ZAP 瀏覽器，填寫活動報名表單（例如 `quantity=1`），點選「確認送出報名」。
3. 此時瀏覽器會處於載入狀態。回到 ZAP，您會發現畫面切換到了 **Break (中斷)** 面板，展示了即將發送的 HTTP POST 請求。
4. 在 Break 面板中，直接修改請求內文：
   - 將 `quantity=1` 改為 `quantity=-5`。
   - 或者是將個人資料修改請求中的 `role=student` 改為 `role=admin`。
5. 修改完成後，點選 ZAP Breakpoint 工具列的 **單步執行 (Step)** 或 **放行 (Play，藍色三角形)** 按鈕，放行封包。
6. 回到網頁，確認是否成功完成負數報名，或升權為管理員。

---

## 6. 登入認證掃描設定 (Authentication Scan)

許多頁面（如 `profile.php`, `admin/` 等）需要登入後才能存取。如果直接進行 ZAP 自動化掃描，將無法掃描到登入後的安全弱點。
為此，我們可以採用以下兩種流程來實現登入後的網頁掃描：

### 方案 A：手動登入被動截取法 (最簡單，適合初學與課堂快速演示)
此方案的原理是**讓學員手動進行登入以獲取 Session Cookie，並藉由 ZAP 爬行與攔截該 Cookie 來直接進行單頁面主動掃描**。

#### 操作步驟：
1. **啟動 ZAP 專屬瀏覽器**：
   - 在 ZAP 主畫面右上角點選 **Launch Browser** 啟動內建的 Chromium 瀏覽器。
2. **進行手動登入**：
   - 在該瀏覽器中輸入 `http://localhost:8080/login.php`，輸入測試帳密 `student01` / `password123` 完成登入。
3. **瀏覽需要權限的內頁（餵送流量）**：
   - 手動點擊導覽列中的「課程查詢」、「留言板」、「個人資料」、「活動報名」。
   - 此時，ZAP 的左側 **Sites** 樹狀目錄中便會出現這些原本無法觸及的內頁網址。
4. **針對指定內頁點擊主動掃描**：
   - 在左側 Sites 樹狀目錄中，對 `/profile.php` 按右鍵，選擇 **Attack (攻擊) > Active Scan (主動掃描)**。
   - ZAP 會直接延用剛才瀏覽時捕獲的 `PHPSESSID` Cookie 進行注入檢測，成功掃描出內頁漏洞。
   - *優缺點*：操作非常簡單直觀，但若掃描過程中發送的 Payload 導致 Session 被系統登出，則後續掃描會失效。

---

### 方案 B：配置 ZAP Form-based 自動登入 (最嚴謹，適合進階自動化與 CI/CD 掃描)
此方案的原理是**在 ZAP 內部配置 Context 認證，讓 ZAP 學會「自動判定登出狀態並自動發送登入 POST」**，從而維持長效的掃描 Session。

#### 操作步驟：
1. **設定 Context 範圍**：
   - 在左側 Sites 中對 `http://localhost:8080` 按右鍵 > **Include in Context (包含在上下文中) > Default Context**。
2. **標記登入 POST 請求**：
   - 手動登入後，在下方的 **History** 面板中找到 `POST:login.php` 的那筆請求。
   - 對它點選右鍵 > **Flag as Context > Default Context: Form-based Auth Target**。
3. **設定登入與登出特徵 (Indicators)**：
   - ZAP 會自動彈出 Context 設定視窗。在 **Authentication (認證)** 面板中：
     - 確認 URL 已設定為 `/login.php`，且 username 與 password 參數已被對應。
     - **Logged In Indicator (已登入識別特徵)**：填入 `\Qlogout.php\E` (或 `登出`)。
     - **Logged Out Indicator (未登入識別特徵)**：填入 `\Qlogin.php\E` (或 `登入`)。
     *(ZAP 會透過比對網頁回應是否含有這些特徵，來即時得知當前 Session 是否仍然有效)*。
4. **設定使用者憑證 (Users)**：
   - 切換至 Context 視窗左側的 **Users (使用者)** 面板，點選 **Add (新增)**。
   - Username 輸入 `student01`，Password 輸入 `password123`，勾選啟用 (Enabled)。
5. **啟動強制使用者模式 (Forced User)**：
   - 切換至 Context 視窗左側的 **Forced User** 面板，勾選啟用，並將使用者設為剛才建立的 `student01`。
   - 儲存關閉設定後，點選 ZAP 上方工具列的 **鎖頭圖示 (Forced User Mode)** 將其鎖上，這會強制 ZAP 的所有請求在 Session 遺失時自動進行重登入。
6. **啟動主動掃描**：
   - 執行主動掃描時，將 **User** 下拉選單改選為 **`student01`**，ZAP 就會使用該權限完成登入後全站掃描。

---


## 7. 產生報告

1. 點選上方選單 **Report (報告) > Generate Report (產生報告)**。
2. 輸入報告標題（如 `VulnCampus-Before-Scan`）。
3. 選擇輸出模板（通常選擇 `Modern HTML Report` 或是 `Markdown Report`）。
4. 設定存檔路徑，點選 **Generate Report**。
5. 此報告可用於比對修補前後的漏洞數量與等級。
