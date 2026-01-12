@echo off
title Redis Server - FuturX
echo ========================================
echo   FuturX Redis Server
echo ========================================
echo.

set REDIS_PATH=C:\laragon\bin\redis\redis-x64-5.0.14.1

if not exist "%REDIS_PATH%\redis-server.exe" (
    echo [ERROR] Redis not found at %REDIS_PATH%
    echo Please install Redis or update the path.
    pause
    exit /b 1
)

echo [INFO] Starting Redis Server...
echo [INFO] Path: %REDIS_PATH%
echo [INFO] Port: 6379
echo.
echo Press Ctrl+C to stop the server.
echo ========================================
echo.

"%REDIS_PATH%\redis-server.exe"

pause
