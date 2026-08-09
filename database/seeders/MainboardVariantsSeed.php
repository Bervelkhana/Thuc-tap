<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class MainboardVariantsSeed extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => 'Mainboard'], ['parent_id' => null]);

        $mainboards = [
            ['socket' => 'LGA1700', 'chipset' => 'B760', 'platform' => 'intel', 'base' => 2200000],
            ['socket' => 'LGA1700', 'chipset' => 'Z790', 'platform' => 'intel', 'base' => 4200000],
            ['socket' => 'AM5', 'chipset' => 'B650', 'platform' => 'amd', 'base' => 2400000],
            ['socket' => 'AM5', 'chipset' => 'X870', 'platform' => 'amd', 'base' => 5000000],
        ];

        $brands = [
            ['name' => 'ASUS', 'series' => 'TUF', 'multiplier' => 1.12],
            ['name' => 'MSI', 'series' => 'MPG', 'multiplier' => 1.10],
            ['name' => 'Gigabyte', 'series' => 'Aorus', 'multiplier' => 1.08],
            ['name' => 'ASRock', 'series' => 'Steel', 'multiplier' => 0.95],
        ];

        $count = 0;
        foreach ($brands as $brand) {
            echo "\n📌 Seeding {$brand['name']} Mainboard variants...\n";
            foreach ($mainboards as $mb) {
                for ($i = 1; $i <= 5; $i++) {
                    $price = (int)($mb['base'] * $brand['multiplier']);
                    Product::updateOrCreate(
                        ['sku' => strtoupper(substr($brand['name'], 0, 3)) . '-MB-' . $mb['chipset'] . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)],
                        [
                            'category_id' => $category->id,
                            'brand' => $brand['name'],
                            'name' => "{$brand['name']} {$brand['series']} {$mb['chipset']} #$i",
                            'price' => $price,
                            'stock_quantity' => 10,
                            'description' => "{$brand['name']} {$mb['chipset']} Socket {$mb['socket']} Motherboard",
                            'socket_type' => $mb['socket'],
                            'chipset' => $mb['chipset'],
                            'platform' => $mb['platform'],
                            'memory_type' => 'DDR5',
                            'tdp' => $mb['chipset'] === 'Z790' || $mb['chipset'] === 'X870' ? 20 : 15,
                            'tier' => match ($mb['chipset']) {
                                'B760', 'B650' => 'entry',
                                'Z790', 'X870' => 'high',
                                default => 'mid',
                            },
                        ]
                    );
                    $count++;
                }
                echo "  ✓ {$brand['name']} {$mb['chipset']} × 5";
            }
        }
        $this->command?->info("\n✅ Seeded $count Mainboard products\n");
    }
}
