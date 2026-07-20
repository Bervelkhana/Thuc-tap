<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'category_id' => function () {
                return Category::inRandomOrder()->value('id') ?? Category::factory()->create()->id;
            },
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'price' => $this->faker->numberBetween(500000, 30000000),
            'stock_quantity' => $this->faker->numberBetween(0, 50),
            'description' => $this->faker->sentence(),
            'thumbnail_url' => null,
            'datasheet_pdf_url' => null,
        ];
    }
}
