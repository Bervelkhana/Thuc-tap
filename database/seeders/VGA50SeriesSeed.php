<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class VGA50SeriesSeed extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['name' => 'VGA'],
            ['parent_id' => null]
        );

        $models = [
            ['name' => 'NVIDIA RTX 5050', 'price' => 4500000, 'tdp' => 130, 'tier' => 'entry', 'vram' => '12 GB', 'vram_type' => 'GDDR7', 'bus_width' => '192-bit'],
            ['name' => 'NVIDIA RTX 5060', 'price' => 5500000, 'tdp' => 170, 'tier' => 'entry', 'vram' => '16 GB', 'vram_type' => 'GDDR7', 'bus_width' => '256-bit'],
            ['name' => 'NVIDIA RTX 5070', 'price' => 12000000, 'tdp' => 250, 'tier' => 'mid', 'vram' => '16 GB', 'vram_type' => 'GDDR7', 'bus_width' => '256-bit'],
            ['name' => 'NVIDIA RTX 5080', 'price' => 22000000, 'tdp' => 320, 'tier' => 'high', 'vram' => '16 GB', 'vram_type' => 'GDDR7', 'bus_width' => '384-bit'],
            ['name' => 'NVIDIA RTX 5090', 'price' => 48000000, 'tdp' => 575, 'tier' => 'ultra', 'vram' => '32 GB', 'vram_type' => 'GDDR7', 'bus_width' => '576-bit'],
        ];

        foreach ($models as $m) {
            for ($i = 1; $i <= 10; $i++) {
                Product::updateOrCreate(
                    [
                        'sku' => $this->makeSku($m['name'], $i),
                    ],
                    [
                        'category_id' => $category->id,
                        'brand' => 'NVIDIA',
                        'name' => $m['name'] . ' #' . $i,
                        'price' => $m['price'],
                        'stock_quantity' => 15,
                        'description' => $m['name'] . ' next-gen GPU, ' . $m['vram'] . ' ' . $m['vram_type'] . ', ' . $m['bus_width'],
                        'tier' => $m['tier'],
                        'tdp' => $m['tdp'],
                        'socket_type' => null,
                        'chipset' => null,
                        'platform' => 'universal',
                        'memory_type' => null,
                        'memory_speed' => null,
                    ]
                );
            }
        }

        $this->command?->info('Seeded NVIDIA RTX 50-series VGA data successfully.');
    }

    private function makeSku(string $name, int $index): string
    {
        return strtoupper(str_replace([' ', '#'], ['-', ''], $name)) . '-' . str_pad((string) $index, 2, '0', STR_PAD_LEFT);
    }
}
