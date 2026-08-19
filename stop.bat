@echo off
title VulnCampus PHP Lab - Stop Services
cls
echo ===================================================
echo   VulnCampus PHP Lab - Stopping Services
echo ===================================================
echo.
echo Stopping Docker containers (Keeping database data)...
echo.
docker compose down

echo.
echo All containers stopped successfully!
echo.
pause
