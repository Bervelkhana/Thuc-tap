<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class VGA50SeriesBrandVariantsSeed extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'VGA'],
            ['parent_id' => null]
        );

        // RTX 50-series models
        $rtxModels = ['5050', '5060', '5070', '5080', '5090'];
        
        // Brand variants with pricing multipliers
        $brands = [
            ['name' => 'ASUS', 'series' => 'ROG Strix', 'multiplier' => 1.15],
            ['name' => 'MSI', 'series' => 'Gaming', 'multiplier' => 1.10],
            ['name' => 'Gigabyte', 'series' => 'Aorus', 'multiplier' => 1.12],
            ['name' => 'Zotac', 'series' => 'Twin Edge', 'multiplier' => 0.95],
        ];

        // Base prices for reference models
        $basePrices = [
            '5050' => 4500000,
            '5060' => 5500000,
            '5070' => 12000000,
            '5080' => 22000000,
            '5090' => 48000000,
        ];

        $baseTdp = [
            '5050' => 130,
            '5060' => 170,
            '5070' => 250,
            '5080' => 320,
            '5090' => 575,
        ];

        $tiers = [
            '5050' => 'entry',
            '5060' => 'entry',
            '5070' => 'mid',
            '5080' => 'high',
            '5090' => 'ultra',
        ];

        $count = 0;

        foreach ($brands as $brand) {
            echo "\n📌 Seeding {$brand['name']} RTX 50-series variants...\n";

            foreach ($rtxModels as $model) {
                $basePrice = $basePrices[$model];
                $adjustedPrice = (int) ($basePrice * $brand['multiplier']);
                $productName = "{$brand['name']} {$brand['series']} RTX {$model}";

                for ($i = 1; $i <= 10; $i++) {
                    Product::updateOrCreate(
                        [
                            'sku' => $this->makeSku($brand['name'], $model, $i),
                        ],
                        [
                            'category_id' => $category->id,
                            'brand' => $brand['name'],
                            'name' => $productName . ' #' . $i,
                            'price' => $adjustedPrice,
                            'stock_quantity' => 12,
                            'description' => $productName . ' - Premium gaming card with custom cooling solution, RTX ' . $model . ' architecture',
                            'tier' => $tiers[$model],
                            'tdp' => $baseTdp[$model],
                            'socket_type' => 'PCIe 5.0',
                            'chipset' => null,
                            'platform' => 'universal',
                            'memory_type' => 'GDDR7',
                            'memory_speed' => $model === '5090' ? 28000 : 24000,
                        ]
                    );
                    $count++;
                }

                $this->command?->info("  ✓ {$productName} × 10 @ " . number_format($adjustedPrice, 0, ',', '.') . " VND");
            }
        }

        $this->command?->info("\n✅ Seeded {$count} products from 4 brands (ASUS, MSI, Gigabyte, Zotac)");
        $this->command?->info("   5 RTX 50-series models × 10 units × 4 brands = 200 products\n");
    }

    private function makeSku(string $brand, string $model, int $index): string
    {
        $brandCode = strtoupper(substr($brand, 0, 3));
        return "{$brandCode}-RTX{$model}-" . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }
}
