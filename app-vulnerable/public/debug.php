<?php
// 教學用弱點：調試與偵錯頁面直接公開。此頁面洩漏了系統內部路徑、PHP 環境變數、資料庫連線帳密等極度敏感的資訊

echo "<h1>VulnCampus 系統偵錯調試頁面 (debug.php)</h1>";
echo "<p class='text-danger'>⚠️ 警告：此頁面含有系統高度敏感參數，正式環境必須移除！</p>";

echo "<h2>1. 伺服器環境變數</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>2. 資料庫配置變數</h2>";
echo "DB_HOST: db<br>";
echo "DB_NAME: vuln_db<br>";
echo "DB_USER: root<br>";
echo "DB_PASS: rootpassword<br>";

echo "<h2>3. PHP 設定資訊 (phpinfo)</h2>";
phpinfo();
