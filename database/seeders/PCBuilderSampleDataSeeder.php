<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class PCBuilderSampleDataSeeder extends Seeder
{
    /**
     * Seed sample PC components for testing compatibility validator
     */
    public function run(): void
    {
        // Ensure categories exist
        $categories = [
            'CPU' => 'Central Processing Unit',
            'Mainboard' => 'Motherboard',
            'RAM' => 'Memory',
            'VGA' => 'Graphics Card',
            'SSD' => 'Storage Drive',
            'PSU' => 'Power Supply',
            'Case' => 'Computer Case',
            'Cooler' => 'Cooler',
        ];

        $categoryMap = [];
        foreach ($categories as $name => $description) {
            $categoryMap[$name] = Category::firstOrCreate(
                ['name' => $name],
                ['parent_id' => null]
            );
        }

        // ===== INTEL SETUP =====
        echo "\n📌 Seeding Intel CPU samples...\n";
        $intelCpus = [
            ['name' => 'Core i3-13100', 'price' => 2500000, 'tier' => 'entry', 'tdp' => 65],
            ['name' => 'Core i5-13600K', 'price' => 6000000, 'tier' => 'mid', 'tdp' => 125],
            ['name' => 'Core i7-13700K', 'price' => 10000000, 'tier' => 'high', 'tdp' => 165],
            ['name' => 'Core i9-13900K', 'price' => 15000000, 'tier' => 'ultra', 'tdp' => 253],
        ];

        foreach ($intelCpus as $cpu) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($cpu['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['CPU']->id,
                    'name' => $cpu['name'],
                    'brand' => 'Intel',
                    'price' => $cpu['price'],
                    'stock_quantity' => 10,
                    'socket_type' => 'LGA1700',
                    'platform' => 'intel',
                    'tier' => $cpu['tier'],
                    'tdp' => $cpu['tdp'],
                    'description' => "Intel {$cpu['name']} Processor",
                ]
            );
        }

        echo "📌 Seeding Intel Mainboard samples...\n";
        $intelMainboards = [
            ['name' => 'ASUS TUF GAMING H610', 'price' => 1800000, 'tier' => 'entry', 'chipset' => 'H610'],
            ['name' => 'MSI MPG B760 EDGE WIFI', 'price' => 2500000, 'tier' => 'mid', 'chipset' => 'B760'],
            ['name' => 'ASUS ROG STRIX Z790-E', 'price' => 4000000, 'tier' => 'high', 'chipset' => 'Z790'],
        ];

        foreach ($intelMainboards as $mb) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($mb['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['Mainboard']->id,
                    'name' => $mb['name'],
                    'brand' => 'ASUS',
                    'price' => $mb['price'],
                    'stock_quantity' => 8,
                    'socket_type' => 'LGA1700',
                    'chipset' => $mb['chipset'],
                    'platform' => 'intel',
                    'tier' => $mb['tier'],
                    'memory_type' => 'DDR5',
                    'description' => "{$mb['name']} Socket LGA1700",
                ]
            );
        }

        // ===== AMD SETUP =====
        echo "📌 Seeding AMD CPU samples...\n";
        $amdCpus = [
            ['name' => 'Ryzen 5 7500', 'price' => 2800000, 'tier' => 'entry', 'tdp' => 65],
            ['name' => 'Ryzen 7 7700X', 'price' => 7000000, 'tier' => 'mid', 'tdp' => 105],
            ['name' => 'Ryzen 9 7900X', 'price' => 10500000, 'tier' => 'high', 'tdp' => 162],
            ['name' => 'Ryzen 9 7950X', 'price' => 16000000, 'tier' => 'ultra', 'tdp' => 162],
        ];

        foreach ($amdCpus as $cpu) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($cpu['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['CPU']->id,
                    'name' => $cpu['name'],
                    'brand' => 'AMD',
                    'price' => $cpu['price'],
                    'stock_quantity' => 10,
                    'socket_type' => 'AM5',
                    'platform' => 'amd',
                    'tier' => $cpu['tier'],
                    'tdp' => $cpu['tdp'],
                    'description' => "AMD {$cpu['name']} Processor",
                ]
            );
        }

        echo "📌 Seeding AMD Mainboard samples...\n";
        $amdMainboards = [
            ['name' => 'ASUS TUF GAMING A620', 'price' => 1600000, 'tier' => 'entry', 'chipset' => 'A620'],
            ['name' => 'MSI MPG B650 EDGE WIFI', 'price' => 2800000, 'tier' => 'mid', 'chipset' => 'B650'],
            ['name' => 'ASUS ROG STRIX X870', 'price' => 4500000, 'tier' => 'high', 'chipset' => 'X870'],
        ];

        foreach ($amdMainboards as $mb) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($mb['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['Mainboard']->id,
                    'name' => $mb['name'],
                    'brand' => 'ASUS',
                    'price' => $mb['price'],
                    'stock_quantity' => 8,
                    'socket_type' => 'AM5',
                    'chipset' => $mb['chipset'],
                    'platform' => 'amd',
                    'tier' => $mb['tier'],
                    'memory_type' => 'DDR5',
                    'description' => "{$mb['name']} Socket AM5",
                ]
            );
        }

        // ===== RAM =====
        echo "📌 Seeding DDR5 RAM samples...\n";
        $ddr5Rams = [
            ['name' => 'Corsair Vengeance RGB Pro 6000 MHz 32GB', 'price' => 3200000, 'speed' => 6000],
            ['name' => 'G.Skill Trident Z5 5600 MHz 32GB', 'price' => 2800000, 'speed' => 5600],
            ['name' => 'Kingston FURY Beast 5200 MHz 32GB', 'price' => 2400000, 'speed' => 5200],
        ];

        foreach ($ddr5Rams as $ram) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($ram['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['RAM']->id,
                    'name' => $ram['name'],
                    'brand' => 'Corsair',
                    'price' => $ram['price'],
                    'stock_quantity' => 15,
                    'memory_type' => 'DDR5',
                    'memory_speed' => $ram['speed'],
                    'tdp' => 15,
                    'description' => "{$ram['name']} RAM",
                ]
            );
        }

        // ===== GPU =====
        echo "📌 Seeding GPU samples...\n";
        $gpus = [
            ['name' => 'NVIDIA RTX 4060', 'price' => 3500000, 'tier' => 'entry', 'tdp' => 150, 'length_mm' => 272],
            ['name' => 'NVIDIA RTX 4070', 'price' => 8000000, 'tier' => 'mid', 'tdp' => 250, 'length_mm' => 261],
            ['name' => 'NVIDIA RTX 4090', 'price' => 25000000, 'tier' => 'ultra', 'tdp' => 450, 'length_mm' => 337],
            ['name' => 'AMD Radeon RX 7700 XT', 'price' => 7500000, 'tier' => 'mid', 'tdp' => 250, 'length_mm' => 302],
        ];

        foreach ($gpus as $gpu) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($gpu['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['VGA']->id,
                    'name' => $gpu['name'],
                    'brand' => str_contains($gpu['name'], 'NVIDIA') ? 'NVIDIA' : 'AMD',
                    'price' => $gpu['price'],
                    'stock_quantity' => 5,
                    'tier' => $gpu['tier'],
                    'tdp' => $gpu['tdp'],
                    'gpu_length_mm' => $gpu['length_mm'],
                    'description' => "{$gpu['name']} Graphics Card",
                ]
            );
        }

        // ===== PSU =====
        echo "📌 Seeding PSU samples...\n";
        $psus = [
            ['name' => 'Corsair RM650x 650W 80+ Gold', 'price' => 2000000, 'wattage' => 650],
            ['name' => 'Seasonic Focus GX-850 850W 80+ Gold', 'price' => 2800000, 'wattage' => 850],
            ['name' => 'EVGA SuperNOVA 1000 GT 1000W 80+ Gold', 'price' => 3800000, 'wattage' => 1000],
        ];

        foreach ($psus as $psu) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($psu['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['PSU']->id,
                    'name' => $psu['name'],
                    'brand' => 'Corsair',
                    'price' => $psu['price'],
                    'stock_quantity' => 10,
                    'tdp' => $psu['wattage'],
                    'description' => "{$psu['name']} Power Supply",
                ]
            );
        }

        // ===== CASE =====
        echo "📌 Seeding Case samples...\n";
        $cases = [
            ['name' => 'NZXT H510 Flow', 'price' => 1500000, 'max_gpu_length_mm' => 381],
            ['name' => 'Corsair Crystal 570X RGB', 'price' => 3000000, 'max_gpu_length_mm' => 300],
            ['name' => 'Lian Li Lancool 3', 'price' => 2000000, 'max_gpu_length_mm' => 420],
        ];

        foreach ($cases as $case) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($case['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['Case']->id,
                    'name' => $case['name'],
                    'price' => $case['price'],
                    'stock_quantity' => 12,
                    'max_gpu_length_mm' => $case['max_gpu_length_mm'],
                    'description' => "{$case['name']} Computer Case",
                ]
            );
        }

        // ===== COOLER =====
        echo "📌 Seeding Cooler samples...\n";
        $coolers = [
            ['name' => 'Noctua NH-D15S', 'price' => 1890000, 'tier' => 'high', 'socket_type' => 'LGA1700, AM5, AM4'],
            ['name' => 'be quiet! Dark Rock Pro 4', 'price' => 2190000, 'tier' => 'high', 'socket_type' => 'LGA1700, AM5, AM4'],
            ['name' => 'Arctic Liquid Freezer II 280', 'price' => 2490000, 'tier' => 'mid', 'socket_type' => 'LGA1700, AM5, AM4'],
            ['name' => 'Cooler Master MasterLiquid 360L Core', 'price' => 1890000, 'tier' => 'mid', 'socket_type' => 'LGA1700, AM5'],
            ['name' => 'Deepcool AK620', 'price' => 1690000, 'tier' => 'mid', 'socket_type' => 'LGA1700, AM5, AM4'],
            ['name' => 'Thermalright Phantom Spirit 120', 'price' => 1490000, 'tier' => 'mid', 'socket_type' => 'LGA1700, AM5, AM4'],
            ['name' => 'Noctua NH-U12S redux', 'price' => 1290000, 'tier' => 'entry', 'socket_type' => 'LGA1700, AM5, AM4'],
        ];

        foreach ($coolers as $cooler) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($cooler['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['Cooler']->id,
                    'name' => $cooler['name'],
                    'price' => $cooler['price'],
                    'stock_quantity' => 10,
                    'tier' => $cooler['tier'],
                    'socket_type' => $cooler['socket_type'],
                    'tdp' => 15,
                    'description' => "{$cooler['name']} CPU Cooler - Hỗ trợ: {$cooler['socket_type']}",
                ]
            );
        }

        // ===== SSD =====
        echo "📌 Seeding SSD samples...\n";
        $ssds = [
            ['name' => 'Samsung 870 QVO 1TB', 'price' => 1200000],
            ['name' => 'WD Blue SN570 1TB', 'price' => 1300000],
            ['name' => 'Crucial P5 Plus 1TB', 'price' => 1500000],
        ];

        foreach ($ssds as $ssd) {
            Product::updateOrCreate(
                ['sku' => strtoupper(substr($ssd['name'], 0, 5) . rand(1000, 9999))],
                [
                    'category_id' => $categoryMap['SSD']->id,
                    'name' => $ssd['name'],
                    'price' => $ssd['price'],
                    'stock_quantity' => 20,
                    'description' => "{$ssd['name']} SSD Storage",
                ]
            );
        }

        echo "\n✅ PC Builder sample data seeded successfully!\n";
        echo "Sample configurations:\n";
        echo "1. Intel i7-13700K + TUF H610 + DDR5 RAM + RTX 4070 (Mixed tier)\n";
        echo "2. AMD Ryzen 9 7900X + ROG STRIX X870 + DDR5 RAM + RTX 4090 (High-end)\n";
        echo "3. Intel i3-13100 + MSI B760 + DDR5 RAM + RTX 4060 (Budget)\n\n";
    }
}
