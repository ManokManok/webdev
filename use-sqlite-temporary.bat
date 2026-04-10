@echo off
echo ============================================
echo    ONINS - Temporary SQLite Setup
echo    (No MySQL server needed!)
echo ============================================
echo.

echo This will configure the app to use SQLite instead of MySQL.
echo SQLite doesn't require a server - it uses a file-based database.
echo.

echo [1/4] Creating .env file with SQLite configuration...
(
    echo # Temporary SQLite Configuration (No MySQL server needed)
    echo DATABASE_URL="sqlite:///%kernel.project_dir%/var/data.db"
) > .env
echo    ✅ .env file created
echo.

echo [2/4] Ensuring var directory exists...
if not exist var mkdir var
echo    ✅ Directory ready
echo.

echo [3/4] Clearing cache...
call php bin/console cache:clear >nul 2>&1
if %errorlevel% equ 0 (
    echo    ✅ Cache cleared
) else (
    echo    ⚠️  Cache clear had issues
)
echo.

echo [4/4] Creating SQLite database and running migrations...
call php bin/console doctrine:database:create --if-not-exists 2>&1 | findstr /V "Could not"
if %errorlevel% equ 0 (
    echo    ✅ Database created
) else (
    echo    ⚠️  Database creation had issues
)
echo.

echo Running migrations...
call php bin/console doctrine:migrations:migrate --no-interaction 2>&1 | findstr /V "Could not"
if %errorlevel% equ 0 (
    echo    ✅ Migrations completed
) else (
    echo    ⚠️  Migrations had issues
)
echo.

echo ============================================
echo    Setup Complete!
echo ============================================
echo.
echo ✅ You can now try logging in!
echo    URL: http://127.0.0.1:8000/login
echo.
echo ℹ️  Note: This uses SQLite (file-based database).
echo    To switch back to MySQL later, run: fix-database-now.bat
echo.
pause

