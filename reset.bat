@echo off
chcp 65001 > nul
echo ===================================================
echo   VulnCampus PHP Lab - 靶場一鍵還原/重置腳本
echo ===================================================
echo.
echo 注意：此操作將徹底清除所有資料庫修改與上傳檔案，
echo 並回復至最初乾淨的種子資料狀態。
echo.
set /p confirm="確認要重置靶場嗎？ (Y/N): "
if /i "%confirm%" neq "Y" (
    echo 操作已取消。
    pause
    exit /b 0
)

echo.
echo [1/3] 正在銷毀現有磁碟卷與容器...
docker compose down -v

echo.
echo [2/3] 正在重新建置並啟動乾淨容器...
docker compose up -d --build

echo.
echo [3/3] 等待資料庫重新初始化 (約需 10 秒)...
timeout /t 10 /nobreak > nul

echo.
echo ===================================================
echo  靶場已成功還原為初始狀態！
echo  弱點版網站:  http://localhost:8080
echo  修正版網站:  http://localhost:8081
echo ===================================================
echo.
pause
