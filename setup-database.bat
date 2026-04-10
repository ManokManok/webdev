@echo off
echo ========================================
echo ONINS Database Setup Script
echo ========================================
echo.

echo Step 1: Checking Docker Desktop...
docker ps >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Docker Desktop is not running!
    echo.
    echo Please:
    echo 1. Open Docker Desktop from Start Menu
    echo 2. Wait until it shows "Docker Desktop is running"
    echo 3. Run this script again
    pause
    exit /b 1
)

echo [OK] Docker Desktop is running
echo.

echo Step 2: Starting MySQL container...
docker-compose up -d database
if %errorlevel% neq 0 (
    echo [ERROR] Failed to start MySQL container
    pause
    exit /b 1
)

echo [OK] MySQL container started
echo.

echo Step 3: Waiting for MySQL to be ready (15 seconds)...
timeout /t 15 /nobreak >nul

echo Step 4: Checking container status...
docker-compose ps database
echo.

echo Step 5: Creating .env file if it doesn't exist...
if not exist .env (
    echo root=rootpassword > .env
    echo onins_db=onins_db >> .env
    echo symfony=symfony >> .env
    echo DATABASE_URL="mysql://symfony:symfony@127.0.0.1:3306/onins_db?serverVersion=8.0&charset=utf8mb4" >> .env
    echo [OK] .env file created with default values
) else (
    echo [INFO] .env file already exists
)
echo.

echo Step 6: Clearing Symfony cache...
call php bin/console cache:clear
echo.

echo Step 7: Creating database...
call php bin/console doctrine:database:create --if-not-exists
echo.

echo Step 8: Running migrations...
call php bin/console doctrine:migrations:migrate --no-interaction
echo.

echo ========================================
echo Setup Complete!
echo ========================================
echo.
echo You can now try logging in at: http://127.0.0.1:8000/login
echo.
pause



