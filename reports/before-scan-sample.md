# VulnCampus PHP Lab 安全檢測報告 (掃描前 - 弱點版)

本報告模擬使用 **OWASP ZAP** 對弱點版靶場網站 (`http://localhost:8080`) 進行安全檢測之成果。

---

## 1. 檢測概述

- **檢測目標**：VulnCampus PHP Lab (弱點版)
- **主機網址**：`http://localhost:8080`
- **檢測工具**：OWASP ZAP v2.14
- **檢測時間**：2026-07-07
- **檢測範疇**：被動掃描、Spider 爬行、AJAX Spider 爬行、主動掃描 (Active Scan)

---

## 2. 弱點統計摘要

| 風險等級 | 發現數量 | 核心弱點項目 |
| :--- | :---: | :--- |
| 🔴 **High (高風險)** | 5 | SQL Injection, Path Traversal, Command Injection, Unrestricted File Upload, Missing Access Control |
| 🟡 **Medium (中風險)** | 4 | Cross Site Scripting (Stored & Reflected), Session Fixation, CORS Misconfiguration |
| 🔵 **Low (低風險)** | 3 | Cookie No HttpOnly, Missing Security Headers, Password Reset Predictable Token |

---

## 3. 詳細弱點明細與證據

### 🔴 High - SQL Injection (SQL 注入)
- **受影響頁面**：`/courses.php` 與 `/course_detail.php`
- **漏洞參數**：`q` (GET) 與 `id` (GET)
- **ZAP 攻擊 Payload**：`course_detail.php?id=1 AND 1=1 --`
- **證據 (Evidence)**：網頁能正常顯示課程，且輸入 `id=1'` 時噴出 `PDOException: SQLSTATE[42000]...` 錯誤。

### 🔴 High - Path Traversal (路徑遍歷)
- **受影響頁面**：`/download.php`
- **漏洞參數**：`file` (GET)
- **ZAP 攻擊 Payload**：`download.php?file=../src/db.php`
- **證據**：成功下載 `db.php` 原始碼並取得資料庫密碼 `rootpassword`。

### 🔴 High - Command Injection (命令注入)
- **受影響頁面**：`/admin/ping.php`
- **漏洞參數**：`ip` (POST)
- **ZAP 攻擊 Payload**：`ip=127.0.0.1; whoami`
- **證據**：網頁輸出結果中印出 `www-data` 指令執行成果。

### 🔴 High - Unrestricted File Upload (任意檔案上傳)
- **受影響頁面**：`/upload.php`
- **漏洞參數**：`avatar` (File)
- **證據**：成功上傳 `shell.php` 腳本，且可以直接透過 `/uploads/shell.php` 執行系統命令。

### 🟡 Medium - Stored & Reflected XSS (跨站腳本攻擊)
- **受影響頁面**：`/messages.php` (Stored)、`/courses.php` (Reflected)
- **證據**：留言板直接輸出 `<script>alert(1)</script>` 導致所有瀏覽此頁面之使用者皆會執行該 JS。

---

## 4. 改善建議

1. **資料庫防護**：全面改用參數化查詢 (Prepared Statements) 防禦 SQL 注入。
2. **輸出轉義**：對所有使用者輸入的欄位，在網頁輸出時使用 `htmlspecialchars` 進行編碼。
3. **下載限制**：禁止由前端控制檔案路徑，改用 File ID 對照下載。
4. **命令執行隔離**：使用原生 API 代替 `shell_exec`，或使用 `filter_var` 驗證格式。
5. **上傳防護**：檢查副檔名與實體 MIME 類型， uploads 目錄限制腳本解析。
