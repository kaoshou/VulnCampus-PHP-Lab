@echo off
title VulnCampus PHP Lab - Quick Start
cls
echo ===================================================
echo   VulnCampus PHP Lab - Quick Start Launcher
echo ===================================================
echo.
echo [1/3] Starting Docker containers (Building images)...
echo.
docker rm -f vuln-db vuln-app-vulnerable vuln-app-fixed vuln-phpmyadmin > nul 2>&1
docker compose down > nul 2>&1
docker compose up -d --build

if %errorlevel% neq 0 (
    echo.
    echo [ERROR] Failed to start Docker services!
    echo Please make sure Docker Desktop is running.
    echo.
    pause
    exit /b %errorlevel%
)

echo.
echo [2/3] Waiting for MariaDB database to initialize (10s)...
timeout /t 10 /nobreak > nul

echo.
echo [3/3] VulnCampus PHP Lab is Ready!
echo ===================================================
echo   Vulnerable App (Port 8080):  http://localhost:8080
echo   Fixed App      (Port 8081):  http://localhost:8081
echo   phpMyAdmin     (Port 8082):  http://localhost:8082
echo ===================================================
echo.
echo Tip: Use OWASP ZAP "Manual Explore -> Launch Browser" to start testing.
echo.
pause
