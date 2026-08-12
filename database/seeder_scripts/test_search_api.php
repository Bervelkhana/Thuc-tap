<?php

use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Testing /api/products/search directly via Laravel HTTP Client:\n\n";

$tests = [
    ['category_slug' => 'mainboard', 'q' => 'z', 'per_page' => 5],
    ['category_slug' => 'cpu', 'q' => 'intel', 'per_page' => 5],
    ['category_slug' => 'vga', 'q' => '', 'per_page' => 5],
    ['category_slug' => 'nonexistent', 'q' => 'test', 'per_page' => 5],
];

foreach ($tests as $i => $params) {
    echo "=== Test #" . ($i + 1) . " ===\n";
    echo "Params: " . json_encode($params) . "\n";
    
    try {
        // Use Laravel's internal HTTP client for testing (bypasses Apache routing)
        $response = Http::get(route('products.search') ?? '/api/products/search', $params);
        
        echo "HTTP Status: " . $response->status() . "\n";
        $body = $response->json();
        echo "Response: " . json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Also check Laravel log
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    echo "=== Recent Log Entries ===\n";
    $lines = file($logFile);
    $searchLogs = array_filter($lines, fn($l) => stripos($l, '[Search]') !== false);
    foreach (array_slice(array_reverse($searchLogs), -10) as $line) {
        echo trim($line) . "\n";
    }
    if (empty($searchLogs)) {
        echo "(No search logs found in application log)\n";
    }
}
