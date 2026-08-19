<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/products.json');

        if (! File::exists($path)) {
            $this->command->warn("File products.json not found at {$path}. Skipping product seeding.");
            return;
        }

        $products = json_decode(File::get($path), true);

        if (! is_array($products)) {
            $this->command->warn('Invalid products.json format. Skipping product seeding.');
            return;
        }

        foreach ($products as $productData) {
            $categoryId = (int) ($productData['category_id'] ?? 0);

            if ($categoryId < 1) {
                $this->command->warn('Skipping product with invalid category_id: ' . ($productData['sku'] ?? 'unknown'));
                continue;
            }

            $product = Product::updateOrCreate(
                ['sku' => $productData['sku']],
                [
                    'category_id' => $categoryId,
                    'name' => $productData['name'],
                    'price' => $productData['price'],
                    'stock_quantity' => $productData['stock_quantity'] ?? 20,
                    'description' => $productData['description'] ?? null,
                    'thumbnail_url' => $productData['thumbnail_url'] ?? null,
                    'brand' => $productData['brand'] ?? null,
                    'socket_type' => $productData['socket_type'] ?? null,
                    'chipset' => $productData['chipset'] ?? null,
                    'platform' => $productData['platform'] ?? null,
                    'tier' => $productData['tier'] ?? null,
                    'tdp' => $productData['tdp'] ?? null,
                    'memory_type' => $productData['memory_type'] ?? null,
                    'memory_speed' => $productData['memory_speed'] ?? null,
                    'gpu_length_mm' => $productData['gpu_length_mm'] ?? null,
                    'max_gpu_length_mm' => $productData['max_gpu_length_mm'] ?? null,
                ]
            );

            if (! empty($productData['attributes']) && is_array($productData['attributes'])) {
                foreach ($productData['attributes'] as $attr) {
                    $attributeId = (int) ($attr['id'] ?? 0);
                    $value = $attr['value'] ?? null;

                    if ($attributeId > 0 && $value !== null) {
                        ProductAttributeValue::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'attribute_id' => $attributeId,
                            ],
                            ['value' => $value]
                        );
                    }
                }
            }
        }
    }
}
