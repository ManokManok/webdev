@echo off
echo ========================================
echo   ONINS - SQLITE VERSION (NO MYSQL)
echo ========================================
echo.
echo This version uses SQLite (no server needed)
echo Perfect for development and testing
echo.

cd /d "C:\Users\USER\OneDrive\Desktop\CabajonWeb\ONINS"

echo [1/3] Switching to SQLite database...
echo DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db" > .env

echo [2/3] Clearing cache...
php bin/console cache:clear --no-warmup

echo [3/3] Creating database if needed...
php bin/console doctrine:database:create --if-not-exists

echo [4/3] Running migrations...
php bin/console doctrine:migrations:migrate --no-interaction

echo.
echo ========================================
echo    SETUP COMPLETE!
echo ========================================
echo.
echo ✅ Server: http://localhost:8000
echo ✅ Login: http://localhost:8000/login
echo ✅ Admin: admin@onins.com / admin123
echo.
echo Press Ctrl+C to stop server
echo ========================================
echo.

php -S localhost:8000 -t public
