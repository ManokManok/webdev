@echo off
echo ========================================
echo   Fixing XAMPP PHP Configuration
echo ========================================
echo.

REM Backup original php.ini
copy /y "C:\xampp\php\php.ini" "C:\xampp\php\php.ini.backup" 2>nul

REM Use our fixed php.ini for XAMPP
copy /y "C:\Users\USER\OneDrive\Desktop\CabajonWeb\ONINS\php.ini" "C:\xampp\php\php.ini"

echo Fixed XAMPP php.ini with proper SQLite support
echo.
echo Restarting Symfony server...
echo.

symfony server:stop 2>nul
taskkill /f /im php-cgi.exe 2>nul

symfony server:start -d
echo.
echo Server started at http://127.0.0.1:8000
pause
