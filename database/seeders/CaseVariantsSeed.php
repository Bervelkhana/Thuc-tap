<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CaseVariantsSeed extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(['name' => 'Case'], ['parent_id' => null]);

        $cases = [
            ['form' => 'Mini Tower', 'glass' => false, 'base' => 900000],
            ['form' => 'Mid Tower', 'glass' => true, 'base' => 1500000],
            ['form' => 'Full Tower', 'glass' => true, 'base' => 2500000],
            ['form' => 'Micro ATX', 'glass' => false, 'base' => 1200000],
        ];

        $brands = [
            ['name' => 'Corsair', 'series' => 'Carbide', 'multiplier' => 1.12],
            ['name' => 'NZXT', 'series' => 'H510', 'multiplier' => 1.10],
            ['name' => 'Lian Li', 'series' => 'Lancool', 'multiplier' => 0.95],
            ['name' => 'Fractal Design', 'series' => 'North', 'multiplier' => 1.08],
        ];

        $count = 0;
        foreach ($brands as $brand) {
            echo "\n📌 Seeding {$brand['name']} Case variants...\n";
            foreach ($cases as $case) {
                for ($i = 1; $i <= 5; $i++) {
                    $price = (int)($case['base'] * $brand['multiplier']);
                    Product::updateOrCreate(
                        ['sku' => strtoupper(substr($brand['name'], 0, 3)) . '-CASE-' . str_replace(' ', '', $case['form']) . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)],
                        [
                            'category_id' => $category->id,
                            'brand' => $brand['name'],
                            'name' => "{$brand['name']} {$brand['series']} {$case['form']}" . ($case['glass'] ? ' Tempered Glass' : '') . " #$i",
                            'price' => $price,
                            'stock_quantity' => 8,
                            'description' => "{$brand['name']} {$case['form']} PC Case" . ($case['glass'] ? ' with Tempered Glass Panel' : ''),
                            'socket_type' => $case['form'],
                            'tdp' => 0,
                            'tier' => $case['form'] === 'Full Tower' ? 'high' : 'mid',
                            'platform' => 'universal',
                        ]
                    );
                    $count++;
                }
                echo "  ✓ {$brand['name']} {$case['form']} × 5";
            }
        }
        $this->command?->info("\n✅ Seeded $count Case products\n");
    }
}
