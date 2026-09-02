@echo off
REM One-time setup for a NEW project cloned from this template.
REM Double-click this right after cloning. It will:
REM   1. Ask what the project is called and rename it everywhere
REM      (package.json, docker-compose.yml container/network/DB names, .env, home.php)
REM   2. Optionally wipe the template's git history and start a fresh repo
REM      (with a prompt for your new GitHub remote)
REM   3. Install npm/composer dependencies
REM   4. Build and start the Docker containers, open VS Code and the browser
REM
REM This launches the real logic in setup-new-project.ps1 with the
REM execution-policy restriction bypassed for this one run only (it does not
REM change your system's PowerShell policy). Once setup finishes successfully,
REM this .bat and its .ps1 companion DELETE THEMSELVES - from then on just use
REM start-dev.bat / stop-dev.bat like any other project.

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup-new-project.ps1"

if errorlevel 1 (
    echo.
    echo Setup did not finish, leaving these scripts in place so you can retry.
    echo.
    pause
    exit /b 1
)

echo.
echo Setup complete - cleaning up the one-time setup scripts...
del /f /q "%~dp0setup-new-project.ps1" >nul 2>&1
del /f /q "%~f0"
