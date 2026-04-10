@echo off
chcp 65001 >nul
echo ============================================
echo    ONINS Database Connection Fix
echo ============================================
echo.

REM Check if Docker is running
echo [1/5] Checking Docker Desktop...
docker ps >nul 2>&1
if %errorlevel% neq 0 (
    echo    ❌ Docker Desktop is NOT running!
    echo.
    echo    ⚠️  ACTION REQUIRED:
    echo    1. Open Docker Desktop from Start Menu
    echo    2. Wait for it to fully start (whale icon in system tray)
    echo    3. Run this script again
    echo.
    pause
    exit /b 1
)
echo    ✅ Docker Desktop is running
echo.

REM Start MySQL container
echo [2/5] Starting MySQL container...
docker-compose up -d database 2>&1 | findstr /V "warning"
if %errorlevel% neq 0 (
    echo    ❌ Failed to start MySQL container
    echo    Check Docker Desktop is fully started
    pause
    exit /b 1
)
echo    ✅ MySQL container started
echo.

REM Wait for MySQL to be ready
echo [3/5] Waiting for MySQL to initialize (20 seconds)...
timeout /t 20 /nobreak >nul
echo    ✅ Wait complete
echo.

REM Check container status
echo [4/5] Checking container status...
docker-compose ps database
echo.

REM Create/Update .env file
echo [5/5] Configuring database connection...
if not exist .env (
    (
        echo # Database Configuration
        echo root=rootpassword
        echo onins_db=onins_db
        echo symfony=symfony
        echo DATABASE_URL="mysql://symfony:symfony@127.0.0.1:3306/onins_db?serverVersion=8.0&charset=utf8mb4"
    ) > .env
    echo    ✅ Created .env file
) else (
    echo    ℹ️  .env file already exists
    echo    Make sure it contains DATABASE_URL
)
echo.

REM Clear cache
echo Clearing Symfony cache...
call php bin/console cache:clear >nul 2>&1
if %errorlevel% equ 0 (
    echo    ✅ Cache cleared
) else (
    echo    ⚠️  Cache clear had issues (may need database first)
)
echo.

REM Create database
echo Creating database...
call php bin/console doctrine:database:create --if-not-exists 2>&1 | findstr /V "Could not"
if %errorlevel% equ 0 (
    echo    ✅ Database created/verified
) else (
    echo    ⚠️  Database creation had issues
)
echo.

REM Run migrations
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
echo ✅ Next steps:
echo    1. Try logging in at: http://127.0.0.1:8000/login
echo    2. After login, you'll be redirected to admin dashboard
echo.
echo If you still see errors, check:
echo    - Docker Desktop is running (whale icon in system tray)
echo    - MySQL container is running: docker ps
echo    - .env file has correct DATABASE_URL
echo.
pause



