@echo off
chcp 65001 > nul
echo ===================================================
echo   VulnCampus PHP Lab - 靶場環境一鍵啟動腳本
echo ===================================================
echo.
echo [1/3] 正在啟動 Docker 容器並建置服務...
docker compose up -d --build

if %errorlevel% neq 0 (
    echo.
    echo [錯誤] 啟動失敗！請確認 Docker Desktop 是否已開啟。
    pause
    exit /b %errorlevel%
)

echo.
echo [2/3] 正在等待 MariaDB 資料庫初始化 (約需 10 秒)...
timeout /t 10 /nobreak > nul

echo.
echo [3/3] 靶場環境已就緒！
echo ===================================================
echo  弱點版網站 (Vulnerable):  http://localhost:8080
echo  安全修正版 (Fixed):       http://localhost:8081
echo  資料庫管理 (phpMyAdmin):  http://localhost:8082
echo ===================================================
echo 提示：請優先使用 OWASP ZAP 的 "Launch Browser" 開啟網址。
echo.
pause
