<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
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

        $categoryMap = [
            1 => 'CPU',
            2 => 'RAM',
            3 => 'MAIN',
            4 => 'SSD',
            5 => 'VGA',
            6 => 'CASE',
            7 => 'COOLER',
        ];

        $categories = Category::all()->keyBy('name');

        $attributeIdMap = [
            1 => 'Socket',
            2 => 'TDP',
            3 => 'RAM Type',
            4 => 'Wattage',
            5 => 'RGB',
            6 => 'Release Date',
            7 => 'Specifications',
        ];

        $attributes = Attribute::all()->keyBy('name');

        foreach ($products as $productData) {
            $rawCategoryId = (int) ($productData['category_id'] ?? 0);
            $categoryName = $categoryMap[$rawCategoryId] ?? null;
            $category = $categoryName ? ($categories[$categoryName] ?? null) : null;

            if (! $category) {
                $this->command->warn("Skipping product with missing category_id={$rawCategoryId}: " . ($productData['sku'] ?? 'unknown'));
                continue;
            }

            $product = Product::updateOrCreate(
                ['sku' => $productData['sku']],
                [
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'price' => $productData['price'],
                    'stock_quantity' => $productData['stock_quantity'] ?? 20,
                    'description' => $productData['description'] ?? null,
                    'thumbnail_url' => $productData['thumbnail_url'] ?? '🛍️',
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
                    $rawAttributeId = (int) ($attr['id'] ?? 0);
                    $attributeName = $attributeIdMap[$rawAttributeId] ?? null;
                    $attribute = $attributeName ? ($attributes[$attributeName] ?? null) : null;

                    if ($attribute && ($value = $attr['value'] ?? null) !== null) {
                        ProductAttributeValue::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'attribute_id' => $attribute->id,
                            ],
                            ['value' => $value]
                        );
                    }
                }
            }
        }
    }
}
