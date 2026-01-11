@echo off
title Futures WebSocket Server
cd /d "%~dp0"

echo ========================================
echo   Futures WebSocket Server
echo ========================================
echo.

:: Check if node_modules exists
if not exist "node_modules" (
    echo Installing dependencies...
    call npm install
    echo.
)

echo Starting server on port 3002...
echo Dashboard: http://localhost:3002/
echo.

node server.js

pause
