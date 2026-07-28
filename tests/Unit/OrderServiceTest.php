<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $orderService;
    protected ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = new ProductService();
        $this->orderService = new OrderService($this->productService);
    }

    /** @test */
    public function can_create_order_successfully()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 1000000, 'stock_quantity' => 10]);

        $orderData = [
            'user_id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'total_amount' => 1000000,
            'payment_method' => 'cod',
        ];

        $items = [
            ['product_id' => $product->id, 'quantity' => 2]
        ];

        // Act
        $order = $this->orderService->createOrder($orderData, $items);

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(1000000, $order->total_amount);
        $this->assertCount(1, $order->items);
        $this->assertEquals(8, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function cannot_create_order_with_insufficient_stock()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 1000000, 'stock_quantity' => 1]);

        $orderData = [
            'user_id' => 1,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'total_amount' => 2000000,
            'payment_method' => 'cod',
        ];

        $items = [
            ['product_id' => $product->id, 'quantity' => 5] // More than available
        ];

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->orderService->createOrder($orderData, $items);
    }

    /** @test */
    public function can_cancel_order_and_restore_stock()
    {
        // Arrange
        $product = Product::factory()->create(['price' => 1000000, 'stock_quantity' => 10]);
        $order = Order::factory()->create(['status' => 'pending']);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $product->update(['stock_quantity' => 7]); // After order was placed

        // Act
        $result = $this->orderService->cancelOrder($order->id);

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function cannot_cancel_order_if_already_shipped()
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'shipped']);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->orderService->cancelOrder($order->id);
    }

    /** @test */
    public function can_get_user_orders()
    {
        // Arrange
        Order::factory(5)->create(['user_id' => 1]);
        Order::factory(3)->create(['user_id' => 2]);

        // Act
        $orders = $this->orderService->getUserOrders(1);

        // Assert
        $this->assertEquals(5, $orders->total());
    }

    /** @test */
    public function can_get_user_orders_filtered_by_status()
    {
        // Arrange
        Order::factory(3)->create(['user_id' => 1, 'status' => 'pending']);
        Order::factory(2)->create(['user_id' => 1, 'status' => 'completed']);

        // Act
        $orders = $this->orderService->getUserOrders(1, ['status' => 'pending']);

        // Assert
        $this->assertEquals(3, $orders->total());
        foreach ($orders as $order) {
            $this->assertEquals('pending', $order->status);
        }
    }
}
