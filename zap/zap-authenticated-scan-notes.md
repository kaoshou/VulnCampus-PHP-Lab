# ZAP 認證掃描與自動化設定筆記

本文件提供 ZAP 認證掃描的操作要點、Scope 設定建議以及自動化掃描 (Automation Framework) 的 YAML 範例。

---

## 1. 認證掃描關鍵步驟 (Authenticated Scan Notes)

當您要讓 ZAP 進入 `profile.php` 或 `admin/` 進行登入後的深度弱點掃描時，請遵循以下配置：

### A. 使用 Session/Cookie 維持
1. **瀏覽與捕獲**：使用 ZAP 內建瀏覽器訪問 `http://localhost:8080/login.php`，輸入 `student01` / `password123` 登入成功。
2. **鎖定 Session**：
   - 在 ZAP 下方 **Http Sessions** 面板中，您會看到當前 `PHPSESSID` 的值。
   - 在該 Session 上點選右鍵，選擇 **Set as Active (設為作用中)**。
   - 這能確保 ZAP 在後續的掃描請求中自動附帶此 Cookie。

### B. 使用 ZAP 認證機制 (Form-Based Authentication)
若 Session 在掃描過程中因注入測試而被登出，為防止 ZAP 失去權限，應配置 Form-Based 自動登入：
- **Context 設定**：
  1. 對 Context 按兩下，選擇 **Authentication (認證)**。
  2. 選擇 **Form-based Authentication**。
  3. Login Form Target URL 填入: `http://localhost:8080/login.php`
  4. 參數對應：`username` 對應 `username`，`password` 對應 `password`。
  5. 登入/登出特徵：
     - Logged in indicator: `\Qlogout.php\E` (或 `登出`)
     - Logged out indicator: `\Qlogin.php\E` (或 `登入`)
- **Users 設定**：
  1. 選擇 **Users** 面板。
  2. 新增使用者，輸入帳密後啟用。
- **Forced User (強制使用者)**：
  1. 選擇 **Forced User** 面板。
  2. 勾選啟用，並下拉選擇剛才建立的使用者。
  3. 此時 ZAP 工具列上的「鎖頭」圖示會亮起，代表 ZAP 會強迫所有超出 Session 的請求重新進行登入。

---

## 2. 避免掃描外部網站的提醒 (Scope Rules)

> [!WARNING]
> **重要防線**：主動掃描 (Active Scan) 具有攻擊性，嚴禁將掃描範圍擴展到靶場以外的任何網站！

為了防止 ZAP 的攻擊 Payload 掃描到網頁引入的外部 CDN（如 `cdn.jsdelivr.net` 或 `code.jquery.com`）：
1. 在 Sites 樹狀目錄中，對本機靶場 `http://localhost:8080` 按右鍵，點選 **Include in Context > Default Context**。
2. 切換至 Context 的 **Exclude from Context (從上下文中排除)** 面板。
3. 新增排除正規表達式，阻擋一切外部 CDN 與網址：
   - `^https?://(?!localhost).*$` (排除任何非 localhost 的外網連結)。
4. 在 ZAP 上方工具列的掃描範圍 (Scope) 中，切換為 **Show only URLs in Scope (僅顯示 Scope 內 URL)**。

---

## 3. ZAP Automation Framework (自動化掃描 YAML 範例)

ZAP 支持利用 YAML 設定檔進行 DevSecOps CI/CD 自動化掃描。以下是一個基本的 YAML 範例：

```yaml
# zap-baseline-example.yaml
# 用於快速對 VulnCampus 靶場進行被動與主動掃描

env:
  contexts:
    - name: VulnCampus-Vuln
      urls:
        - http://localhost:8080/
      includePaths:
        - http://localhost:8080/.*
      excludePaths:
        - http://localhost:8080/logout.php # 防止自動登出
  parameters:
    failOnError: true
    failOnWarning: false
    progressToStdout: true

jobs:
  - type: passiveScan-config
    parameters:
      maxAlertsPerRule: 10
  - type: spider
    parameters:
      context: VulnCampus-Vuln
      maxDuration: 5
  - type: activeScan
    parameters:
      context: VulnCampus-Vuln
      policy: Default Policy
  - type: report
    parameters:
      template: modern-html
      reportDir: /zap/reports
      reportFile: vuln-campus-baseline-report
```

您可以使用 ZAP CLI 執行此自動化任務：
```bash
zap.sh -cmd -autorun zap-baseline-example.yaml
```
