<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'category_id' => function () {
                return Category::inRandomOrder()->value('id') ?? Category::factory()->create()->id;
            },
            'sku' => strtoupper(Str::random(8)),
            'name' => $name,
            'price' => $this->faker->numberBetween(500000, 30000000),
            'stock_quantity' => $this->faker->numberBetween(5, 50),
            'description' => $this->faker->sentence(),
            'thumbnail_url' => null,
            'datasheet_pdf_url' => null,
            'brand' => $this->faker->randomElement(['Intel', 'AMD', 'NVIDIA', 'Corsair', 'Kingston', 'G.Skill', 'Seagate', 'Samsung', 'ASUS', 'MSI']),
            'socket_type' => $this->faker->randomElement([null, 'LGA1700', 'AM5', 'DDR4', 'DDR5', 'PCIe 4.0']),
            'chipset' => $this->faker->randomElement([null, 'H610', 'B760', 'Z790', 'A620', 'B650', 'X670']),
            'platform' => $this->faker->randomElement(['intel', 'amd', 'universal', 'other']),
            'tier' => $this->faker->randomElement(['entry', 'mid', 'high', 'ultra']),
            'tdp' => $this->faker->numberBetween(35, 450),
            'memory_type' => $this->faker->randomElement([null, 'DDR4', 'DDR5']),
            'memory_speed' => $this->faker->randomElement([null, 3200, 3600, 5200, 5600, 6000]),
        ];
    }

    /**
     * State: Intel CPU
     */
    public function intelCpu()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Core i3-13100', 'Core i5-13600K', 'Core i7-13700K', 'Core i9-13900K']),
                'brand' => 'Intel',
                'socket_type' => $this->faker->randomElement(['LGA1700']),
                'platform' => 'intel',
                'tier' => $this->faker->randomElement(['entry', 'mid', 'high', 'ultra']),
                'tdp' => $this->faker->numberBetween(65, 253),
                'price' => $this->faker->numberBetween(2000000, 15000000),
            ];
        });
    }

    /**
     * State: AMD CPU
     */
    public function amdCpu()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => $this->faker->randomElement(['Ryzen 5 7500', 'Ryzen 7 7700X', 'Ryzen 9 7950X']),
                'brand' => 'AMD',
                'socket_type' => 'AM5',
                'platform' => 'amd',
                'tier' => $this->faker->randomElement(['entry', 'mid', 'high', 'ultra']),
                'tdp' => $this->faker->numberBetween(65, 162),
                'price' => $this->faker->numberBetween(2000000, 15000000),
            ];
        });
    }

    /**
     * State: Intel Mainboard
     */
    public function intelMainboard()
    {
        return $this->state(function (array $attributes) {
            $chipset = $this->faker->randomElement(['H610', 'B760', 'Z790']);
            return [
                'name' => "ASUS TUF GAMING {$chipset}",
                'brand' => 'ASUS',
                'socket_type' => 'LGA1700',
                'chipset' => $chipset,
                'platform' => 'intel',
                'tier' => $this->tierFromChipset($chipset),
                'memory_type' => 'DDR5',
                'price' => $this->faker->numberBetween(1500000, 4000000),
            ];
        });
    }

    /**
     * State: AMD Mainboard
     */
    public function amdMainboard()
    {
        return $this->state(function (array $attributes) {
            $chipset = $this->faker->randomElement(['A620', 'B650', 'X670']);
            return [
                'name' => "MSI MPG B{$chipset}-EDGE WIFI",
                'brand' => 'MSI',
                'socket_type' => 'AM5',
                'chipset' => $chipset,
                'platform' => 'amd',
                'tier' => $this->tierFromChipset($chipset),
                'memory_type' => 'DDR5',
                'price' => $this->faker->numberBetween(1500000, 4000000),
            ];
        });
    }

    /**
     * State: DDR4 RAM
     */
    public function ddr4Ram()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => "Corsair Vengeance {$this->faker->randomElement(['3200 MHz', '3600 MHz'])} 16GB",
                'brand' => 'Corsair',
                'memory_type' => 'DDR4',
                'memory_speed' => $this->faker->randomElement([3200, 3600]),
                'tdp' => 10,
                'price' => $this->faker->numberBetween(800000, 2000000),
            ];
        });
    }

    /**
     * State: DDR5 RAM
     */
    public function ddr5Ram()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => "G.Skill Trident Z5 {$this->faker->randomElement(['5200 MHz', '5600 MHz', '6000 MHz'])} 32GB",
                'brand' => 'G.Skill',
                'memory_type' => 'DDR5',
                'memory_speed' => $this->faker->randomElement([5200, 5600, 6000]),
                'tdp' => 15,
                'price' => $this->faker->numberBetween(2000000, 4000000),
            ];
        });
    }

    /**
     * State: GPU
     */
    public function gpu()
    {
        return $this->state(function (array $attributes) {
            $model = $this->faker->randomElement(['RTX 4060', 'RTX 4070', 'RTX 4090', 'RX 7700 XT', 'RX 7900 XT']);
            return [
                'name' => $model,
                'brand' => str_contains($model, 'RTX') ? 'NVIDIA' : 'AMD',
                'tier' => $this->tierFromGpu($model),
                'tdp' => $this->tdpFromGpu($model),
                'price' => $this->priceFromGpu($model),
            ];
        });
    }

    /**
     * State: PSU
     */
    public function psu()
    {
        return $this->state(function (array $attributes) {
            $wattage = $this->faker->randomElement([650, 850, 1000, 1200]);
            return [
                'name' => "Corsair RM{$wattage}x {$wattage}W 80+ Gold",
                'brand' => 'Corsair',
                'tdp' => $wattage,
                'price' => $this->priceFromPsu($wattage),
            ];
        });
    }

    private function tierFromChipset(string $chipset): string
    {
        return match ($chipset) {
            'H610', 'A620' => 'entry',
            'B760', 'B650' => 'mid',
            'Z790', 'X670' => 'high',
            default => 'mid',
        };
    }

    private function tierFromGpu(string $model): string
    {
        return match (true) {
            str_contains($model, '4060') || str_contains($model, '7600') => 'entry',
            str_contains($model, '4070') || str_contains($model, '7700') => 'mid',
            str_contains($model, '4080') || str_contains($model, '7800') => 'high',
            str_contains($model, '4090') || str_contains($model, '7900') => 'ultra',
            default => 'mid',
        };
    }

    private function tdpFromGpu(string $model): int
    {
        return match (true) {
            str_contains($model, '4060') || str_contains($model, '7600') => 150,
            str_contains($model, '4070') || str_contains($model, '7700') => 250,
            str_contains($model, '4080') || str_contains($model, '7800') => 320,
            str_contains($model, '4090') || str_contains($model, '7900') => 450,
            default => 200,
        };
    }

    private function priceFromGpu(string $model): int
    {
        return match (true) {
            str_contains($model, '4060') || str_contains($model, '7600') => 3000000,
            str_contains($model, '4070') || str_contains($model, '7700') => 7000000,
            str_contains($model, '4080') || str_contains($model, '7800') => 12000000,
            str_contains($model, '4090') || str_contains($model, '7900') => 25000000,
            default => 5000000,
        };
    }

    private function priceFromPsu(int $wattage): int
    {
        return match ($wattage) {
            650 => 2000000,
            850 => 2500000,
            1000 => 3000000,
            1200 => 4000000,
            default => 2500000,
        };
    }
}

