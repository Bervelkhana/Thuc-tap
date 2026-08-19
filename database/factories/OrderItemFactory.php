<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition()
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'price' => $this->faker->numberBetween(500000, 30000000),
            'snapshot' => [
                'name' => $this->faker->words(3, true),
                'price' => $this->faker->numberBetween(500000, 30000000),
                'thumbnail_url' => $this->faker->imageUrl(200, 200),
            ],
        ];
    }
}
