@echo off
cd /d "%~dp0"
echo Luntian HR — http://127.0.0.1:8000
echo Press Ctrl+C to stop.
php artisan serve --host=127.0.0.1 --port=8000
