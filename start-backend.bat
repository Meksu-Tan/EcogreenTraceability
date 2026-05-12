@echo off
echo ========================================
echo Starting EODS Backend (Laravel API)
echo ========================================
echo.

cd backend

echo Clearing cache...
call php artisan config:clear
call php artisan cache:clear
call php artisan route:clear

echo.
echo ========================================
echo Starting server at http://127.0.0.1:8000
echo.
echo IMPORTANT: Use 127.0.0.1 NOT localhost
echo Frontend should access: http://127.0.0.1:8000
echo ========================================
echo.
echo Press Ctrl+C to stop
echo.

php artisan serve --host=127.0.0.1 --port=8000
