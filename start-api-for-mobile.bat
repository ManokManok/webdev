@echo off
cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { $r = Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/login' -Method POST -ContentType 'application/json' -Body '{\"email\":\"customer@onins.com\",\"password\":\"customer123\"}' -TimeoutSec 8; if ($r.status -eq 'success') { exit 0 } } catch { }; exit 1" >nul 2>&1
if not errorlevel 1 (
  echo ONINS API is already running on port 8000.
  echo   http://127.0.0.1:8000
  powershell -NoProfile -File "%~dp0..\appdevv1\scripts\get-lan-ip.ps1"
  exit /b 0
)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0..\appdevv1\scripts\start-api.ps1"
if errorlevel 1 exit /b 1
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0..\appdevv1\scripts\ensure-api.ps1" -WaitSeconds 45
exit /b %ERRORLEVEL%
