<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    /** @test */
    public function test_concurrent_orders_do_not_cause_negative_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $result1 = null;
        $result2 = null;

        DB::transaction(function () use ($product, &$result1) {
            $orderData = [
                'user_id' => 1,
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'customer_phone' => '0901234567',
                'delivery_address' => '123 Main St',
                'payment_method' => 'cod',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ];

            try {
                $result1 = $this->orderService->createOrder($orderData);
            } catch (\Exception $e) {
                $result1 = $e->getMessage();
            }
        });

        DB::transaction(function () use ($product, &$result2) {
            $orderData = [
                'user_id' => 2,
                'customer_name' => 'Jane Doe',
                'customer_email' => 'jane@example.com',
                'customer_phone' => '0901234568',
                'delivery_address' => '456 Main St',
                'payment_method' => 'cod',
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
            ];

            try {
                $result2 = $this->orderService->createOrder($orderData);
            } catch (\Exception $e) {
                $result2 = $e->getMessage();
            }
        });

        $product->refresh();
        $this->assertGreaterThanOrEqual(0, $product->stock_quantity);
        $this->assertEquals(5, $product->stock_quantity + ($result1 instanceof Order ? 3 : 0) + ($result2 instanceof Order ? 3 : 0));
    }

    /** @test */
    public function test_concurrent_order_creation_with_insufficient_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 2]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('không đủ hàng');

        $orderService = new OrderService();
        $orderService->createOrder([
            'user_id' => 1,
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

        $orderService = new OrderService();
        $orderService->createOrder([
            'user_id' => 1,
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
            'user_id' => 1,
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
