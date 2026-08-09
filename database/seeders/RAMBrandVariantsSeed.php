<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class RAMBrandVariantsSeed extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => 'RAM'], ['parent_id' => null]);

        $ramModels = [
            ['capacity' => '16GB', 'type' => 'DDR4', 'speed' => 3200, 'base' => 1200000],
            ['capacity' => '32GB', 'type' => 'DDR4', 'speed' => 3600, 'base' => 2400000],
            ['capacity' => '32GB', 'type' => 'DDR5', 'speed' => 5600, 'base' => 3200000],
            ['capacity' => '48GB', 'type' => 'DDR5', 'speed' => 6000, 'base' => 4500000],
            ['capacity' => '64GB', 'type' => 'DDR5', 'speed' => 6400, 'base' => 8500000],
        ];

        $brands = [
            ['name' => 'Corsair', 'series' => 'Vengeance', 'multiplier' => 1.10],
            ['name' => 'G.Skill', 'series' => 'Trident Z', 'multiplier' => 1.08],
            ['name' => 'Kingston', 'series' => 'Fury', 'multiplier' => 0.95],
            ['name' => 'ADATA', 'series' => 'XPG', 'multiplier' => 0.98],
        ];

        $count = 0;
        foreach ($brands as $brand) {
            echo "\n📌 Seeding {$brand['name']} RAM variants...\n";
            foreach ($ramModels as $ram) {
                for ($i = 1; $i <= 5; $i++) {
                    $price = (int)($ram['base'] * $brand['multiplier']);
                    Product::updateOrCreate(
                        ['sku' => strtoupper(substr($brand['name'], 0, 3)) . '-RAM-' . str_replace(' ', '', $ram['type'] . $ram['capacity']) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)],
                        [
                            'category_id' => $category->id,
                            'brand' => $brand['name'],
                            'name' => "{$brand['name']} {$brand['series']} {$ram['type']} {$ram['speed']}MHz {$ram['capacity']} #$i",
                            'price' => $price,
                            'stock_quantity' => 15,
                            'description' => "{$brand['name']} {$ram['capacity']} {$ram['type']} {$ram['speed']}MHz Gaming Memory",
                            'memory_type' => $ram['type'],
                            'memory_speed' => $ram['speed'],
                            'socket_type' => $ram['type'],
                            'tdp' => $ram['type'] === 'DDR5' ? 15 : 10,
                            'tier' => $this->getTier($ram['speed'], $ram['type']),
                            'platform' => 'universal',
                        ]
                    );
                    $count++;
                }
                echo "  ✓ {$brand['name']} {$ram['type']} {$ram['speed']}MHz {$ram['capacity']} × 5";
            }
        }
        $this->command?->info("\n✅ Seeded $count RAM products\n");
    }

    private function getTier($speed, $type): string
    {
        if ($type === 'DDR4' && $speed <= 3200) return 'entry';
        if ($type === 'DDR4') return 'mid';
        if ($type === 'DDR5' && $speed <= 5600) return 'mid';
        return 'high';
    }
}
