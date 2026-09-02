@echo off
REM ============================================================
REM  LavaLust Docker Template - one-click dev environment starter
REM  Double-click this file (or make a desktop shortcut to it) to:
REM    1. Make sure Docker Desktop is running
REM    2. Create .env from .env.example if missing
REM    3. Start the docker-compose stack (php, nginx, mysql, phpmyadmin, node)
REM    4. Open the project in VS Code
REM    5. Open the app in your default browser
REM ============================================================

REM Always run from the folder this .bat file lives in, no matter
REM where it's double-clicked from.
cd /d "%~dp0"

echo.
echo === LavaLust Dev Environment ===
echo.

REM --- 1. Check Docker Desktop is running ---
docker info >nul 2>&1
if errorlevel 1 (
    echo Docker does not appear to be running.
    echo Trying to start Docker Desktop...
    start "" "C:\Program Files\Docker\Docker\Docker Desktop.exe"
    echo Waiting for Docker to come up, this can take up to a minute...

    :waitfordocker
    timeout /t 5 >nul
    docker info >nul 2>&1
    if errorlevel 1 goto waitfordocker

    echo Docker is up.
) else (
    echo Docker is already running.
)

REM --- 2. Create .env if it doesn't exist yet ---
if not exist ".env" (
    if exist ".env.example" (
        copy ".env.example" ".env" >nul
        echo Created .env from .env.example
    )
)

REM --- 3. Start the containers ---
echo.
echo Starting containers...
docker-compose up -d

echo.
echo Waiting a few seconds for services to settle...
timeout /t 8 >nul

docker-compose ps

REM --- 4. Open the project in VS Code ---
where code >nul 2>&1
if errorlevel 1 (
    echo VS Code CLI "code" not found on PATH - skipping editor launch.
    echo ^(In VS Code: Ctrl+Shift+P -^> "Shell Command: Install 'code' command in PATH"^)
) else (
    echo Opening project in VS Code...
    code .
)

REM --- 5. Open the app in the browser ---
echo Opening http://localhost in your browser...
start "" "http://localhost"

echo.
echo === Ready ===
echo   App:        http://localhost
echo   phpMyAdmin: http://localhost:8080
echo   Stop with:  docker-compose down
echo.
pause
