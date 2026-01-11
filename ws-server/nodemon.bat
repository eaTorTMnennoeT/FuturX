@echo off
cd /d "%~dp0"
echo ========================================
echo   Futures WebSocket Server
echo ========================================
.\node_modules\.bin\nodemon.cmd server.js
pause
