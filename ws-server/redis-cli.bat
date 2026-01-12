@echo off
title Redis CLI - FuturX
set REDIS_PATH=C:\laragon\bin\redis\redis-x64-5.0.14.1

if not exist "%REDIS_PATH%\redis-cli.exe" (
    echo [ERROR] Redis CLI not found at %REDIS_PATH%
    pause
    exit /b 1
)

"%REDIS_PATH%\redis-cli.exe" %*
