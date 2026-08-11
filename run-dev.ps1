Start-Process php -ArgumentList 'artisan serve --host=127.0.0.1 --port=8000'
Start-Process npm -ArgumentList 'run dev -- --host 127.0.0.1 --port 5175'
Write-Host 'Started Laravel on http://127.0.0.1:8000'
Write-Host 'Started Vite on http://127.0.0.1:5175'
