@echo off
cd /d "%~dp0"
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\dev.ps1" -Action Start
if errorlevel 1 (
    echo Startup failed. See the error above and README.md.
    pause
    exit /b 1
)
