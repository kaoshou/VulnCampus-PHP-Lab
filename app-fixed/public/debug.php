<?php
// 修補重點：將調試 debug 頁面徹底關閉，或直接對外部返回 404 Not Found，防止洩露環境變數與原始碼

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>404 Not Found</title>
</head>
<body style="font-family: sans-serif; text-align: center; padding-top: 100px;">
    <h1>404 Not Found</h1>
    <p>找不到您要求的頁面。</p>
    <a href="/index.php">回首頁</a>
</body>
</html>
