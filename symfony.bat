@echo off
REM Symfony CLI wrapper for ONINS project
REM This script proxies commands to PHP or handles them directly

setlocal EnableDelayedExpansion

REM Get the command arguments
set "SYMFONY_CMD=%~1"

if "%SYMFONY_CMD%"=="serv:start" goto :start_server
if "%SYMFONY_CMD%"=="server:start" goto :start_server
if "%SYMFONY_CMD%"=="serve:start" goto :start_server
if "%SYMFONY_CMD%"=="serve" goto :start_server

REM For other commands, use bin/console
if "%SYMFONY_CMD%"=="" goto :show_help

echo Running bin/console %*
"C:\xampp\php\php.exe" "%~dp0bin\console" %*
goto :eof

:start_server
echo ========================================
echo   ONINS Symfony Development Server
echo ========================================
echo.
echo Server: http://localhost:8000
echo Login: http://localhost:8000/login
echo Admin: http://localhost:8000/admin
echo.
echo Press Ctrl+C to stop the server
echo ========================================
echo.

REM Clear cache first
echo Clearing cache...
"C:\xampp\php\php.exe" "%~dp0bin\console" cache:clear

REM Start development server
echo Starting server...
"C:\xampp\php\php.exe" -S localhost:8000 -t "%~dp0public"
goto :eof

:show_help
echo Symfony CLI Wrapper
echo.
echo Usage: symfony [command]
echo.
echo Common commands:
echo   serv:start    Start the development server
echo   server:start  Start the development server (alias)
echo   serve:start   Start the development server (alias)
echo   serve         Start the development server (alias)
echo   [any command] Runs bin/console [command]
echo.
