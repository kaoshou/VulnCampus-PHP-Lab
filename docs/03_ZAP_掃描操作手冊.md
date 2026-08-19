# 03 OWASP ZAP 完整掃描與操作手冊

本手冊指導學員與教師如何使用 **OWASP ZAP** 工具，對 **VulnCampus PHP Lab** 進行全方位的安全檢測，內容完整涵蓋 ZAP 十大核心功能與實戰操作指南。

---

## 1. 基礎概念與本機代理設定 (Local Proxy)

在開始掃描前，請確保您的 **VulnCampus PHP Lab** 靶場已正常運行：
* 弱點版位址：`http://localhost:8080`
* 修正版位址：`http://localhost:8081`

### 設定模式一：🌟 最推薦做法（防呆首選 / 免裝憑證）
1. 點選 ZAP 畫面右上角或 Quick Start 面板的 **"Manual Explore" (手動探索)**。
2. 點選 **"Launch Browser" (啟動瀏覽器)**。
3. **為什麼優先使用 Launch Browser？**
   - ✅ **免安裝 CA 根憑證**：不會出現 HTTPS 憑證不受信任或被封鎖的紅字。
   - ✅ **自動解除 Bypass 限制**：一般 Chrome/Edge 預設會繞過 `localhost` / `127.0.0.1` 的 Proxy 設定導致抓不到封包，ZAP 內建瀏覽器會自動停用 Bypass，確保 100% 成功攔截流量。
   - ✅ **與個人日常瀏覽器完全隔離**：不會影響學員本機其他網頁的分頁。

---

### 設定模式二：手動設定個人 Google Chrome 代理與安裝根憑證 (進階實務)
若學員希望使用自己本機日常安裝的 **Google Chrome** 進行檢測，請依序完成以下三大步驟：

#### 步驟 1：修改 ZAP 本機監聽埠口 (避免與靶場 8080 衝突)
1. 開啟 ZAP 選項設定：
   - **Windows / Linux**：點選上方選單 **Tools (工具) > Options (選項) > Local Servers/Proxies**
   - **macOS**：點選螢幕左上方選單 **ZAP > Settings (或 Preferences)**，快捷鍵為 **`Cmd + ,`**，尋找 **Local Servers/Proxies**
2. 將 **Port** 修改為 **`8090`** (因為 8080/8081 已被靶場佔用)，Address 維持 `localhost` 或 `127.0.0.1`，點選確認。

#### 步驟 2：從 ZAP 匯出並在 Chrome 中安裝 Root CA 根憑證 (解密 HTTPS 流量)
> 💡 **為什麼要裝憑證？** 當瀏覽 HTTPS 網站時，ZAP 作為中間人代理需要動態簽發憑證。若未安裝 ZAP 根憑證，Chrome 會跳出 `NET::ERR_CERT_AUTHORITY_INVALID`（您的連線不是私人連線）並阻擋連線。

1. **從 ZAP 匯出根憑證**：
   - 進入 ZAP 的 **Options / Settings > Dynamic SSL Certificate (動態 SSL 憑證)**。
   - 點選 **Save (儲存)** 按鈕，將憑證另存為 `owasp_zap_root_ca.cer`（存於桌面方便尋找）。
2. **在 Windows (Chrome) 匯入憑證**：
   - 打開 Chrome，點選右上角三個點 `⋮` -> **設定 (Settings)** -> **隱私權和安全性 (Privacy and security)** -> **安全性 (Security)**。
   - 向下滾動點選 **管理裝置憑證 (Manage device certificates)**（或直接在 Windows 搜尋執行 `certmgr.msc`）。
   - 切換至 **「受信任的根憑證授權單位」 (Trusted Root Certification Authorities)** 頁籤。
   - 點選 **匯入 (Import...)** -> 瀏覽選取剛才存下的 `owasp_zap_root_ca.cer` -> 下一步 -> 完成。
   - 若跳出安全性警告提示「是否確定要安裝此憑證？」，點選 **是 (Yes)**。
3. **在 macOS (Chrome) 匯入憑證**：
   - 開啟 Mac 內建的 **鑰匙圈存取 (Keychain Access)** 應用程式。
   - 選擇 **登入 (login)** 或 **系統 (System)** 鑰匙圈。
   - 將 `owasp_zap_root_ca.cer` 檔案拖入清單中。
   - 雙擊開啟剛匯入的 **OWASP Root CA** 憑證，展開 **信任 (Trust)** 區塊。
   - 將「使用此憑證時 (When using this certificate)」改為 **「永遠信任 (Always Trust)」**，關閉視窗並輸入 Mac 密碼確認。

#### 步驟 3：在 Chrome 設定 Proxy 代理伺服器與排坑
- **方法 A：使用 Chrome 擴充套件 (推薦 / 最方便切換)**
  1. 在 Chrome 線上應用程式商店安裝 **ZeroOmega** (或 **Proxy SwitchyOmega**)。
  2. 新增 Profile (情境模式)，協定選擇 **HTTP**，伺服器填寫 **`127.0.0.1`**，連接埠填寫 **`8090`**。
  3. 點選儲存後，在擴充套件圖示切換為該模式即可。
- **方法 B：使用 Windows / macOS 系統代理設定 (無套件做法)**
  1. 打開 Chrome 設定 -> 搜尋「Proxy」-> 點選「開啟電腦的 Proxy 設定」。
  2. 開啟「手動設定 Proxy」：IP 填 `127.0.0.1`，Port 填 `8090`。
  3. **⚠️ 極重要排坑（學員常抓不到封包的原因）**：
     - Windows 預設會勾選「近端 (內部網路) 位址不使用 Proxy」，且下方例外清單常有 `<local>; 127.0.0.1; localhost`。
     - **必須將這些例外規則清空，或取消勾選近端不使用 Proxy**，否則存取 `localhost:8080` 時 Chrome 會自動繞過 ZAP 直接連線！

#### 步驟 4：驗證連線
打開 Chrome 瀏覽器，在網址列輸入 `http://127.0.0.1:8080`，切換回 ZAP 的 **History (歷史記錄)** 面板，若能看到發出的 GET 請求與 HTTP 200 回應，代表代理與憑證配置大功告成！

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

### 🔍 如何在 ZAP 中打開「Scripts (腳本)」面板？
在 ZAP 的預設介面中，Scripts 標籤頁通常是隱藏的。請使用以下任一方法開啟：

- **方法 1（最直覺 / 點選加號）**：
  在左側視窗（即 **Sites 標籤頁旁邊**）點選 **綠色加號 `+`** 按鈕，在下拉選單中點選 **`Scripts`**（或 `腳本`）。
- **方法 2（選單列）**：
  點選螢幕最上方選單 **View (檢視) > Show Tab (顯示標籤頁) > Scripts (腳本)**。
- **方法 3（快捷鍵）**：
  - Windows / Linux：按下 **`Ctrl + Alt + S`**
  - macOS：按下 **`Cmd + Option + S`**

> 💡 **排坑提醒（若選單中沒有 Scripts 或引擎是空的）**：
> 1. 點選頂端工具列的「**Manage Add-ons (三個小方塊圖示)**」。
> 2. 切換至 **Marketplace (市集)** 頁籤，搜尋 **`Script Console`** 或 **`GraalVM JavaScript`** 並點選 **Install Selected** 安裝。

---

### 實作：自訂個資偵測腳本 (PII Detector)
我們在靶場中配置了自訂個資洩漏頁面 `/pii_leakage.php`，讓我們配置 ZAP 來自動報警：

#### 🌟 方式 A：直接載入專案預置腳本（最快就緒）
1. 打開 **Scripts** 面板。
2. 展開目錄樹，在 **"Passive Rules" (被動規則)** 上按右鍵 -> **"Load Script..." (載入腳本)**。
3. 選取本專案目錄中的 **`zap/pii_detector.js`** 檔案並開啟。
4. 在該腳本按右鍵 -> 點選 **"Enable Script" (啟用腳本)**。

#### 方式 B：手動新增與編寫腳本
1. 在 `Scripts` 目錄樹的 **"Passive Rules"** 上按右鍵 -> **"New Script..."**。
2. 設定視窗中填入：
   - **Script Name (名稱)**：`PII_Detector`
   - **Type (類型)**：`Passive Rules`
   - **Script Engine (腳本引擎)**：選擇 **`ECMAScript : Graal.js`**（若使用舊版則選 `Oracle Nashorn`）
   - **Template (模板)**：可保留空白或選擇預設
3. 點選 **儲存 (Save)**，將以下偵測代碼貼入右側腳本編輯區：
   ```javascript
   function scan(ps, msg, src) {
       // 排除二進位圖片等，僅檢測文字回應
       if (!msg.getResponseHeader().isImage() && msg.getResponseBody().length() > 0) {
           var body = msg.getResponseBody().toString();
           var uri = msg.getRequestHeader().getURI().toString();
           
           // 1. 偵測台灣身分證字號 (英文字母 + 1或2 + 8碼數字)
           var idRegex = /[A-Z][12]\d{8}/g;
           var idMatch = idRegex.exec(body);
           if (idMatch) {
               ps.raiseAlert(
                   2, // Risk: Medium (2)
                   2, // Confidence: Medium (2)
                   "自訂個資洩漏：偵測到身分證字號 (PII Leakage)", // Name
                   "網頁回應中洩漏了未遮罩的明文身分證字號：" + idMatch[0], // Description
                   uri, // URI
                   "",  // Param
                   "",  // Attack
                   "建議對身分證字號進行資料遮罩處理 (如 A12****789)。", // Solution
                   "",  // Reference
                   idMatch[0], // Evidence
                   10091, // CWE ID
                   0,     // WASC ID
                   msg    // HTTP Message
               );
           }
           
           // 2. 偵測台灣手機號碼 (09開頭)
           var phoneRegex = /09\d{2}-?\d{3}-?\d{3}/g;
           var phoneMatch = phoneRegex.exec(body);
           if (phoneMatch) {
               ps.raiseAlert(
                   2, // Risk: Medium (2)
                   2, // Confidence: Medium (2)
                   "自訂個資洩漏：偵測到手機號碼 (Phone Leakage)", // Name
                   "網頁回應中洩漏了未遮罩的明文手機號碼：" + phoneMatch[0], // Description
                   uri, // URI
                   "",  // Param
                   "",  // Attack
                   "建議對手機號碼進行去識別化處理 (如 0912-***-678)。", // Solution
                   "",  // Reference
                   phoneMatch[0], // Evidence
                   10092, // CWE ID
                   0,     // WASC ID
                   msg    // HTTP Message
               );
           }
       }
   }
   ```
4. 點選編輯器上方的 **儲存圖示**，並確認腳本為 **Enabled (已啟用)** 狀態。

---

### 🧩 觀念補充一：ZAP 腳本類型 (Script Type) 選項解析

在建立腳本時，**Type (類型)** 決定了該腳本在 ZAP 檢測流程中的**觸發時機與角色**：

| 腳本類型 (Script Type) | 觸發時機 / 特性 | 典型應用場景 |
| :--- | :--- | :--- |
| **Passive Rules (被動規則)**<br>*(本實作所選)* | 流量流經代理時，在背景**只讀分析**回應內容（不發送任何額外攻擊封包，安全無副作用）。 | 檢測明文個資外洩 (身分證/信用卡)、缺少安全 Header (CSP/HSTS)、Cookie 缺少 HttpOnly 等。 |
| **Active Rules (主動規則)** | 主動掃描時觸發，會**主動發送攻擊 Payload** 到目標伺服器測試邊界。 | 自訂特殊 SQL Injection、命令注入、特定 CVE 漏洞的探測語句。 |
| **HTTP Sender (HTTP 發送器)** | 在 ZAP 發出請求前或收到回應後**即時攔截並竄改**。 | 自動在每個請求標頭加上自訂 Authorization Token、動態計算 API 簽名 (HMAC)、更新 CSRF Token。 |
| **Authentication (身分驗證)** | 當 ZAP 發現登入 Session 失效時自動觸發。 | 處理非標準 Form 表單的複雜登入流程（如 OAuth2 換票、JWT Refresh、多步驟登入）。 |
| **Payload Generator / Processor** | 在 Fuzzer 暴力破解與模糊測試時觸發。 | 動態產生客製化字典檔（如自動計算 MD5 雜湊字典、Base64 編碼轉換）。 |
| **Proxy (代理腳本)** | 瀏覽器發出請求流經 ZAP Proxy 的最前線攔截。 | 即時過濾雜訊、替換指定網址或擋下特定外部網域。 |
| **Selenium (瀏覽器自動化)** | 控制內建的真實瀏覽器（Chromium/Firefox）。 | 模擬使用者滑鼠點選、觸發動態 JavaScript 事件與探索 SPA 單頁式應用。 |

---

### 🧩 觀念補充二：ZAP 腳本引擎 (Script Engine) 選項解析

當您在 ZAP 建立腳本時，下拉選單會出現多種腳本引擎，其意義與適用情境如下：

| 腳本引擎 (Script Engine) | 語言 / 技術核心 | 特性與適用情境 | 推薦度 |
| :--- | :--- | :--- | :---: |
| **Oracle Nashorn** | JavaScript (ES5.1) | **Java 8 內建的輕量級 JS 引擎**。允許在 JVM 內執行 JavaScript 並直接呼叫 ZAP 的 Java 物件（如 `Alert`、`HttpMessage`）。歷史悠久、相容性佳，但 Java 15 後已逐漸被 Graal.js 取代。 | ⭐️⭐️⭐️⭐️ |
| **Graal.js / ECMAScript** | 現代 JavaScript (ES6+) | **Oracle GraalVM 推出的新一代高效能 JS 引擎**。完全支援現代 JavaScript 語法（`let`, `const`, Promise 等），執行效能極佳，是目前最新版 ZAP 撰寫 JS 規則的首選。 | ⭐️⭐️⭐️⭐️⭐️ (首選) |
| **Mozilla Zest** | 無代碼 / 視覺化 JSON | **Mozilla 與 OWASP 共同開發的宣告式安全腳本語言**。適合「不會寫程式碼」的使用者，可透過 ZAP 介面拖拉、錄製 (Record) 與設定條件來建立自動化測試流程。 | ⭐️⭐️⭐️⭐️ |
| **Python (Jython)** | Python 2.7 / 3 | **在 Java 上運行的 Python 解譯器**。適合習慣使用 Python 撰寫資安 PoC、自動化爬蟲或自訂 Payload 處理規則的測試人員。 | ⭐️⭐️⭐️⭐️ |
| **Groovy / Kotlin / Ruby** | JVM 衍生語言 | 供熟悉特定 JVM 程式語言的工程師使用，功能與 JavaScript 引擎相同，皆可操作 ZAP API。 | ⭐️⭐️ |

> 💡 **選用建議**：若您撰寫的是一般的 JavaScript 規則（如本章的個資偵測），請優先選擇 **`ECMAScript : Graal.js`** 或 **`Oracle Nashorn`** 即可。

---

### 📚 自訂被動掃描腳本開發指南與官方 API 規格

為了讓學員未來能自行開發符合企業需求的自訂防護或稽核規則，以下提供 ZAP 官方標準 `ps.raiseAlert()` 函式的完整參數規格與開發參考資源：

#### 1. `ps.raiseAlert()` 參數完整規格表

```javascript
ps.raiseAlert(risk, confidence, name, description, uri, param, attack, otherInfo, solution, evidence, cweId, wascId, msg);
```

| 參數位置 | 參數名稱 | 資料型態 | 說明與填寫範例 |
| :--- | :--- | :--- | :--- |
| 1 | `risk` | int | **風險等級**：`0` = Informational (資訊), `1` = Low (低), `2` = Medium (中), `3` = High (高) |
| 2 | `confidence` | int | **可信度**：`0` = False Positive, `1` = Low, `2` = Medium, `3` = High, `4` = User Confirmed |
| 3 | `name` | String | **警報標題名稱**（例如："自訂個資洩漏：偵測到身分證字號"） |
| 4 | `description` | String | **漏洞詳細敘述**（例如："網頁回應內容中包含未遮罩的明文身分證字號..."） |
| 5 | `uri` | String | **目標 URL 網址**（透過 `msg.getRequestHeader().getURI().toString()` 取得） |
| 6 | `param` | String | **觸發的參數名稱**（若針對整體 Response Body 檢測，填寫空字串 `""` 即可） |
| 7 | `attack` | String | **攻擊 Payload 字串**（被動掃描無主動攻擊，通常填寫空字串 `""`） |
| 8 | `otherInfo` | String | **其他補充資訊**（可填寫修復細節或內部合規政策指引） |
| 9 | `solution` | String | **防禦修復建議**（例如："落實個資遮罩 (Data Masking) 與去識別化規範。"） |
| 10 | `evidence` | String | **關鍵證據文字**（將高亮標註在 ZAP 檢視視窗中，如匹配到的身分證字號 `A123456789`） |
| 11 | `cweId` | int | **CWE 弱點編號**（例如：`359` 代表機敏個資外洩，`200` 代表一般資訊洩漏） |
| 12 | `wascId` | int | **WASC 威脅分類編號**（例如：`13` 代表 Information Leakage） |
| 13 | `msg` | HttpMessage | **當前的 HTTP 訊息物件**（傳入 `msg` 本體，供 ZAP 關聯請求與回應封包） |

---

#### 2. 官方參考資源與開發文件 (Official References)

學員若想深入研究各類自訂腳本（如 HTTP 請求修改、動態加解密、自動化認證），可直接查閱以下官方資源：

- 📖 **ZAP 官方 Scripting 使用指南**：[https://www.zaproxy.org/docs/desktop/addons/script-console/](https://www.zaproxy.org/docs/desktop/addons/script-console/)
- 🌐 **ZAP 官方 Community Scripts 社群範例庫 (GitHub)**：[https://github.com/zaproxy/community-scripts](https://github.com/zaproxy/community-scripts)
  *(內含大量由全球資安專家撰寫的 JavaScript / Python 被動與主動規則範例)*
- 📑 **ZAP PassiveScanHelper Javadoc API 文件**：[https://javadoc.io/doc/org.zaproxy/zap/latest/org/zaproxy/zap/extension/pscan/PassiveScanHelper.html](https://javadoc.io/doc/org.zaproxy/zap/latest/org/zaproxy/zap/extension/pscan/PassiveScanHelper.html)

---

#### 驗證成果：
1. 使用 ZAP 瀏覽器存取弱點版 `http://localhost:8080/pii_leakage.php`。
2. 切換至 ZAP 的 **Alerts (警示)** 面板，會立刻跳出 **「自訂個資洩漏：偵測到身分證字號 (PII Leakage)」** 與 **「自訂個資洩漏：偵測到手機號碼 (Phone Leakage)」** 的黃色警報！
3. 接著存取修正版 `http://localhost:8081/pii_leakage.php`，觀察到該警報不會觸發，從中即可學到資料掩碼（Data Masking）的效益。

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

### 🌟 方法一：一鍵匯入預設 Context（最推薦 / 3 秒就緒）
專案已預置好完整的 Context 設定檔，包含目標範圍、排除 logout.php、Form 登入規則與 `student01` 測試帳號：
1. 點選 ZAP 上方選單 **File (檔案) > Import Context... (匯入 Context)**。
2. 選擇專案目錄中的 **`zap/VulnCampus_Default.context`** 檔案並開啟。
3. 在左側 Context 面板即會出現 `VulnCampus-Vulnerable`。
4. 點選頂端工具列的 **鎖頭圖示 (Forced User Mode)**，選取 `student01` 即可立即進行認證掃描！

---

### 方法二：手動逐步配置自動登入 (Form-based Auth)
1. 在左側 Sites 對靶場 `http://localhost:8080` 點選右鍵 -> **Include in Context > Default Context**。
2. **⚠️ 重要防呆：排除登出網址 (防止掃描中斷)**：
   - 找到 `http://localhost:8080/logout.php`，點選右鍵 -> **Exclude from Context > Default Context**（或 **Exclude from > Active Scan**）。
   - *原因：若未排除，ZAP 掃描到 `logout.php` 會導致 Session 被直接銷毀登出，使後續需權限的頁面全部漏掃。*
3. 進入 ZAP 瀏覽器進行手動登入。在底部的 **History** 尋找 `POST:login.php` 的登入請求。
4. 對該筆請求點選右鍵 -> **Flag as Context > Default Context: Form-based Auth Target**。
5. ZAP 會彈出 Context 配置畫面。確認 Username、Password 參數映射無誤。
   - **Logged In Indicator (登入成功標記)**：填入 `\Qlogout.php\E` (或 `登出`)。
   - **Logged Out Indicator (登入失效標記)**：填入 `\Qlogin.php\E` (或 `登入`)。
6. 切換至 Context 的 **Users** 面板，點選 **Add** 新增帳號：`student01`，密碼 `password123`，並勾選啟用。
7. 切換至 **Forced User**，啟用強制使用者，設定為 `student01`。
8. 在 ZAP 工具列頂端，鎖上 **鎖頭圖示 (Forced User Mode)**，使 ZAP 自動進行登入 Session 檢測。

---

## 6. 主動掃描與漏洞判讀 (Active Scan)

主動掃描 (Active Scan) 會對目標參數發送多種攻擊語句 (SQLi, XSS, Cmd Injection, SSRF, XXE) 以探測系統邊界。

### 🌟 課堂快速技巧：單一頁面聚焦掃描 (Focused Scanning)
> 💡 **課堂時間管理秘訣**：對整個 Context 或站台執行 Active Scan 可能需要 10~20 分鐘。在進行特定弱點演練與修補驗證時，**強烈建議只對單一目標頁面進行掃描**！
> - 例如在 Sites 目錄樹中找到 `courses.php` 或 `course_detail.php`，直接對該節點按右鍵 -> **Attack > Active Scan**。
> - 這樣只需 5~10 秒即可快速驗證該網頁的 SQLi / XSS 是否存在或已修復完成。

### 全站執行掃描步驟：
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
