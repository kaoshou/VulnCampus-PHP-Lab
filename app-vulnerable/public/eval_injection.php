<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$expr = $_POST['expr'] ?? '';
$result = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $expr !== '') {
    // 漏洞點 (CWE-95)：將使用者輸入直接拼接進 eval() 函數中動態執行，導致任意 PHP 代碼執行 (RCE)
    try {
        // 隱藏的輸出捕獲，防止 phpinfo() 等直接打亂整個 HTML 渲染，但仍會執行並回顯
        ob_start();
        $eval_ret = eval("return " . $expr . ";");
        $ob_content = ob_get_clean();
        
        if ($eval_ret !== null) {
            $result = $eval_ret;
        } else {
            $result = $ob_content;
        }
    } catch (Throwable $t) {
        $error = "計算引擎出錯：" . $t->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>⚙️ Eval 程式碼注入漏洞 (CWE-95) - VulnCampus</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
    <style>
        body { font-family: sans-serif; background-color: #f4f6f9; }
        .instructions { background-color: #ffeef0; border-left: 5px solid #dc3545; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body class="bg-light">

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
        <h2>⚙️ Eval 程式碼注入漏洞 (CWE-95) 演練</h2>
        <div>
            <a href="/index.php" class="btn btn-secondary">回首頁</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger font-weight-bold py-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- 左側：工具表單與說明 -->
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white font-weight-bold">
                    🧮 線上智能數學公式計算器
                </div>
                <div class="card-body">
                    <p class="text-muted">輸入任意數學算式，後端的 PHP 動態執行引擎將即時計算出結果。</p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="expr">請輸入數學公式：</label>
                            <input type="text" name="expr" id="expr" class="form-control" placeholder="例如: 100 * (5 - 3) / 2" value="<?= htmlspecialchars($expr) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-danger btn-block font-weight-bold">開始動態計算</button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm instructions p-3">
                <h5 class="text-danger font-weight-bold">🎯 Eval 注入演練指引</h5>
                <ol class="pl-3 mb-0 text-muted small">
                    <li class="mb-2"><strong>漏洞成因</strong>：後端直接使用了功能強大但極具安全風險的 <code>eval()</code> 函數來動態評估與執行字串算式，且未對使用者輸入進行任何過濾或字元白名單檢查。</li>
                    <li class="mb-2"><strong>正常測試</strong>：<br>
                        輸入 <code>50 + (10 * 3)</code>，觀察是否能算出 <code>80</code> 的結果。
                    </li>
                    <li class="mb-2"><strong>漏洞驗證 (Eval Injection)</strong>：<br>
                        因為 eval 會將輸入當成 PHP 程式碼執行，試著輸入：
                        <br>- 執行命令：<code>system('whoami')</code>
                        <br>- 查看環境：<code>phpinfo()</code>
                        <br>並送出查詢。
                    </li>
                    <li class="mb-2">觀察右側輸出，系統是否直接回顯了當前伺服器的執行帳號或整個 PHP 組態環境？這說明攻擊者已成功取得了遠端代碼執行 (RCE) 權限！</li>
                </ol>
            </div>
        </div>

        <!-- 右側：結果與代碼審查 -->
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white font-weight-bold">
                    📊 計算與回顯結果
                </div>
                <div class="card-body bg-white" style="min-height: 250px;">
                    <?php if ($result !== ''): ?>
                        <pre><code><?= htmlspecialchars($result) ?></code></pre>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <h4>等待計算執行...</h4>
                            <p class="small">請在左方輸入公式並點選計算。</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white font-weight-bold">
                    📝 脆弱 Eval 執行代碼
                </div>
                <div class="card-body bg-light">
                    <pre style="background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px;" class="small"><code>// 直接將變數拼接入 eval 中動態運行，導致 RCE
$result = eval("return " . $expr . ";");</code></pre>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
