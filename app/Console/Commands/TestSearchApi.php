<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ProductController;

class TestSearchApi extends Command
{
    protected $signature = 'test:search-api';
    protected $description = 'Test the products/search API endpoint directly via controller';

    public function handle()
    {
        $controller = app(ProductController::class);

        $tests = [
            ['category_slug' => 'mainboard', 'q' => 'z'],
            ['category_slug' => 'cpu', 'q' => 'intel'],
            ['category_slug' => 'vga', 'q' => ''],
            ['category_slug' => 'nonexistent', 'q' => 'test'],
            ['category_slug' => 'ram', 'q' => 'ddr5'],
            ['category_slug' => 'psu', 'q' => ''],
            ['category_slug' => 'case', 'q' => ''],
        ];

        foreach ($tests as $i => $params) {
            echo str_repeat('=', 60) . "\n";
            echo "Test #" . ($i + 1) . ": category_slug={$params['category_slug']} q={$params['q']}\n";
            echo str_repeat('-', 60) . "\n";

            // Build a proper Laravel Request object
            $request = Request::create(
                '/api/products/search',
                'GET',
                array_filter($params, fn($v) => $v !== '')
            );

            try {
                /** @var \Illuminate\Http\JsonResponse $response */
                $response = $controller->search($request);
                $data = json_decode($response->getContent(), true);

                echo "HTTP Status: " . $response->getStatusCode() . "\n";
                echo "Status: " . ($data['status'] ?? 'N/A') . "\n";

                if (isset($data['data']) && is_array($data['data'])) {
                    echo "Results count: " . count($data['data']) . "\n";
                    foreach ($data['data'] as $product) {
                        echo sprintf("  - id=%d name='%s' price=%s\n",
                            $product['id'],
                            $product['name'],
                            number_format($product['price'], 0, ',', '.') . ' VND'
                        );
                    }
                } else {
                    echo "Message: " . ($data['message'] ?? '(no data)') . "\n";
                }
            } catch (\Exception $e) {
                echo "EXCEPTION: " . $e->getMessage() . "\n";
                echo "Stack: " . $e->getTraceAsString() . "\n";
            }

            echo "\n";
        }

        return Command::SUCCESS;
    }
}
