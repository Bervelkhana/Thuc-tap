<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'CPU' => [
                ['name' => 'Intel Core i3-12100F', 'sku' => 'CPU-I3-12100F', 'price' => 2500000, 'brand' => 'Intel', 'socket_type' => 'LGA1700', 'platform' => 'intel', 'tier' => 'entry', 'tdp' => 58, 'description' => 'Intel Core i3-12100F - 4 cores, 4 threads, up to 4.3GHz. LGA1700 socket, efficient performance for everyday computing and light gaming.'],
                ['name' => 'Intel Core i5-13400F', 'sku' => 'CPU-I5-13400F', 'price' => 5200000, 'brand' => 'Intel', 'socket_type' => 'LGA1700', 'platform' => 'intel', 'tier' => 'mid', 'tdp' => 65, 'description' => 'Intel Core i5-13400F - 10 cores (6P+4E), 16 threads, up to 4.6GHz. Excellent for gaming and productivity.'],
                ['name' => 'Intel Core i5-13600K', 'sku' => 'CPU-I5-13600K', 'price' => 6800000, 'brand' => 'Intel', 'socket_type' => 'LGA1700', 'platform' => 'intel', 'tier' => 'mid', 'tdp' => 125, 'description' => 'Intel Core i5-13600K - 14 cores (6P+8E), 20 threads, up to 5.1GHz. Unlocked multiplier for overclocking.'],
                ['name' => 'Intel Core i7-13700K', 'sku' => 'CPU-I7-13700K', 'price' => 10500000, 'brand' => 'Intel', 'socket_type' => 'LGA1700', 'platform' => 'intel', 'tier' => 'high', 'tdp' => 125, 'description' => 'Intel Core i7-13700K - 16 cores (8P+8E), 24 threads, up to 5.4GHz. High-end performance for enthusiasts.'],
                ['name' => 'Intel Core i9-13900K', 'sku' => 'CPU-I9-13900K', 'price' => 15800000, 'brand' => 'Intel', 'socket_type' => 'LGA1700', 'platform' => 'intel', 'tier' => 'ultra', 'tdp' => 125, 'description' => 'Intel Core i9-13900K - 24 cores (8P+16E), 32 threads, up to 5.8GHz. Flagship processor for extreme workloads.'],
                ['name' => 'AMD Ryzen 5 7600', 'sku' => 'AMD-R5-7600', 'price' => 4800000, 'brand' => 'AMD', 'socket_type' => 'AM5', 'platform' => 'amd', 'tier' => 'mid', 'tdp' => 65, 'description' => 'AMD Ryzen 5 7600 - 6 cores, 12 threads, up to 5.3GHz. AM5 platform, excellent gaming performance.'],
                ['name' => 'AMD Ryzen 7 7800X3D', 'sku' => 'AMD-R7-7800X3D', 'price' => 11200000, 'brand' => 'AMD', 'socket_type' => 'AM5', 'platform' => 'amd', 'tier' => 'high', 'tdp' => 120, 'description' => 'AMD Ryzen 7 7800X3D - 8 cores, 16 threads, 3D V-Cache technology. The best gaming CPU on the market.'],
                ['name' => 'AMD Ryzen 9 7900X', 'sku' => 'AMD-R9-7900X', 'price' => 13500000, 'brand' => 'AMD', 'socket_type' => 'AM5', 'platform' => 'amd', 'tier' => 'ultra', 'tdp' => 170, 'description' => 'AMD Ryzen 9 7900X - 12 cores, 24 threads, up to 5.6GHz. High-end processor for content creation and gaming.'],
                ['name' => 'AMD Ryzen 5 8400F', 'sku' => 'AMD-R5-8400F', 'price' => 3200000, 'brand' => 'AMD', 'socket_type' => 'AM5', 'platform' => 'amd', 'tier' => 'entry', 'tdp' => 65, 'description' => 'AMD Ryzen 5 8400F - 6 cores, 12 threads. Budget-friendly AM5 processor for mainstream builds.'],
            ],
            'MAIN' => [
                ['name' => 'ASUS ROG STRIX B760-A GAMING WIFI', 'sku' => 'MB-ASUS-B760A', 'price' => 5800000, 'brand' => 'ASUS', 'socket_type' => 'LGA1700', 'chipset' => 'B760', 'platform' => 'intel', 'tier' => 'mid', 'memory_type' => 'DDR5', 'description' => 'ASUS ROG STRIX B760-A - LGA1700, B760 chipset, DDR5 support, WiFi 6E, PCIe 4.0. Great for Intel mid-range builds.'],
                ['name' => 'MSI MAG B650 TOMAHAWK WIFI', 'sku' => 'MB-MSI-B650', 'price' => 5200000, 'brand' => 'MSI', 'socket_type' => 'AM5', 'chipset' => 'B650', 'platform' => 'amd', 'tier' => 'mid', 'memory_type' => 'DDR5', 'description' => 'MSI MAG B650 TOMAHAWK - AM5, B650 chipset, DDR5 support, WiFi 6E, robust VRM cooling.'],
                ['name' => 'Gigabyte B760 AORUS ELITE AX', 'sku' => 'MB-GIGA-B760', 'price' => 4800000, 'brand' => 'Gigabyte', 'socket_type' => 'LGA1700', 'chipset' => 'B760', 'platform' => 'intel', 'tier' => 'mid', 'memory_type' => 'DDR5', 'description' => 'Gigabyte B760 AORUS ELITE AX - LGA1700, B760, DDR5, WiFi 6, 2.5GbE LAN.'],
                ['name' => 'ASRock B760M Steel Legend WiFi', 'sku' => 'MB-ASR-B760M', 'price' => 3900000, 'brand' => 'ASRock', 'socket_type' => 'LGA1700', 'chipset' => 'B760', 'platform' => 'intel', 'tier' => 'entry', 'memory_type' => 'DDR5', 'description' => 'ASRock B760M Steel Legend - mATX form factor, LGA1700, B760, DDR5, WiFi. Great value for compact builds.'],
                ['name' => 'ASUS ROG STRIX Z790-E GAMING WIFI', 'sku' => 'MB-ASUS-Z790E', 'price' => 8500000, 'brand' => 'ASUS', 'socket_type' => 'LGA1700', 'chipset' => 'Z790', 'platform' => 'intel', 'tier' => 'high', 'memory_type' => 'DDR5', 'description' => 'ASUS ROG STRIX Z790-E - High-end Z790 motherboard with DDR5, PCIe 5.0, WiFi 6E, and extensive overclocking features.'],
                ['name' => 'MSI MPG X670E CARBON WIFI', 'sku' => 'MB-MSI-X670E', 'price' => 9200000, 'brand' => 'MSI', 'socket_type' => 'AM5', 'chipset' => 'X670E', 'platform' => 'amd', 'tier' => 'high', 'memory_type' => 'DDR5', 'description' => 'MSI MPG X670E CARBON - AM5, X670E chipset, DDR5, PCIe 5.0, WiFi 6E. For high-end AMD builds.'],
                ['name' => 'Gigabyte X670 AORUS ELITE AX', 'sku' => 'MB-GIGA-X670', 'price' => 7800000, 'brand' => 'Gigabyte', 'socket_type' => 'AM5', 'chipset' => 'X670', 'platform' => 'amd', 'tier' => 'mid', 'memory_type' => 'DDR5', 'description' => 'Gigabyte X670 AORUS ELITE AX - AM5, X670, DDR5, WiFi 6E. Excellent features for AMD AM5 platform.'],
            ],
            'RAM' => [
                ['name' => 'Corsair Vengeance DDR5 16GB (2x8) 5600MHz', 'sku' => 'RAM-COR-16G-D5', 'price' => 1400000, 'brand' => 'Corsair', 'memory_type' => 'DDR5', 'memory_speed' => 5600, 'description' => 'Corsair Vengeance DDR5 16GB (2x8GB) 5600MHz. High-performance memory with Intel XMP 3.0 support.'],
                ['name' => 'G.Skill Trident Z5 DDR5 32GB (2x16) 6000MHz', 'sku' => 'RAM-GSK-32G-D5', 'price' => 3200000, 'brand' => 'G.Skill', 'memory_type' => 'DDR5', 'memory_speed' => 6000, 'description' => 'G.Skill Trident Z5 RGB DDR5 32GB (2x16GB) 6000MHz CL30. Premium design with RGB lighting.'],
                ['name' => 'Kingston Fury Beast DDR4 16GB (2x8) 3200MHz', 'sku' => 'RAM-KST-16G-D4', 'price' => 850000, 'brand' => 'Kingston', 'memory_type' => 'DDR4', 'memory_speed' => 3200, 'description' => 'Kingston FURY Beast DDR4 16GB (2x8GB) 3200MHz. Reliable performance for gaming and general use.'],
                ['name' => 'Crucial Ballistix DDR5 32GB (2x16) 5200MHz', 'sku' => 'RAM-CRU-32G-D5', 'price' => 2700000, 'brand' => 'Crucial', 'memory_type' => 'DDR5', 'memory_speed' => 5200, 'description' => 'Crucial Ballistix DDR5 32GB (2x16GB) 5200MHz. Game-ready memory with on-die ECC.'],
                ['name' => 'Corsair Dominator DDR5 32GB (2x16) 6400MHz', 'sku' => 'RAM-COR-32G-D5-64', 'price' => 4500000, 'brand' => 'Corsair', 'memory_type' => 'DDR5', 'memory_speed' => 6400, 'description' => 'Corsair Dominator Platinum RGB DDR5 32GB (2x16GB) 6400MHz. Top-tier performance with iconic heatspreader design.'],
                ['name' => 'G.Skill Ripjaws DDR4 32GB (2x16) 3600MHz', 'sku' => 'RAM-GSK-32G-D4', 'price' => 1800000, 'brand' => 'G.Skill', 'memory_type' => 'DDR4', 'memory_speed' => 3600, 'description' => 'G.Skill Ripjaws V DDR4 32GB (2x16GB) 3600MHz CL18. Great value for high-capacity DDR4 builds.'],
            ],
            'SSD' => [
                ['name' => 'Samsung 980 PRO 1TB NVMe PCIe 4.0', 'sku' => 'SSD-SAM-980PRO-1T', 'price' => 2300000, 'brand' => 'Samsung', 'description' => 'Samsung 980 PRO 1TB NVMe PCIe 4.0 x4. Read up to 7000MB/s, Write up to 5000MB/s.'],
                ['name' => 'Samsung 990 PRO 2TB NVMe PCIe 4.0', 'sku' => 'SSD-SAM-990PRO-2T', 'price' => 4200000, 'brand' => 'Samsung', 'description' => 'Samsung 990 PRO 2TB NVMe PCIe 4.0 x4. Read up to 7450MB/s, Write up to 6900MB/s.'],
                ['name' => 'WD Black SN770 1TB NVMe PCIe 4.0', 'sku' => 'SSD-WD-SN770-1T', 'price' => 1600000, 'brand' => 'Western Digital', 'description' => 'WD Black SN770 1TB NVMe PCIe 4.0. Read up to 5150MB/s, Write up to 4900MB/s.'],
                ['name' => 'WD Blue SN580 1TB NVMe PCIe 4.0', 'sku' => 'SSD-WD-SN580-1T', 'price' => 1200000, 'brand' => 'Western Digital', 'description' => 'WD Blue SN580 1TB NVMe PCIe 4.0. Read/Write up to 4150MB/s. Reliable everyday SSD.'],
                ['name' => 'Crucial P3 Plus 1TB NVMe PCIe 4.0', 'sku' => 'SSD-CRU-P3P-1T', 'price' => 1400000, 'brand' => 'Crucial', 'description' => 'Crucial P3 Plus 1TB NVMe PCIe 4.0. Read up to 4700MB/s. Great value for large capacity storage.'],
                ['name' => 'Samsung 970 EVO Plus 1TB NVMe PCIe 3.0', 'sku' => 'SSD-SAM-970EP-1T', 'price' => 1800000, 'brand' => 'Samsung', 'description' => 'Samsung 970 EVO Plus 1TB NVMe PCIe 3.0 x4. Read up to 3500MB/s, Write up to 3300MB/s.'],
            ],
            'VGA' => [
                ['name' => 'ASUS Dual RTX 4060 8GB', 'sku' => 'VGA-ASUS-RTX4060', 'price' => 7800000, 'brand' => 'ASUS', 'tier' => 'entry', 'tdp' => 115, 'description' => 'ASUS Dual GeForce RTX 4060 8GB GDDR6. Compact design, excellent 1080p gaming performance with DLSS 3.'],
                ['name' => 'MSI RTX 4070 GAMING X 12GB', 'sku' => 'VGA-MSI-RTX4070', 'price' => 14500000, 'brand' => 'MSI', 'tier' => 'mid', 'tdp' => 200, 'description' => 'MSI GeForce RTX 4070 GAMING X 12GB GDDR6X. Great 1440p gaming with ray tracing and DLSS 3.'],
                ['name' => 'Gigabyte RTX 4060 Ti EAGLE 8GB', 'sku' => 'VGA-GIGA-RTX4060TI', 'price' => 9200000, 'brand' => 'Gigabyte', 'tier' => 'mid', 'tdp' => 160, 'description' => 'Gigabyte RTX 4060 Ti EAGLE 8GB GDDR6. Excellent 1080p/1440p gaming, compact dual-fan design.'],
                ['name' => 'ASRock RX 7800 XT Phantom Gaming 16GB', 'sku' => 'VGA-ASR-RX7800XT', 'price' => 13800000, 'brand' => 'ASRock', 'tier' => 'mid', 'tdp' => 263, 'description' => 'ASRock RX 7800 XT Phantom Gaming 16GB GDDR6. Great rasterization performance for 1440p gaming.'],
                ['name' => 'ASUS RTX 4080 SUPER TUF GAMING 16GB', 'sku' => 'VGA-ASUS-RTX4080S', 'price' => 24500000, 'brand' => 'ASUS', 'tier' => 'high', 'tdp' => 320, 'description' => 'ASUS RTX 4080 SUPER TUF GAMING 16GB GDDR6X. Premium build quality, excellent 4K gaming performance.'],
                ['name' => 'MSI RX 7600 MECH 2X 8GB', 'sku' => 'VGA-MSI-RX7600', 'price' => 6500000, 'brand' => 'MSI', 'tier' => 'entry', 'tdp' => 165, 'description' => 'MSI RX 7600 MECH 2X 8GB GDDR6. Budget-friendly 1080p gaming card with good efficiency.'],
                ['name' => 'Gigabyte RTX 4090 GAMING OC 24GB', 'sku' => 'VGA-GIGA-RTX4090', 'price' => 32000000, 'brand' => 'Gigabyte', 'tier' => 'ultra', 'tdp' => 450, 'description' => 'Gigabyte RTX 4090 GAMING OC 24GB GDDR6X. Ultimate gaming performance for 4K and creative workloads.'],
            ],
            'CASE' => [
                ['name' => 'NZXT H5 Flow', 'sku' => 'CASE-NZXT-H5F', 'price' => 2100000, 'brand' => 'NZXT', 'description' => 'NZXT H5 Flow - Mid Tower, ATX/mATX support, high airflow mesh front panel, tempered glass side panel.'],
                ['name' => 'Corsair 4000D Airflow', 'sku' => 'CASE-COR-4000D', 'price' => 2500000, 'brand' => 'Corsair', 'description' => 'Corsair 4000D Airflow - Mid Tower, steel mesh front panel, excellent cooling performance, cable management.'],
                ['name' => 'Lian Li Lancool 216', 'sku' => 'CASE-LIAN-L216', 'price' => 2900000, 'brand' => 'Lian Li', 'description' => 'Lian Li Lancool 216 - Mid Tower, 2x 160mm front fans, 1x 140mm rear fan, great airflow out of the box.'],
                ['name' => 'Montech X3 Mesh', 'sku' => 'CASE-MON-X3', 'price' => 900000, 'brand' => 'Montech', 'description' => 'Montech X3 Mesh - Micro-ATX/Mini-ITX, compact design, mesh front, 4x 120mm fans included.'],
                ['name' => 'NZXT H9 Elite', 'sku' => 'CASE-NZXT-H9E', 'price' => 4200000, 'brand' => 'NZXT', 'description' => 'NZXT H9 Elite - Mid Tower, dual-chamber design, 4x 140mm fans, RGB lighting, premium build quality.'],
                ['name' => 'Corsair iCUE 7000X RGB', 'sku' => 'CASE-COR-7000X', 'price' => 6800000, 'brand' => 'Corsair', 'description' => 'Corsair iCUE 7000X RGB - Full Tower, E-ATX support, 3x 140mm RGB fans, premium all-steel construction.'],
            ],
            'COOLER' => [
                ['name' => 'Noctua NH-D15S', 'sku' => 'COOL-NOC-D15S', 'price' => 2100000, 'brand' => 'Noctua', 'tier' => 'high', 'tdp' => 250, 'description' => 'Noctua NH-D15S - Premium dual-tower air cooler. 2x 140mm PWM fans, exceptional cooling performance, near-silent operation.'],
                ['name' => 'be quiet! Dark Rock Pro 4', 'sku' => 'COOL-BQ-DRP4', 'price' => 2400000, 'brand' => 'be quiet!', 'tier' => 'high', 'tdp' => 250, 'description' => 'be quiet! Dark Rock Pro 4 - High-end air cooler with 2 silent wings PWM fans. Excellent for overclocking.'],
                ['name' => 'Arctic Liquid Freezer II 280', 'sku' => 'COOL-ARC-LF2-280', 'price' => 2800000, 'brand' => 'Arctic', 'tier' => 'mid', 'tdp' => 300, 'description' => 'Arctic Liquid Freezer II 280 - 280mm AIO liquid cooler. High-performance pump, VRM fan for motherboard cooling.'],
                ['name' => 'Cooler Master MasterLiquid 360L Core', 'sku' => 'COOL-CM-ML360L', 'price' => 1900000, 'brand' => 'Cooler Master', 'tier' => 'mid', 'tdp' => 350, 'description' => 'Cooler Master MasterLiquid 360L Core - 360mm AIO, dual-chamber pump, customizable ARGB fans.'],
                ['name' => 'Deepcool AK620', 'sku' => 'COOL-DP-AK620', 'price' => 1500000, 'brand' => 'Deepcool', 'tier' => 'mid', 'tdp' => 260, 'description' => 'Deepcool AK620 - Dual-tower air cooler, 2x 120mm PWM fans, excellent price-to-performance ratio.'],
                ['name' => 'Thermalright Phantom Spirit 120', 'sku' => 'COOL-TR-PS120', 'price' => 1200000, 'brand' => 'Thermalright', 'tier' => 'entry', 'tdp' => 255, 'description' => 'Thermalright Phantom Spirit 120 - Budget-friendly dual-tower air cooler. Great value for mainstream CPUs.'],
                ['name' => 'Noctua NH-U12S redux', 'sku' => 'COOL-NOC-U12S', 'price' => 1400000, 'brand' => 'Noctua', 'tier' => 'entry', 'tdp' => 180, 'description' => 'Noctua NH-U12S redux - Single-tower air cooler, 1x 120mm fan. Compact, reliable, and near-silent.'],
            ],
        ];

        foreach ($categories as $categoryName => $products) {
            $category = \App\Models\Category::where('name', $categoryName)->first();

            if (!$category) {
                $this->command->warn("Category {$categoryName} not found. Skipping products.");
                continue;
            }

            foreach ($products as $productData) {
                Product::updateOrCreate(
                    ['sku' => $productData['sku']],
                    [
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'price' => $productData['price'],
                        'stock_quantity' => 20,
                        'discount_percentage' => rand(0, 15),
                        'description' => $productData['description'],
                        'thumbnail_url' => 'https://placehold.co/400x400/EEE/31343C?text=' . urlencode(Str::of($productData['name'])->limit(20)),
                        'brand' => $productData['brand'] ?? null,
                        'socket_type' => $productData['socket_type'] ?? null,
                        'chipset' => $productData['chipset'] ?? null,
                        'platform' => $productData['platform'] ?? null,
                        'tier' => $productData['tier'] ?? null,
                        'tdp' => $productData['tdp'] ?? null,
                        'memory_type' => $productData['memory_type'] ?? null,
                        'memory_speed' => $productData['memory_speed'] ?? null,
                    ]
                );
            }
        }
    }
}
