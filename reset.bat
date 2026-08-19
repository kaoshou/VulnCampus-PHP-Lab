@echo off
title VulnCampus PHP Lab - Reset Database
cls
echo ===================================================
echo   VulnCampus PHP Lab - Reset & Clean Launcher
echo ===================================================
echo.
echo WARNING: This will remove all database changes and uploaded files,
echo and reset the environment back to initial clean seed data.
echo.
set /p confirm="Are you sure you want to reset the lab? (Y/N): "
if /i "%confirm%" neq "Y" (
    echo.
    echo Operation cancelled by user.
    echo.
    pause
    exit /b 0
)

echo.
echo [1/3] Removing old volumes and containers...
docker compose down -v

echo.
echo [2/3] Building and starting clean containers...
docker compose up -d --build

echo.
echo [3/3] Waiting for MariaDB database to initialize (10s)...
timeout /t 10 /nobreak > nul

echo.
echo ===================================================
echo   VulnCampus PHP Lab has been reset successfully!
echo   Vulnerable App:  http://localhost:8080
echo   Fixed App:       http://localhost:8081
echo ===================================================
echo.
pause
