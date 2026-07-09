<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$expr = $_POST['expr'] ?? '';
$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $expr !== '') {
    // 安全修補防護 (CWE-95)：利用嚴格的正則白名單過濾，僅允許基本數學運算字元，禁止任何 PHP 函數字元
    if (preg_match('/^[0-9+\\-*\/().\s]+$/', $expr)) {
        try {
            ob_start();
            $eval_ret = eval("return " . $expr . ";");
            $ob_content = ob_get_clean();
            
            if ($eval_ret !== null) {
                $result = $eval_ret;
            } else {
                $result = $ob_content;
            }
        } catch (Throwable $t) {
            $error = "計算引擎執行出錯：" . $t->getMessage();
        }
    } else {
        $error = "❌ 安全警告：輸入算式中包含非法字元（如英文字母或非算術符號），已拒絕執行以防堵代碼注入！";
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>⚙️ Eval 程式碼注入修補 (CWE-95) - VulnCampus</title>
    <!-- 使用 Bootstrap 5 與修正版風格對齊 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; background-color: #f8fafc; color: #0f172a; }
        .instructions { background-color: #e8f5e9; border-left: 5px solid #2e7d32; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2 class="text-primary">⚙️ Eval 程式碼注入安全修補 (CWE-95)</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold py-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與安全防禦說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-success text-white font-weight-bold py-3">
                    🧮 線上智能數學公式計算器 (已進行安全防禦)
                </div>
                <div class="card-body p-4 bg-white">
                    <p class="text-muted">本安全版引入了正則白名單，阻斷任何包含字母或系統函數的輸入傳入執行。</p>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label for="expr" class="form-label font-weight-bold">請輸入數學公式：</label>
                            <input type="text" name="expr" id="expr" class="form-control" placeholder="例如: 100 * (5 - 3) / 2" value="<?= htmlspecialchars($expr) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-success text-white w-100 font-weight-bold">安全動態計算</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-4 border-0">
                <h5 class="text-success font-weight-bold mb-3">🛡️ 安全修補對照說明</h5>
                <p class="text-muted small">
                    <strong>如何防範 Eval 程式碼注入 (CWE-95)？</strong>
                    <br><br>
                    <strong>1. 絕不使用 eval 處理不可信的輸入</strong>：
                    在生產環境中，最好的實踐是完全避免使用 <code>eval()</code> 來執行計算。應改用專門的安全數學解析引擎（如抽象語法樹 AST 解析器或專門的數學庫）。
                    <br><br>
                    <strong>2. 實施嚴格的正則白名單 (Regex Whitelist)</strong>：
                    如果在此案例中必須使用動態求值，應對輸入執行極為嚴苛的白名單正則過濾。例如使用 <code>/^[0-9+\\-*\/().\s]+$/</code>，這限制了輸入字元只能為數字和四則運算符，從而完全阻止了如 <code>system()</code>, <code>eval()</code>, <code>exec()</code> 等包含英文字母的 PHP 敏感函數傳入執行。
                </p>
            </div>
        </div>

        <!-- 右側：結果輸出與代碼修補對照 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📊 計算結果顯示
                </div>
                <div class="card-body bg-white p-4" style="min-height: 250px;">
                    <?php if ($result !== ''): ?>
                        <pre><code><?= htmlspecialchars($result) ?></code></pre>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <h4>等待計算執行...</h4>
                            <p class="small">請在左方輸入並送出，此處將顯示經過防禦檢查後的安全計算結果。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white font-weight-bold py-3">
                    📝 安全動態執行代碼 (CWE-95 修補)
                </div>
                <div class="card-body bg-white p-4">
                    <h6 class="font-weight-bold text-secondary">修補前 (直接將變數傳入 eval)：</h6>
                    <pre style="background-color: #f8f9fa; color: #b91c1c; padding: 12px; border: 1px dashed #fca5a5; border-radius: 5px;" class="small"><code>$result = eval("return " . $expr . ";");</code></pre>

                    <h6 class="font-weight-bold text-success mt-4">修補後 (限制字元僅能為數學運算字元)：</h6>
                    <pre style="background-color: #f0fdf4; color: #166534; padding: 12px; border: 1px dashed #bbf7d0; border-radius: 5px;" class="small"><code>// 限制僅能輸入數字與算術運算符，不允許任何英文字母與函數字眼
if (preg_match('/^[0-9+\\-*\/().\s]+$/', $expr)) {
    $result = eval("return " . $expr . ";");
} else {
    // 報警並拒絕執行
}</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
