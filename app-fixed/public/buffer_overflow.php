<?php
require_once __DIR__ . '/../src/helpers.php';
check_auth();

$input = $_GET['input'] ?? '';
$output_msg = '';
$error_msg = '';

if ($input !== '') {
    // 縱深防禦 1：在 PHP 端進行嚴格的長度校驗，大於等於 64 個字元就直接阻斷並返回錯誤
    if (strlen($input) >= 64) {
        http_response_code(400);
        $error_msg = "🚫 [安全性錯誤] 偵測到異常超長字串！輸入長度為 " . strlen($input) . " 字元，已超出最大限制 63 字元。系統已自動攔截此潛在的緩衝區溢位攻擊 (CWE-120)。";
    } else {
        // 使用 escapeshellarg 避免命令注入 (Command Injection)
        $safe_input = escapeshellarg($input);
        $cmd = "/usr/local/bin/vuln-process " . $safe_input;
        
        $output = [];
        $retval = 0;
        exec($cmd, $output, $retval);
        
        if ($retval === 139) {
            http_response_code(500);
            $error_msg = "🚨 [系統崩潰] 伺服器核心拋出 Exception：Segmentation fault (Core Dumped)！";
        } else {
            $output_msg = implode("\n", $output);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>底層緩衝區溢位 (Buffer Overflow) 演練 - 安全修正版</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
        .btn-primary { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <h2 class="text-success mb-4">🛡️ 緩衝區溢位 (Buffer Overflow) 安全修正版</h2>
                <p class="text-muted">本修正版實施了<strong>雙重防線 (Defense in Depth)</strong>：<br>
                1. <strong>後端驗證 (PHP)</strong>：強制限制輸入不可大於或等於 64 個字元。<br>
                2. <strong>底層修補 (C)</strong>：底層 C 二進位採用安全的 <code>strncpy</code> 進行字串複製，避免超出緩衝區邊界。</p>
                <hr>
                
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="input">請輸入字串傳遞給底層 C 程式：</label>
                        <input type="text" class="form-control" id="input" name="input" value="<?php echo htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); ?>" placeholder="請輸入字串..." required>
                        <small class="form-text text-muted">提示：在此版本中，若輸入 64 個字元以上的長字串，將會被 PHP 前端驗證阻截，且底層 C 程式亦有安全防護。</small>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">傳送並執行底層程式</button>
                </form>

                <?php if ($input !== ''): ?>
                    <div class="mt-4">
                        <h4>執行狀態與回應結果：</h4>
                        <?php if ($error_msg !== ''): ?>
                            <div class="alert alert-danger">
                                <?php echo $error_msg; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                執行成功！退出代碼：<code><?php echo $retval; ?></code>
                            </div>
                            <pre><?php echo htmlspecialchars($output_msg, ENT_QUOTES, 'UTF-8'); ?></pre>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-secondary">返回首頁</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
