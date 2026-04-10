@echo off
echo ========================================
echo   ONINS Symfony Development Server
echo ========================================
echo.
echo This script starts Symfony with XAMPP PHP
echo Server: http://localhost:8000
echo Login: http://localhost:8000/login
echo Admin: http://localhost:8000/admin
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

REM Set PHP to use XAMPP configuration
set PHPRC=C:\Users\USER\OneDrive\Desktop\xampp\php

REM Clear cache first
echo Clearing cache...
"C:\Users\USER\OneDrive\Desktop\xampp\php\php.exe" bin\console cache:clear

REM Start development server
echo Starting server...
"C:\Users\USER\OneDrive\Desktop\xampp\php\php.exe" -S localhost:8000 -t public
