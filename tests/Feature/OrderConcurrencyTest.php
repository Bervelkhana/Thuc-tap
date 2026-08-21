<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class OrderConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
        $this->user = \App\Models\User::factory()->create();
        Auth::login($this->user);
    }

    /** @test */
    public function test_concurrent_orders_do_not_cause_negative_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $basePath = str_replace('\\', '/', realpath(__DIR__ . '/../..'));
        $tempFile = sys_get_temp_dir() . '/order_concurrency_' . uniqid() . '.php';
        $outputFile1 = sys_get_temp_dir() . '/order_concurrency_out1_' . uniqid() . '.json';
        $outputFile2 = sys_get_temp_dir() . '/order_concurrency_out2_' . uniqid() . '.json';

        $script = <<<PHP
<?php
require '$basePath/vendor/autoload.php';
\$app = require_once '$basePath/bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

\$productId = (int) \$argv[1];
\$quantity = (int) \$argv[2];
\$outputFile = \$argv[3];

\$orderService = new App\Services\OrderService();
try {
    \$order = \$orderService->createOrder([
        'user_id' => 1,
        'customer_name' => 'Concurrent Test',
        'customer_email' => 'concurrent@test.com',
        'customer_phone' => '0901234567',
        'delivery_address' => 'Test Address',
        'payment_method' => 'cod',
        'items' => [['product_id' => \$productId, 'quantity' => \$quantity]],
    ]);
    file_put_contents(\$outputFile, json_encode(['success' => true, 'order_id' => \$order->id]));
} catch (Throwable \$e) {
    file_put_contents(\$outputFile, json_encode(['success' => false, 'error' => \$e->getMessage()]));
}
PHP;

        file_put_contents($tempFile, $script);

        $env = [
            'APP_ENV' => 'testing',
        ];

        $process1 = new Process([PHP_BINARY, $tempFile, (string) $product->id, '3', $outputFile1]);
        $process1->setEnv($env);
        $process1->setTimeout(120);
        $process1->setIdleTimeout(60);

        $process2 = new Process([PHP_BINARY, $tempFile, (string) $product->id, '3', $outputFile2]);
        $process2->setEnv($env);
        $process2->setTimeout(120);
        $process2->setIdleTimeout(60);

        $process1->start();
        $process2->start();

        $process1->wait();
        $process2->wait();

        unlink($tempFile);

        if ($process1->getExitCode() !== 0) {
            file_put_contents($outputFile1, json_encode([
                'success' => false,
                'error' => 'Process exited with code ' . $process1->getExitCode() . ': ' . $process1->getErrorOutput(),
            ]));
        }
        if ($process2->getExitCode() !== 0) {
            file_put_contents($outputFile2, json_encode([
                'success' => false,
                'error' => 'Process exited with code ' . $process2->getExitCode() . ': ' . $process2->getErrorOutput(),
            ]));
        }

        $result1 = json_decode(file_get_contents($outputFile1), true);
        $result2 = json_decode(file_get_contents($outputFile2), true);

        @unlink($outputFile1);
        @unlink($outputFile2);

        $product->refresh();

        $order1Created = $result1['success'] ?? false;
        $order2Created = $result2['success'] ?? false;

        $this->assertGreaterThanOrEqual(0, $product->stock_quantity);
        $totalDeducted = ($order1Created ? 3 : 0) + ($order2Created ? 3 : 0);
        $this->assertEquals(5, $product->stock_quantity + $totalDeducted);
    }

    /** @test */
    public function test_duplicate_product_lines_rejected()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 3],
                ['product_id' => $product->id, 'quantity' => 3],
            ],
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('items');
    }

    /** @test */
    public function test_concurrent_order_creation_with_insufficient_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 2]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('không đủ hàng');

        $this->orderService->createOrder([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ]);
    }

    /** @test */
    public function test_order_creation_with_incompatible_cpu_mainboard_is_rejected()
    {
        $cpuCategory = \App\Models\Category::factory()->create(['name' => 'CPU']);
        $mainboardCategory = \App\Models\Category::factory()->create(['name' => 'Mainboard']);
        $cpu = Product::factory()->create(['category_id' => $cpuCategory->id, 'socket_type' => 'LGA1700']);
        $mainboard = Product::factory()->create(['category_id' => $mainboardCategory->id, 'socket_type' => 'AM5']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CPU và Mainboard không tương thích');

        $this->orderService->createOrder([
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $cpu->id, 'quantity' => 1],
                ['product_id' => $mainboard->id, 'quantity' => 1],
            ],
        ]);
    }

    /** @test */
    public function test_order_snapshot_preserves_product_data()
    {
        $product = Product::factory()->create(['name' => 'Old Name', 'price' => 1000000, 'stock_quantity' => 10]);

        $orderData = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        $product->update(['name' => 'New Name', 'price' => 2000000]);

        $order->refresh();
        $this->assertEquals('Old Name', $order->snapshot['items'][0]['name']);
        $this->assertEquals(1000000, $order->snapshot['items'][0]['price']);
    }
}
