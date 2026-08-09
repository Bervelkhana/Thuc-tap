<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class SSDVariantsSeed extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => 'SSD'], ['parent_id' => null]);

        $ssdModels = [
            ['capacity' => '500GB', 'type' => 'SATA', 'base' => 1000000],
            ['capacity' => '1TB', 'type' => 'NVMe', 'base' => 1500000],
            ['capacity' => '2TB', 'type' => 'NVMe', 'base' => 3000000],
            ['capacity' => '4TB', 'type' => 'NVMe', 'base' => 6000000],
        ];

        $brands = [
            ['name' => 'Samsung', 'series' => '970', 'multiplier' => 1.15],
            ['name' => 'WD', 'series' => 'Black', 'multiplier' => 1.10],
            ['name' => 'Crucial', 'series' => 'P5', 'multiplier' => 0.95],
            ['name' => 'SK Hynix', 'series' => 'Gold', 'multiplier' => 1.05],
        ];

        $count = 0;
        foreach ($brands as $brand) {
            echo "\n📌 Seeding {$brand['name']} SSD variants...\n";
            foreach ($ssdModels as $ssd) {
                for ($i = 1; $i <= 5; $i++) {
                    $price = (int)($ssd['base'] * $brand['multiplier']);
                    Product::updateOrCreate(
                        ['sku' => strtoupper(substr($brand['name'], 0, 3)) . '-SSD-' . str_replace('TB', 'T', $ssd['capacity']) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)],
                        [
                            'category_id' => $category->id,
                            'brand' => $brand['name'],
                            'name' => "{$brand['name']} {$brand['series']} {$ssd['capacity']} {$ssd['type']} #$i",
                            'price' => $price,
                            'stock_quantity' => 20,
                            'description' => "{$brand['name']} {$ssd['capacity']} {$ssd['type']} Storage Drive",
                            'socket_type' => $ssd['type'] === 'NVMe' ? 'M.2' : 'SATA',
                            'tdp' => $ssd['type'] === 'NVMe' ? 8 : 5,
                            'tier' => $ssd['type'] === 'NVMe' ? 'mid' : 'entry',
                            'platform' => 'universal',
                        ]
                    );
                    $count++;
                }
                echo "  ✓ {$brand['name']} {$ssd['type']} {$ssd['capacity']} × 5";
            }
        }
        $this->command?->info("\n✅ Seeded $count SSD products\n");
    }
}
