<?php
require_once __DIR__ . '/../src/helpers.php';
check_login();

$input = $_GET['input'] ?? '';
$output_msg = '';
$error_msg = '';

if ($input !== '') {
    // 使用 escapeshellarg 避免命令注入 (Command Injection)，只允許傳送字串作為單一參數
    $safe_input = escapeshellarg($input);
    $cmd = "/usr/local/bin/vuln-process " . $safe_input;
    
    $output = [];
    $retval = 0;
    exec($cmd, $output, $retval);
    
    if ($retval === 139) {
        // SIGSEGV 崩潰的退出代碼一般為 139 (128 + 11)
        http_response_code(500);
        $error_msg = "🚨 [系統崩潰] 伺服器核心拋出 Exception：Segmentation fault (Core Dumped)！記憶體溢漏，溢位範圍已覆寫 Return Address。";
    } else {
        $output_msg = implode("\n", $output);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>底層緩衝區溢位 (Buffer Overflow) 演練 - 弱點版</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .card { box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 10px; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        pre { background-color: #272822; color: #f8f8f2; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card p-4">
                <h2 class="text-danger mb-4">💥 緩衝區溢位 (Buffer Overflow) 綜合演練</h2>
                <p class="text-muted">本功能會呼叫系統底層使用 C 語言編寫的二進位程式。此程式配置了 <code>char buffer[64]</code>，但使用不安全的 <code>strcpy</code> 來處理您傳入的參數。</p>
                <hr>
                
                <form method="GET" action="">
                    <div class="form-group">
                        <label for="input">請輸入字串傳遞給底層 C 程式：</label>
                        <input type="text" class="form-control" id="input" name="input" value="<?php echo htmlspecialchars($input, ENT_QUOTES, 'UTF-8'); ?>" placeholder="請輸入 64 字元內的字串..." required>
                        <small class="form-text text-muted">提示：輸入長度若小於 64 個字元，系統將正常處理。若輸入超過 64 個字元，將覆寫記憶體堆疊（Stack）。</small>
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
