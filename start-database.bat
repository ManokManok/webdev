@echo off
echo Starting MySQL Database Container...
echo.
echo Make sure Docker Desktop is running first!
echo.
docker-compose up -d database
echo.
echo Waiting for database to be ready...
timeout /t 5 /nobreak >nul
docker-compose ps
echo.
echo Database should be running now. Check the status above.
pause



