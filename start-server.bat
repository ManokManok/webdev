@echo off
echo Starting Symfony development server with XAMPP PHP...
echo Server will be available at: http://localhost:8000
echo Press Ctrl+C to stop the server
echo.

set PHPRC=C:\Users\USER\OneDrive\Desktop\xampp\php
"C:\Users\USER\OneDrive\Desktop\xampp\php\php.exe" -S localhost:8000 -t public
