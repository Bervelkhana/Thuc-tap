<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = new OrderService();
    }

    /** @test */
    public function can_create_order_successfully()
    {
        $product = Product::factory()->create(['price' => 1000000, 'stock_quantity' => 10]);

        $orderData = [
            'user_id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(Order::STATUS_PENDING, $order->status);
        $this->assertEquals(2000000, $order->total_amount);
        $this->assertCount(1, $order->items);
        $this->assertEquals(8, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->snapshot);
        $this->assertCount(1, $order->snapshot['items']);
    }

    /** @test */
    public function cannot_create_order_with_insufficient_stock()
    {
        $product = Product::factory()->create(['price' => 1000000, 'stock_quantity' => 1]);

        $orderData = [
            'user_id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('không đủ hàng');
        $this->orderService->createOrder($orderData);
    }

    /** @test */
    public function can_cancel_order_and_restore_stock()
    {
        $product = Product::factory()->create(['price' => 1000000, 'stock_quantity' => 10]);
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $product->update(['stock_quantity' => 7]);

        $result = $this->orderService->cancelOrder($order->id);

        $this->assertTrue($result);
        $this->assertEquals(Order::STATUS_CANCELLED, $order->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->fresh()->cancelled_at);
    }

    /** @test */
    public function cannot_cancel_order_if_already_shipped()
    {
        $order = Order::factory()->create(['status' => Order::STATUS_SHIPPED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Không thể hủy order');
        $this->orderService->cancelOrder($order->id);
    }

    /** @test */
    public function order_state_transitions_are_valid()
    {
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $this->assertTrue($order->canTransitionTo(Order::STATUS_PROCESSING));
        $this->assertTrue($order->canTransitionTo(Order::STATUS_CANCELLED));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_DELIVERED));

        $order->update(['status' => Order::STATUS_PROCESSING]);
        $this->assertTrue($order->canTransitionTo(Order::STATUS_SHIPPED));
        $this->assertTrue($order->canTransitionTo(Order::STATUS_CANCELLED));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_PENDING));

        $order->update(['status' => Order::STATUS_SHIPPED]);
        $this->assertTrue($order->canTransitionTo(Order::STATUS_DELIVERED));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_PENDING));

        $order->update(['status' => Order::STATUS_DELIVERED]);
        $this->assertFalse($order->canTransitionTo(Order::STATUS_CANCELLED));
        $this->assertFalse($order->canTransitionTo(Order::STATUS_PENDING));
    }

    /** @test */
    public function order_snapshot_is_saved_on_creation()
    {
        $product = Product::factory()->create(['price' => 1000000, 'stock_quantity' => 10]);

        $orderData = [
            'user_id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'payment_method' => 'cod',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        $this->assertNotNull($order->snapshot);
        $this->assertEquals(2000000, $order->snapshot['total']);
        $this->assertCount(1, $order->snapshot['items']);
        $this->assertEquals($product->name, $order->snapshot['items'][0]['name']);
    }

    /** @test */
    public function order_snapshot_preserves_product_data_after_update()
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
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ];

        $order = $this->orderService->createOrder($orderData);

        $product->update(['name' => 'New Name', 'price' => 2000000]);

        $order->refresh();
        $this->assertEquals('Old Name', $order->snapshot['items'][0]['name']);
        $this->assertEquals(1000000, $order->snapshot['items'][0]['price']);
    }

    /** @test */
    public function test_order_rollback_on_failure()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $initialStock = $product->stock_quantity;

        DB::beginTransaction();
        try {
            $order = Order::create(['user_id' => 1, 'status' => Order::STATUS_PENDING]);
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 3,
                'price' => $product->price,
            ]);
            $product->decrement('stock_quantity', 3);

            throw new \Exception('Simulated failure');
        } catch (\Exception $e) {
            DB::rollBack();
        }

        $product->refresh();
        $this->assertEquals($initialStock, $product->stock_quantity);
    }

    /** @test */
    public function cannot_create_order_with_incompatible_cpu_mainboard()
    {
        $cpuCategory = \App\Models\Category::factory()->create(['name' => 'CPU']);
        $mainboardCategory = \App\Models\Category::factory()->create(['name' => 'Mainboard']);
        $cpu = Product::factory()->create(['category_id' => $cpuCategory->id, 'socket_type' => 'LGA1700']);
        $mainboard = Product::factory()->create(['category_id' => $mainboardCategory->id, 'socket_type' => 'AM5']);

        $orderData = [
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
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('CPU và Mainboard không tương thích');
        $this->orderService->createOrder($orderData);
    }

    /** @test */
    public function can_get_user_orders()
    {
        Order::factory(5)->create(['user_id' => 1]);
        Order::factory(3)->create(['user_id' => 2]);

        $orders = $this->orderService->getUserOrders(1);

        $this->assertEquals(5, $orders->total());
    }

    /** @test */
    public function can_get_user_orders_filtered_by_status()
    {
        Order::factory(3)->create(['user_id' => 1, 'status' => Order::STATUS_PENDING]);
        Order::factory(2)->create(['user_id' => 1, 'status' => Order::STATUS_DELIVERED]);

        $orders = $this->orderService->getUserOrders(1, ['status' => Order::STATUS_PENDING]);

        $this->assertEquals(3, $orders->total());
        foreach ($orders as $order) {
            $this->assertEquals(Order::STATUS_PENDING, $order->status);
        }
    }
}
