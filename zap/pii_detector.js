/**
 * ==============================================================================
 * VulnCampus PII Detector - 自訂個資外洩被動掃描腳本 (JavaScript / Graal.js)
 * ==============================================================================
 * 
 * 📌 腳本基本資訊：
 * - 適用類型 (Type)：Passive Rules (被動掃描規則)
 * - 腳本引擎 (Engine)：ECMAScript : Graal.js / Oracle Nashorn
 * - 執行時機：當 HTTP Response 經過 ZAP Proxy 代理時自動在背景分析
 * 
 * 📚 官方參考資源與開發文件：
 * - ZAP 官方腳本開發手冊：https://www.zaproxy.org/docs/desktop/addons/script-console/
 * - ZAP Community Scripts 範例庫：https://github.com/zaproxy/community-scripts
 * - PassiveScanHelper API 文件：https://javadoc.io/doc/org.zaproxy/zap/latest/org/zaproxy/zap/extension/pscan/PassiveScanHelper.html
 * 
 * 🧩 ps.raiseAlert() 參數規格說明：
 * 1. risk        : 風險等級 (0=Informational, 1=Low, 2=Medium, 3=High)
 * 2. confidence  : 可信度 (0=False Positive, 1=Low, 2=Medium, 3=High, 4=User Confirmed)
 * 3. name        : 警報標題名稱 (String)
 * 4. description : 漏洞詳細說明 (String)
 * 5. uri         : 觸發漏洞的目標 URL (String)
 * 6. param       : 有問題的參數名稱 (String，若為整體 Response 則留空 "")
 * 7. attack      : 攻擊字串 (String，被動掃描通常留空 "")
 * 8. otherInfo   : 其他補充資訊 / 修復建議 (String)
 * 9. solution    : 安全防禦解決方案 (String)
 * 10. evidence   : 觸發警報的關鍵證據文字 (String，將高亮顯示於 ZAP)
 * 11. cweId      : 常見弱點列舉編號 (int，如 359 = PII Leakage, 200 = Info Disclosure)
 * 12. wascId     : Web 安全威脅分類編號 (int，如 13 = Information Leakage)
 * 13. msg        : 當前的 HTTP Message 物件 (HttpMessage)
 */

function scan(ps, msg, src) {
    // 排除二進位圖片、影音等檔案，僅分析有內容的文字/HTML/JSON 回應
    if (!msg.getResponseHeader().isImage() && msg.getResponseBody().length() > 0) {
        var body = msg.getResponseBody().toString();
        var uri = msg.getRequestHeader().getURI().toString();
        
        // ----------------------------------------------------------------------
        // 1. 偵測台灣身分證字號 (1 位大寫英文 + 1 或 2 + 8 位數字)
        // ----------------------------------------------------------------------
        var idRegex = /[A-Z][12]\d{8}/g;
        var idMatch = idRegex.exec(body);
        if (idMatch) {
            ps.raiseAlert(
                2, // Risk: Medium (中風險)
                3, // Confidence: High (高可信度)
                "自訂個資洩漏：偵測到身分證字號 (PII Leakage)", 
                "網頁回應內容中包含未經遮罩的明文身分證字號：" + idMatch[0] + "，恐違反個人資料保護法規。", 
                uri, 
                "", 
                "", 
                "建議對身分證字號進行資料遮罩處理 (例如：A12****789) 或去識別化後再回傳前端。", 
                "落實資料庫輸出過濾與個資遮蔽 (Data Masking) 規範。", 
                idMatch[0], // Evidence: 身分證字號本體 (ZAP 將高亮顯示)
                359,        // CWE-359: Exposure of Private Personal Information to an Unauthorized Actor
                13,         // WASC-13: Information Leakage
                msg
            );
        }
        
        // ----------------------------------------------------------------------
        // 2. 偵測台灣行動電話號碼 (09 開頭 + 8 位數字，支援連字號格式)
        // ----------------------------------------------------------------------
        var phoneRegex = /09\d{2}-?\d{3}-?\d{3}/g;
        var phoneMatch = phoneRegex.exec(body);
        if (phoneMatch) {
            ps.raiseAlert(
                2, // Risk: Medium (中風險)
                3, // Confidence: High (高可信度)
                "自訂個資洩漏：偵測到手機號碼 (Phone Leakage)", 
                "網頁回應內容中包含未經去識別化的明文行動電話號碼：" + phoneMatch[0] + "。", 
                uri, 
                "", 
                "", 
                "建議對聯絡電話進行去識別化隱碼處理 (例如：0912-***-678)。", 
                "落實輸出編碼與機敏欄位隱碼政策。", 
                phoneMatch[0], // Evidence: 手機號碼本體
                359,           // CWE-359
                13,            // WASC-13
                msg
            );
        }
    }
}
