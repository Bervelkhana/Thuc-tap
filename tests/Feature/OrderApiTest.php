<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'customer']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    /** @test */
    public function can_create_order_with_valid_data()
    {
        Sanctum::actingAs($this->user, ['*']);

        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000000,
            'stock_quantity' => 10,
        ]);

        $payload = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
            ],
            'payment_method' => 'cod',
        ];

        $response = $this->postJson('/api/orders', $payload);

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
            'status' => Order::STATUS_PENDING,
        ]);
        $this->assertEquals(8, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function cannot_create_order_with_insufficient_stock()
    {
        Sanctum::actingAs($this->user, ['*']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000000,
            'stock_quantity' => 1,
        ]);

        $payload = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                ],
            ],
            'payment_method' => 'cod',
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertUnprocessable();
        $response->assertJsonStructure([
            'status',
            'message',
        ]);
    }

    /** @test */
    public function cannot_create_order_with_missing_required_fields()
    {
        Sanctum::actingAs($this->user, ['*']);

        $payload = [
            'customer_name' => 'John Doe',
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertUnprocessable();
    }

    /** @test */
    public function customer_cannot_forge_order_for_other_user()
    {
        Sanctum::actingAs($this->user, ['*']);

        $otherUser = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 1000000,
            'stock_quantity' => 10,
        ]);

        $payload = [
            'user_id' => $otherUser->id,
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'cod',
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('user_id');
    }

    /** @test */
    public function can_retrieve_admin_orders()
    {
        Sanctum::actingAs($this->admin, ['*']);
        Order::factory(5)->create();

        $response = $this->getJson('/api/admin/orders');

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
                ],
            ],
        ]);
    }

    /** @test */
    public function can_update_order_status_with_valid_transition()
    {
        Sanctum::actingAs($this->admin, ['*']);
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => Order::STATUS_PENDING]);

        $payload = ['status' => Order::STATUS_PROCESSING];

        $response = $this->patchJson("/api/admin/orders/{$order->id}", $payload);

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_PROCESSING,
        ]);
    }

    /** @test */
    public function cannot_update_order_status_with_invalid_transition()
    {
        Sanctum::actingAs($this->admin, ['*']);
        $order = Order::factory()->create(['user_id' => $this->user->id, 'status' => Order::STATUS_DELIVERED]);

        $payload = ['status' => Order::STATUS_PENDING];

        $response = $this->patchJson("/api/admin/orders/{$order->id}", $payload);

        $response->assertUnprocessable();
    }

    /** @test */
    public function can_cancel_order_and_restore_stock()
    {
        Sanctum::actingAs($this->admin, ['*']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 10,
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => Order::STATUS_PENDING,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => $product->price,
            'snapshot' => [
                'name' => $product->name,
                'price' => $product->price,
                'thumbnail_url' => $product->thumbnail_url,
            ],
        ]);

        $product->update(['stock_quantity' => 7]);

        $response = $this->postJson("/api/admin/orders/{$order->id}/cancel");

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CANCELLED,
        ]);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function can_cancel_order_in_shipped_status()
    {
        Sanctum::actingAs($this->admin, ['*']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'stock_quantity' => 10,
        ]);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => Order::STATUS_SHIPPED,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => $product->price,
            'snapshot' => [
                'name' => $product->name,
                'price' => $product->price,
                'thumbnail_url' => $product->thumbnail_url,
            ],
        ]);

        $product->update(['stock_quantity' => 7]);

        $response = $this->postJson("/api/admin/orders/{$order->id}/cancel");

        $response->assertOk();
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => Order::STATUS_CANCELLED,
        ]);
        $this->assertEquals(10, $product->fresh()->stock_quantity);
    }

    /** @test */
    public function order_delivery_time_calculated_correctly()
    {
        Sanctum::actingAs($this->user, ['*']);
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'price' => 5000000,
            'stock_quantity' => 10,
        ]);

        $payload = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '0901234567',
            'delivery_address' => '123 Main St',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                ],
            ],
            'payment_method' => 'cod',
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertCreated();
        $data = $response->json('data');
        $this->assertNotEmpty($data['estimated_delivery']);
    }

    /** @test */
    public function customer_cannot_access_admin_order_routes()
    {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(403);
    }

    /** @test */
    public function guest_cannot_access_admin_routes()
    {
        $response = $this->getJson('/api/admin/orders');

        $response->assertStatus(401);
    }

    /** @test */
    public function admin_can_access_order_routes()
    {
        Sanctum::actingAs($this->admin, ['*']);

        $response = $this->getJson('/api/admin/orders');

        $response->assertOk();
    }
}
