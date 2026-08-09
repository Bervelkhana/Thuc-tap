<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PSUVariantsSeed extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => 'PSU'], ['parent_id' => null]);

        $psus = [
            ['wattage' => 650, 'cert' => '80+ Gold', 'base' => 1400000],
            ['wattage' => 850, 'cert' => '80+ Gold', 'base' => 2300000],
            ['wattage' => 1000, 'cert' => '80+ Platinum', 'base' => 3500000],
            ['wattage' => 1200, 'cert' => '80+ Platinum', 'base' => 4500000],
        ];

        $brands = [
            ['name' => 'Corsair', 'series' => 'RM', 'multiplier' => 1.10],
            ['name' => 'Seasonic', 'series' => 'Focus', 'multiplier' => 1.12],
            ['name' => 'EVGA', 'series' => 'SuperNOVA', 'multiplier' => 1.05],
            ['name' => 'Be Quiet', 'series' => 'Straight', 'multiplier' => 1.15],
        ];

        $count = 0;
        foreach ($brands as $brand) {
            echo "\n📌 Seeding {$brand['name']} PSU variants...\n";
            foreach ($psus as $psu) {
                for ($i = 1; $i <= 5; $i++) {
                    $price = (int)($psu['base'] * $brand['multiplier']);
                    Product::updateOrCreate(
                        ['sku' => strtoupper(substr($brand['name'], 0, 3)) . '-PSU-' . $psu['wattage'] . 'W-' . str_pad($i, 2, '0', STR_PAD_LEFT)],
                        [
                            'category_id' => $category->id,
                            'brand' => $brand['name'],
                            'name' => "{$brand['name']} {$brand['series']} {$psu['wattage']}W {$psu['cert']} #$i",
                            'price' => $price,
                            'stock_quantity' => 12,
                            'description' => "{$brand['name']} {$psu['wattage']}W Power Supply {$psu['cert']} Certified",
                            'tdp' => $psu['wattage'],
                            'socket_type' => 'ATX',
                            'tier' => $psu['wattage'] <= 650 ? 'entry' : ($psu['wattage'] <= 850 ? 'mid' : 'high'),
                            'platform' => 'universal',
                        ]
                    );
                    $count++;
                }
                echo "  ✓ {$brand['name']} {$psu['wattage']}W × 5";
            }
        }
        $this->command?->info("\n✅ Seeded $count PSU products\n");
    }
}
