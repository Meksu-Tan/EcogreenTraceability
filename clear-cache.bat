@echo off
echo ========================================
echo Clearing EODS Cache
echo ========================================
echo.

cd backend

echo Clearing Laravel cache...
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo.
echo ========================================
echo Cache cleared successfully!
echo ========================================
echo.
echo You can now restart the backend server.
echo.

pause
