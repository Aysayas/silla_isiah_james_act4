@echo off
REM Stops the LavaLust docker-compose stack started by start-dev.bat.
cd /d "%~dp0"

echo Stopping containers...
docker-compose down

echo.
echo Done. (Your database data is kept - it's in a Docker volume.)
echo To wipe the database too, run: docker-compose down -v
echo.
pause
