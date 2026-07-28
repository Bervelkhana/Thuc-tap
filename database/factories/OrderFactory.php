<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition()
    {
        return [
            'user_id' => function () {
                return User::factory()->create()->id;
            },
            'total_amount' => $this->faker->numberBetween(1000000, 50000000),
            'payment_method' => 'cod',
            'status' => 'pending',
        ];
    }
}
