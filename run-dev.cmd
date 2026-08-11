@echo off
setlocal

start "Laravel" cmd /c "php artisan serve --host=127.0.0.1 --port=8000"
start "Vite" cmd /c "npm run dev -- --host 127.0.0.1 --port 5175"

echo Started Laravel on http://127.0.0.1:8000
echo Started Vite on http://127.0.0.1:5175
