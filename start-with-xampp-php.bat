@echo off
echo ========================================
echo   FORCING XAMPP PHP - FINAL FIX
echo ========================================
echo.

REM Kill any existing PHP server on port 8000
for /f "tokens=1-5" %%a in ('netstat -ano ^| findstr :8000') do (
    for /f "tokens=1,2" %%b in ("%%a") do (
        taskkill /F /PID %%b 2>nul
    )
)

REM Force XAMPP PHP to be first in PATH
set "OLD_PATH=%PATH%"
set "PATH=C:\Users\USER\OneDrive\Desktop\xampp\php;%OLD_PATH%"

echo Testing XAMPP PHP extensions...
"C:\Users\USER\OneDrive\Desktop\xampp\php\php.exe" -m | findstr "pdo_mysql" >nul
if %errorlevel% neq 0 (
    echo ❌ ERROR: PDO MySQL extension not found in XAMPP PHP!
    echo.
    echo XAMPP PHP location: C:\Users\USER\OneDrive\Desktop\xampp\php\php.exe
    echo.
    pause
    exit /b 1
)

echo ✅ PDO MySQL extension found in XAMPP PHP
echo.

echo Clearing cache with XAMPP PHP...
"C:\Users\USER\OneDrive\Desktop\xampp\php\php.exe" bin\console cache:clear --no-warmup

echo.
echo Starting server with XAMPP PHP...
echo Server: http://localhost:8000
echo Login: http://localhost:8000/login
echo.
echo Press Ctrl+C to stop server
echo ========================================
echo.

REM Start server with forced XAMPP PHP
"C:\Users\USER\OneDrive\Desktop\xampp\php\php.exe" -S localhost:8000 -t public
