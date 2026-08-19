# VulnCampus PHP Lab - PHP 版網站弱點檢測與安全改善教學靶場

本專案是一個專為課程《網站弱點檢測與安全改善：ZAP 應用實務》設計的 PHP 輕量化教學靶場。
透過模擬「校園活動、課程查詢與報名平台」的 Web 應用程式，帶領學員實踐 OWASP Top 10:2025 安全風險的檢測與程式修補。

---

## ⚠️ 免責聲明與合法使用聲明

1. **僅供本機教學與授權演練**：本專案只用於合法授權的本機安全研究與教學環境，嚴禁將弱點版 (Vulnerable App) 部署到公開網域或生產環境！
2. **無真實個資**：本專案所有使用者資料、身分證字號、聯絡電話、信箱等均為自動產生的虛擬假個資，絕無真實個資洩漏。
3. **教學漏洞專用與環境安全**：本專案為資安教學與漏洞防禦對照設計之靶場，**內含刻意保留之真實安全漏洞**（如系統命令注入、SQL 注入、跨站腳本與權限缺失等）。本靶場**僅限本機封閉環境演練，嚴禁暴露於網際網路或未受信任的網路環境**。
4. **不使用時請關閉服務**：課程演練結束或暫不使用時，**請務必執行 `docker compose down` 關閉所有容器**，避免本機成為潛在的網路受攻擊面。

---

## 🛠️ 技術架構與連接埠口

本靶場基於原生 PHP 8.2、Apache 網頁伺服器及 MariaDB 資料庫，透過 Docker Compose 一鍵啟動：

- **弱點版網站 (app-vulnerable)**：`http://localhost:8080` (用於展示與進行 ZAP 弱點掃描)
- **安全修正版網站 (app-fixed)**：`http://localhost:8081` (用於對照修補成效，可被 ZAP 掃描比對)
- **phpMyAdmin 資料庫管理員**：`http://localhost:8082` (用於視覺化管理資料庫，預設帳密 `root / rootpassword`)

---

## 🚀 快速啟動指引

### 方法一：使用一鍵管理腳本 (推薦學員快速操作)
- **Windows (點兩下或於 PowerShell 執行)**：
  - `start.bat`：一鍵啟動靶場服務（首次啟動請等待 10~15 秒供資料庫完成初始化）。
  - `stop.bat`：一鍵停止靶場容器。
  - `reset.bat`：**一鍵重置靶場**（當資料庫被改壞或想清除測試上傳檔案時，點擊此腳本 10 秒快速自救還原！）。
- **macOS / Linux (終端機執行)**：
  - `bash start.sh`：一鍵啟動靶場（或先執行 `chmod +x *.sh` 後輸入 `./start.sh`）
  - `bash stop.sh`：一鍵停止靶場
  - `bash reset.sh`：一鍵重置資料庫與靶場環境

### 方法二：使用標準原生 Docker Compose 指令 (跨平台通用 / 避免腳本突發狀況)
若不想使用腳本，或遇到腳本權限問題，可直接在終端機（PowerShell / Command Prompt / Terminal）使用標準 Docker 指令：

1. **啟動靶場服務 (背景執行並自動重新建置)**：
   ```bash
   docker compose up -d --build
   ```
2. **停止靶場運行 (保留現有資料庫數據)**：
   ```bash
   docker compose down
   ```
3. **徹底清除舊資料並重置為初始狀態 (還原預設種子資料庫)**：
   ```bash
   docker compose down -v
   docker compose up -d --build
   ```
4. **檢視容器運行狀態**：
   ```bash
   docker compose ps
   ```
5. **檢視即時容器日誌**：
   ```bash
   docker compose logs -f
   ```

## 🛡️ 安全掛載與演練隔離防護 (Docker Volumes)

為了避免學員在正式演練中上傳惡意檔案或 Webshell 覆蓋真實檔案，本靶場配置了雙重安全防護：
1. **容器內唯讀掛載 (`:ro`)**：網頁程式碼目錄在容器內部為唯讀屬性。學員的後門 Webshell 無法篡改或覆蓋本地實體檔案；但當您在本機（宿主機）使用編輯器（如 VS Code）修改代碼時，變更依然會即時同步並在網頁上生效。
2. **上傳目錄虛擬化隔離**：圖片與大頭貼上傳目錄 (`uploads/`) 被單獨隔離掛載至 Docker 內部虛擬命名磁碟卷中（`vuln-vulnerable-uploads` 與 `vuln-fixed-uploads`）。學員上傳的 Webshell 僅會存留在 Docker 的虛擬空間，完全不會污染或寫入您的本機實體專案目錄，確保主機安全。

---

## 🔑 測試帳號與密碼對照表

本系統預置了以下三種角色的測試帳密，供手動測試與 ZAP 認證掃描使用：

| 角色 / 權限 | 帳號 (Username) | 弱點版密碼 | 修正版密碼 | 說明 |
| :--- | :--- | :--- | :--- | :--- |
| **管理員** | `admin` | `admin` | `AdminPassword!2026` | 後台高權限管理員，具備診斷與名冊匯出權限 |
| **一般學生 01** | `student01` | `password123` | `Student01Pass!2026` | 用於正常瀏覽、留言與活動報名 |
| **一般學生 02** | `student02` | `password123` | `Student02Pass!2026` | 用於測試 IDOR 越權 (例如 student01 可修改或取消其檔案) |
| **教師** | `teacher01` | `password123` | `Teacher01Pass!2026` | 可以查看自己教授的課程 |

---

## 📂 專案目錄結構說明

```text
vuln-campus-php-lab/
├── docker-compose.yml        # Docker 服務定義 (含 MariaDB 健康檢查)
├── README.md                 # 本說明文件
├── start.bat / start.sh      # 一鍵啟動腳本 (Windows / Mac-Linux)
├── stop.bat / stop.sh        # 一鍵停止腳本
├── reset.bat / reset.sh      # 一鍵還原與清除舊資料庫腳本
├── app-vulnerable/           # 弱點版網站目錄 (Port 8080)
│   ├── public/               # Web 根目錄 (courses.php, login.php 等)
│   ├── src/                  # 資料庫與 Session 設定 (db.php, helpers.php)
│   └── Dockerfile            # 弱點版映像建置設定 (內含 gcc, ping)
├── app-fixed/                # 安全修正版網站目錄 (Port 8081)
│   ├── public/
│   ├── src/
│   └── Dockerfile
├── db/                       # 資料庫初始化檔案
│   ├── init.sql              # 資料庫 schema 建立 (建立 vuln_db 與 fixed_db)
│   └── seed.sql              # 初始測試資料與密碼雜湊置入
├── docs/                     # 教學說明文件 (繁體中文，適合課程教材)
│   ├── 00_課程教材_WEB安全與OWASP_ZAP實踐指南.md # 完整課程大綱與觀念講義
│   ├── 01_課程靶場使用說明.md # 靶場操作基礎與 Docker 啟動教學
│   ├── 02_OWASP_TOP10_對照表.md # 十大弱點在靶場中的分布、手動驗證與修補對照
│   ├── 03_ZAP_掃描操作手冊.md  # ZAP Spider/Active Scan/認証掃描實務操作
│   ├── 04_修補前後比較表.md   # 提供學員演練撰寫的修補對照模板
│   ├── 05_教師用弱點答案對照表.md # 教師演示各漏洞、手動改包與上課重點指引
│   ├── 06_常見問題排除.md    # 解決資料庫連線、ZAP 認證設定及 Proxy 常見故障
│   └── 07_學員課堂實作闖關檢核表.md # 課堂實作任務指引與作業簽核表
├── zap/                      # ZAP 設定檔
│   ├── VulnCampus_Default.context # ZAP 預設認證 Context (可一鍵匯入)
│   └── zap-baseline-example.yaml  # 自動化 CI/CD 掃描設定範例
└── reports/                  # ZAP 掃描報告範例 (Before/After 成果對比)
```

---

## 🛡️ OWASP Top 10:2025 弱點演練對照表

本靶場包含以下精確設計的漏洞，學員可在 **弱點版 (Port 8080)** 進行攻擊檢測，並在 **修正版 (Port 8081)** 驗證防護：

1. **A01:2025 Broken Access Control**：`profile.php?id=X` (IDOR)、未授權匯出名冊、審核 API、`hidden_control.php` (客戶端安全控制缺失) 及 `switch_defect.php` (Switch case 缺少 break 越權)。
2. **A02:2025 Security Misconfiguration**：缺失安全回應標頭、Cookie 缺少 HttpOnly 標記、`debug.php` 暴露配置。
3. **A03:2025 Supply Chain Failures**：引入漏洞舊版前端套件 (jQuery 1.12.4 / Bootstrap 4.0.0)。
4. **A04:2025 Cryptographic Failures**：MD5 密碼雜湊、可預測的 MD5 密碼重設 Token、Base64 儲存假個資、`cookie_sensitive.php` (明文 Cookie 敏感資訊) 及 `cve_2025_7783.php` (弱隨機 Boundary 生成)。
5. **A05:2025 Injection**：課程查詢 SQL Injection、Stored/Reflected XSS、`download.php` Path Traversal、後台命令注入、`command_injection.php` (命令注入專屬頁面)、`eval_injection.php` (Eval 代碼注入)、`xpath_injection.php` (XPath 注入) 及 `crlf_injection.php` (CRLF 響應分裂注入)。
6. **A06:2025 Insecure Design**：活動名額併發 Race Condition 超賣、報名數量可輸入負數、優惠券重複使用限制失效。
7. **A07:2025 Authentication Failures**：弱密碼、帳密提示錯誤細節 (帳號列舉)、Session Fixation、無登入次數限制。
8. **A08:2025 Data Integrity Failures**：修改資料隱藏欄位 `role=user` 竄改 (Mass Assignment)。
9. **A09:2025 Logging Failures**：無系統稽核日誌 (安全版已寫入 `audit_logs` 並提供後台檢視)。
10. **A10:2025 Mishandling Exceptions**：資料庫 Exception 直接噴在網頁畫面上。
11. **補充演練 - 緩衝區溢位 (Buffer Overflow)**：呼叫底層 C 語言二進位程式，輸入超長字串引發記憶體溢位與系統崩潰 (SIGSEGV 退出碼 139)。

---

## 📈 教學與檢測建議流程

1. **環境啟動**：執行 `docker compose up -d --build`，確認可存取 `http://localhost:8080` (弱點) 與 `http://localhost:8081` (修正)。
2. **初次掃描 (被動掃描)**：開啟 ZAP 瀏覽器或設定 Proxy，瀏覽弱點版網站，觀察 Alerts 產生的 Security Headers 缺失。
3. **主動掃描 (Active Scan)**：對 `courses.php` 與 `course_detail.php` 執行 Active Scan，預期 ZAP 將會成功偵測出 **SQL Injection** 與 **XSS** 漏洞。
4. **手動驗證與攔截 (Breakpoint / Repeater)**：
   - 登入 `student01`，修改個人檔案時，開啟 ZAP 攔截器將 hidden 欄位 `role=student` 修改為 `role=admin`，送出後重新登入，驗證是否成功升權為管理員。
   - 報名活動時，手動將數量欄位修改為 `-1`，驗證報名結餘金額。
5. **程式修補與重新檢測**：對比 `app-vulnerable` 與 `app-fixed` 的程式碼設計差異（可參閱代碼中的 `// 教學用弱點` 與 `// 修補重點` 註解）。
6. **防護驗證**：利用 ZAP 掃描修正版 `http://localhost:8081`，觀察 Alerts 中高/中風險的弱點已完全消除。

---

## 👤 作者與專案資訊

- **專案名稱**：VulnCampus PHP Lab (校園活動與課程報名平台教學靶場)
- **內容設計**：崑山科科技大學 鄭郁翰
- **適用課程**：網站弱點檢測與安全改善：ZAP 應用實務、安全程式碼編寫 (Secure Coding)
- **專案目的**：專為教學與學術研究設計的 Web 漏洞修補對照靶場，嚴禁部署於公開網路。
