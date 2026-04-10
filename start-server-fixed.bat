@echo off
echo ========================================
echo   FORCING XAMPP PHP FOR SYMFONY
echo ========================================
echo.
echo Testing PHP extensions...
"C:\xampp\php\php.exe" -m | findstr pdo_mysql
if %errorlevel% neq 0 (
    echo ERROR: PDO MySQL extension not found!
    pause
    exit /b 1
)
echo ✅ PDO MySQL extension found
echo.
echo Clearing cache...
"C:\xampp\php\php.exe" bin\console cache:clear
echo.
echo Starting server at http://localhost:8000
echo Login: http://localhost:8000/login
echo Press Ctrl+C to stop
echo ========================================
echo.

REM Force use of XAMPP PHP
set PATH=C:\xampp\php;%PATH%
"C:\xampp\php\php.exe" -c "C:\Users\USER\OneDrive\Desktop\CabajonWeb\ONINS\php.ini" -S localhost:8000 -t public
