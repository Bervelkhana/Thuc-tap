<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateProductSalesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Cập nhật 6 sản phẩm đầu tiên với dữ liệu sale
        Product::limit(6)->get()->each(function ($product) {
            $product->update([
                'is_on_sale' => true,
                'discount_percentage' => rand(10, 40),
                'sale_price' => $product->price * (1 - rand(10, 40) / 100),
            ]);
        });
    }
}
