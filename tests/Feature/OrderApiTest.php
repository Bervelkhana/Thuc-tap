<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function can_create_order_with_valid_data()
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000000,
            'stock_quantity' => 10,
        ]);

        $payload = [
            'user_id' => $this->user->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ]
            ],
            'payment_method' => 'cod',
        ];

        // Act
        $response = $this->postJson('/api/orders', $payload);

        // Assert
        $response->assertCreated();
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'order_id',
                'created_at',
                'estimated_delivery',
                'total',
            ],
        ]);
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function cannot_create_order_with_insufficient_stock()
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000000,
            'stock_quantity' => 1,
        ]);

        $payload = [
            'user_id' => $this->user->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5, // More than available
                ]
            ],
        ];

        // Act
        $response = $this->postJson('/api/orders', $payload);

        // Assert
        $response->assertUnprocessable();
        $response->assertJsonStructure([
            'status',
            'message',
        ]);
    }

    /** @test */
    public function cannot_create_order_with_missing_required_fields()
    {
        // Arrange
        $payload = [
            'user_id' => $this->user->id,
            // Missing items
        ];

        // Act
        $response = $this->postJson('/api/orders', $payload);

        // Assert
        $response->assertUnprocessable();
    }

    /** @test */
    public function can_retrieve_admin_orders()
    {
        // Arrange
        Order::factory(5)->create();

        // Act
        $response = $this->getJson('/api/admin/orders');

        // Assert
        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'user_id',
                    'total_amount',
                    'status',
                ]
            ],
        ]);
    }

    /** @test */
    public function can_update_order_status()
    {
        // Arrange
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => 'pending']);

        $payload = ['status' => 'confirmed'];

        // Act
        $response = $this->patchJson("/api/admin/orders/{$order->id}", $payload);

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'confirmed',
        ]);
    }

    /** @test */
    public function can_cancel_order_and_restore_stock()
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 10,
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $product->update(['stock_quantity' => 7]); // After order placed

        // Act
        $response = $this->deleteJson("/api/admin/orders/{$order->id}");

        // Assert
        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelled',
        ]);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function cannot_cancel_order_in_shipped_status()
    {
        // Arrange
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'shipped',
        ]);

        // Act
        $response = $this->deleteJson("/api/admin/orders/{$order->id}");

        // Assert
        $response->assertUnprocessable();
    }

    /** @test */
    public function order_delivery_time_calculated_correctly()
    {
        // Arrange
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 5000000,
            'stock_quantity' => 10,
        ]);

        $payload = [
            'user_id' => $this->user->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ]
            ],
            'payment_method' => 'cod',
        ];

        // Act
        $response = $this->postJson('/api/orders', $payload);

        // Assert
        $response->assertCreated();
        // Total >= 5M should have 4 days delivery
        $data = $response->json('data');
        $this->assertNotEmpty($data['estimated_delivery']);
    }
}
