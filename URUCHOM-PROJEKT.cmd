@echo off
setlocal
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\start-project.ps1"
if errorlevel 1 (
  echo.
  echo Projekt nie wystartowal poprawnie.
  pause
  exit /b %errorlevel%
)
echo.
echo Projekt jest uruchomiony.
pause
