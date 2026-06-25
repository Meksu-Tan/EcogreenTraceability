@echo off
echo Starting all EODS servers...

echo Starting Reference Backend (Port 8001)...
start "Reference Backend (Port 8001)" cmd /k "set PHP_CLI_SERVER_WORKERS=4 && cd /d %~dp0\reference-dont-change && php artisan serve --port=8001"

echo Starting Main Backend (Port 8000)...
start "Main Backend (Port 8000)" cmd /k "set PHP_CLI_SERVER_WORKERS=4 && cd /d %~dp0\backend && php artisan serve --port=8000"

echo Starting Frontend (Port 5173)...
start "Frontend Vite (Port 5173)" cmd /k "cd /d %~dp0\frontend && npm run dev"

echo All servers are starting in separate windows.
echo You can close this window now.
pause
