# VulnCampus PHP Lab - PHP 版網站弱點檢測與安全改善教學靶場

本專案是一個專為課程《網站弱點檢測與安全改善：OWASP ZAP 應用》設計的 PHP 輕量化教學靶場。
透過模擬「校園活動、課程查詢與報名平台」的 Web 應用程式，帶領學員實踐 OWASP Top 10:2025 安全風險的檢測與程式修補。

---

## ⚠️ 免責聲明與合法使用聲明

1. **僅供本機教學與授權演練**：本專案只用於合法授權的本機安全研究與教學環境，嚴禁將弱點版 (Vulnerable App) 部署到公開網域或生產環境！
2. **無真實個資**：本專案所有使用者資料、身分證字號、聯絡電話、信箱等均為自動產生的虛擬假個資，絕無真實個資洩漏。
3. **無安全風險工具**：本專案不包含任何惡意攻擊或連線外部非法 C2 伺服器的指令與木馬，請安心使用。

---

## 🛠️ 技術架構與連接埠口

本靶場基於原生 PHP 8.2、Apache 網頁伺服器及 MariaDB 資料庫，透過 Docker Compose 一鍵啟動：

- **弱點版網站 (app-vulnerable)**：`http://localhost:8080` (用於展示與進行 ZAP 弱點掃描)
- **安全修正版網站 (app-fixed)**：`http://localhost:8081` (用於對照修補成效，可被 ZAP 掃描比對)
- **phpMyAdmin 資料庫管理員**：`http://localhost:8082` (用於視覺化管理資料庫，預設帳密 `root / rootpassword`)

---

## 🚀 快速啟動指引

請確保本機已安裝 [Docker](https://www.docker.com/) 與 Docker Compose，然後在專案根目錄下執行以下指令：

### 1. 啟動服務 (自動建置並執行)
```bash
docker compose up -d --build
```

### 2. 停止並保留資料
```bash
docker compose down
```

### 3. 重建資料庫並徹底清除舊資料
```bash
docker compose down -v
docker compose up -d --build
```

---

## 🔑 測試帳號與密碼對照表

本系統預置了以下三種角色的測試帳密，供手動測試與 ZAP 認證掃描使用：

| 角色 / 權限 | 帳號 (Username) | 弱點版密碼 (MD5 儲存) | 修正版密碼 (Bcrypt 儲存) | 說明 |
| :--- | :--- | :--- | :--- | :--- |
| **管理員** | `admin` | `admin` | `AdminPassword!2026` | 後台高權限管理員，具備診斷與名冊匯出權限 |
| **一般學生 01** | `student01` | `password123` | `password123` | 用於正常瀏覽、留言與活動報名 |
| **一般學生 02** | `student02` | `password123` | `password123` | 用於測試 IDOR 越權 (例如 student01 可修改或取消其檔案) |
| **教師** | `teacher01` | `password123` | `password123` | 可以查看自己教授的課程 |

---

## 📂 專案目錄結構說明

```text
vuln-campus-php-lab/
├── docker-compose.yml        # Docker 服務定義
├── README.md                 # 本說明文件
├── app-vulnerable/           # 弱點版網站目錄
│   ├── public/               # Web 根目錄 (含 courses.php, login.php, messages.php 等)
│   ├── src/                  # 資料庫與 Session 設定 (db.php, helpers.php)
│   └── Dockerfile            # 弱點版映像建置設定
├── app-fixed/                # 安全修正版網站目錄 (結構與弱點版對稱，但已做安全防護)
│   ├── public/
│   ├── src/
│   └── Dockerfile
├── db/                       # 資料庫初始化檔案
│   ├── init.sql              # 資料庫 schema 建立 (建立 vuln_db 與 fixed_db)
│   └── seed.sql              # 初始測試資料與密碼雜湊置入
├── docs/                     # 教學說明文件 (繁體中文，適合課程教材)
│   ├── 01_課程靶場使用說明.md # 靶場操作基礎與 Docker 啟動教學
│   ├── 02_OWASP_TOP10_對照表.md # 十大弱點在靶場中的分布、手動驗證與修補對照
│   ├── 03_ZAP_掃描操作手冊.md  # ZAP Spider/Active Scan/認証掃描實務操作
│   ├── 04_修補前後比較表.md   # 提供學員演練撰寫的修補對照模板
│   ├── 05_教師用弱點答案對照表.md # 教師演示各漏洞、手動改包與上課重點指引
│   └── 06_常見問題排除.md    # 解決資料庫連線、ZAP 認證設定及 Proxy 常見故障
├── zap/                      # ZAP 範例設定檔 (Baseline/Active Scan 參數)
└── reports/                  # ZAP 掃描報告範例 (Before/After 成果對比)
```

---

## 🛡️ OWASP Top 10:2025 弱點演練對照表

本靶場包含以下精確設計的漏洞，學員可在 **弱點版 (Port 8080)** 進行攻擊檢測，並在 **修正版 (Port 8081)** 驗證防護：

1. **A01:2025 Broken Access Control**：`profile.php?id=X` (IDOR)、未授權匯出名冊及審核 API。
2. **A02:2025 Security Misconfiguration**：缺失安全回應標頭、Cookie 缺少 HttpOnly 標記、`debug.php` 暴露配置。
3. **A03:2025 Supply Chain Failures**：引入漏洞舊版前端套件 (jQuery 1.12.4 / Bootstrap 4.0.0)。
4. **A04:2025 Cryptographic Failures**：MD5 密碼雜湊、可預測的 MD5 密碼重設 Token、Base64 儲存假個資。
5. **A05:2025 Injection**：課程查詢 SQL Injection、Stored/Reflected XSS、`download.php` Path Traversal、後台命令注入。
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
- **內容設計**：崑山科科技大學 鄭郁翰 老師
- **適用課程**：網站弱點檢測與安全改善、OWASP ZAP 應用實務、安全程式碼編寫 (Secure Coding)
- **專案目的**：專為教學與學術研究設計的 Web 漏洞修補對照靶場，嚴禁部署於公開網路。
