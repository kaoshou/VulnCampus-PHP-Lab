@echo off
chcp 65001 > nul
echo ===================================================
echo   VulnCampus PHP Lab - 停止靶場環境
echo ===================================================
echo.
echo 正在停止並保留現有資料...
docker compose down

echo.
echo 靶場所有容器已安全停止！
echo.
pause
