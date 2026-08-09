<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HardwareProductsSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = $this->catalog();

        foreach ($catalog as $categoryName => $config) {
            $category = Category::firstOrCreate(
                ['name' => $categoryName],
                ['parent_id' => null]
            );

            $attributeIds = [];
            foreach ($config['attributes'] as $attributeName) {
                $attribute = Attribute::firstOrCreate(['name' => $attributeName]);
                $attributeIds[] = $attribute->id;
            }
            $category->attributes()->sync($attributeIds);

            foreach ($config['products'] as $productData) {
                $sku = Str::upper(Str::slug($productData['brand'] . '-' . $productData['name']));
                $description = $this->buildDescription($productData);

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'price' => $productData['price'],
                        'stock_quantity' => rand(5, 40),
                        'description' => $description,
                        'thumbnail_url' => null,
                        'datasheet_pdf_url' => null,
                    ]
                );

                $pivot = [];
                foreach ($productData['specs'] as $attributeName => $value) {
                    $attribute = Attribute::firstOrCreate(['name' => $attributeName]);
                    $pivot[$attribute->id] = ['value' => $this->stringifyValue($value)];
                }

                if (!empty($pivot)) {
                    $product->attributes()->sync($pivot);
                }
            }
        }
    }

    private function catalog(): array
    {
        return [
            'CPU' => [
                'attributes' => ['Socket', 'Số nhân', 'Số luồng', 'Xung nhịp gốc', 'Xung nhịp boost', 'Cache', 'TDP', 'iGPU', 'Tiến trình'],
                'products' => [
                    ['name' => 'Intel Core i3-10100F', 'brand' => 'Intel', 'price' => 1800000, 'release_year' => 2020, 'specs' => ['Socket' => 'LGA1200', 'Số nhân' => 4, 'Số luồng' => 8, 'Xung nhịp gốc' => '3.6 GHz', 'Xung nhịp boost' => '4.3 GHz', 'Cache' => '6 MB', 'TDP' => '65W', 'iGPU' => false, 'Tiến trình' => '14 nm']],
                    ['name' => 'Intel Core i3-12100F', 'brand' => 'Intel', 'price' => 2200000, 'release_year' => 2022, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 4, 'Số luồng' => 8, 'Xung nhịp gốc' => '3.3 GHz', 'Xung nhịp boost' => '4.3 GHz', 'Cache' => '12 MB', 'TDP' => '58W', 'iGPU' => false, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i5-12400F', 'brand' => 'Intel', 'price' => 3400000, 'release_year' => 2022, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 6, 'Số luồng' => 12, 'Xung nhịp gốc' => '2.5 GHz', 'Xung nhịp boost' => '4.4 GHz', 'Cache' => '18 MB', 'TDP' => '65W', 'iGPU' => false, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i5-13400F', 'brand' => 'Intel', 'price' => 4300000, 'release_year' => 2023, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 10, 'Số luồng' => 16, 'Xung nhịp gốc' => '2.5 GHz', 'Xung nhịp boost' => '4.6 GHz', 'Cache' => '20 MB', 'TDP' => '65W', 'iGPU' => false, 'Tiến trình' => '10 nm']],
                    ['name' => 'AMD Ryzen 5 5600', 'brand' => 'AMD', 'price' => 3200000, 'release_year' => 2022, 'specs' => ['Socket' => 'AM4', 'Số nhân' => 6, 'Số luồng' => 12, 'Xung nhịp gốc' => '3.5 GHz', 'Xung nhịp boost' => '4.4 GHz', 'Cache' => '35 MB', 'TDP' => '65W', 'iGPU' => false, 'Tiến trình' => '7 nm']],
                    ['name' => 'AMD Ryzen 5 7500F', 'brand' => 'AMD', 'price' => 4200000, 'release_year' => 2023, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 6, 'Số luồng' => 12, 'Xung nhịp gốc' => '3.7 GHz', 'Xung nhịp boost' => '5.0 GHz', 'Cache' => '32 MB', 'TDP' => '65W', 'iGPU' => false, 'Tiến trình' => '5 nm']],
                    ['name' => 'AMD Ryzen 7 7800X3D', 'brand' => 'AMD', 'price' => 9800000, 'release_year' => 2023, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 8, 'Số luồng' => 16, 'Xung nhịp gốc' => '4.2 GHz', 'Xung nhịp boost' => '5.0 GHz', 'Cache' => '104 MB', 'TDP' => '120W', 'iGPU' => true, 'Tiến trình' => '5 nm']],
                    ['name' => 'AMD Ryzen 9 7950X', 'brand' => 'AMD', 'price' => 14500000, 'release_year' => 2022, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 16, 'Số luồng' => 32, 'Xung nhịp gốc' => '4.5 GHz', 'Xung nhịp boost' => '5.7 GHz', 'Cache' => '80 MB', 'TDP' => '170W', 'iGPU' => true, 'Tiến trình' => '5 nm']],
                ],
            ],
            'Mainboard' => [
                'attributes' => ['Socket', 'Chipset', 'Chuẩn', 'RAM hỗ trợ', 'Khe M.2', 'PCIe x16', 'SATA', 'LAN', 'Wi-Fi', 'Audio'],
                'products' => [
                    ['name' => 'ASUS Prime H310M-K', 'brand' => 'ASUS', 'price' => 1250000, 'release_year' => 2018, 'specs' => ['Socket' => 'LGA1151 v2', 'Chipset' => 'Intel H310', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 1, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC887']],
                    ['name' => 'Gigabyte H410M H V2', 'brand' => 'Gigabyte', 'price' => 1450000, 'release_year' => 2020, 'specs' => ['Socket' => 'LGA1200', 'Chipset' => 'Intel H410', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 1, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC887']],
                    ['name' => 'MSI B560M PRO-VDH', 'brand' => 'MSI', 'price' => 2300000, 'release_year' => 2021, 'specs' => ['Socket' => 'LGA1200', 'Chipset' => 'Intel B560', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'MSI MAG B660M MORTAR WIFI DDR4', 'brand' => 'MSI', 'price' => 4200000, 'release_year' => 2022, 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel B660', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi‑Fi 6', 'Audio' => 'Realtek ALC1200']],
                    ['name' => 'MSI PRO Z790-P WIFI DDR4', 'brand' => 'MSI', 'price' => 5800000, 'release_year' => 2022, 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel Z790', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 4, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi‑Fi 6E', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'ASUS Prime A620M-K', 'brand' => 'ASUS', 'price' => 2200000, 'release_year' => 2023, 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD A620', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'MSI PRO B650M-A WIFI', 'brand' => 'MSI', 'price' => 4300000, 'release_year' => 2023, 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD B650', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi‑Fi 6E', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'ASUS ROG STRIX B650E-F GAMING WIFI', 'brand' => 'ASUS', 'price' => 8500000, 'release_year' => 2022, 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD B650E', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 4, 'PCIe x16' => 2, 'SATA' => 4, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi‑Fi 6E', 'Audio' => 'ROG SupremeFX ALC4080']],
                ],
            ],
            'RAM' => [
                'attributes' => ['Loại', 'Dung lượng', 'Số module', 'Tốc độ', 'CAS Latency', 'Điện áp', 'Form factor', 'ECC', 'RGB'],
                'products' => [
                    ['name' => 'Kingston FURY Beast 8GB DDR4 3200MHz', 'brand' => 'Kingston', 'price' => 450000, 'release_year' => 2020, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '8GB', 'Số module' => 1, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Corsair Vengeance LPX 16GB (2x8GB) DDR4 3600MHz', 'brand' => 'Corsair', 'price' => 950000, 'release_year' => 2020, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '16GB', 'Số module' => 2, 'Tốc độ' => '3600 MHz', 'CAS Latency' => 'CL18', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Corsair Vengeance RGB Pro 32GB (2x16GB) DDR4 3200MHz', 'brand' => 'Corsair', 'price' => 1650000, 'release_year' => 2020, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                    ['name' => 'G.Skill Trident Z5 RGB 32GB (2x16GB) DDR5 6000MHz', 'brand' => 'G.Skill', 'price' => 2950000, 'release_year' => 2022, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '6000 MHz', 'CAS Latency' => 'CL30', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                    ['name' => 'Corsair Vengeance RGB 64GB (2x32GB) DDR5 5600MHz', 'brand' => 'Corsair', 'price' => 5400000, 'release_year' => 2023, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '64GB', 'Số module' => 2, 'Tốc độ' => '5600 MHz', 'CAS Latency' => 'CL40', 'Điện áp' => '1.25V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                ],
            ],
            'VGA' => [
                'attributes' => ['GPU', 'VRAM', 'Memory type', 'Bus memory', 'Base clock', 'Boost clock', 'Ray tracing', 'Power consumption', 'Recommended PSU'],
                'products' => [
                    ['name' => 'NVIDIA GeForce GTX 1650 4GB', 'brand' => 'NVIDIA', 'price' => 3200000, 'release_year' => 2019, 'specs' => ['GPU' => 'Turing', 'VRAM' => '4GB', 'Memory type' => 'GDDR5', 'Bus memory' => '128-bit', 'Base clock' => '1485 MHz', 'Boost clock' => '1665 MHz', 'Ray tracing' => false, 'Power consumption' => '75W', 'Recommended PSU' => '300W']],
                    ['name' => 'NVIDIA GeForce RTX 3060 12GB', 'brand' => 'NVIDIA', 'price' => 7200000, 'release_year' => 2021, 'specs' => ['GPU' => 'Ampere', 'VRAM' => '12GB', 'Memory type' => 'GDDR6', 'Bus memory' => '192-bit', 'Base clock' => '1320 MHz', 'Boost clock' => '1777 MHz', 'Ray tracing' => true, 'Power consumption' => '170W', 'Recommended PSU' => '550W']],
                    ['name' => 'NVIDIA GeForce RTX 4070 SUPER 12GB', 'brand' => 'NVIDIA', 'price' => 17500000, 'release_year' => 2024, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '12GB', 'Memory type' => 'GDDR6X', 'Bus memory' => '192-bit', 'Base clock' => '1980 MHz', 'Boost clock' => '2475 MHz', 'Ray tracing' => true, 'Power consumption' => '220W', 'Recommended PSU' => '650W']],
                    ['name' => 'NVIDIA GeForce RTX 4090 24GB', 'brand' => 'NVIDIA', 'price' => 48000000, 'release_year' => 2022, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '24GB', 'Memory type' => 'GDDR6X', 'Bus memory' => '384-bit', 'Base clock' => '2235 MHz', 'Boost clock' => '2520 MHz', 'Ray tracing' => true, 'Power consumption' => '450W', 'Recommended PSU' => '850W']],
                    ['name' => 'AMD Radeon RX 6600 8GB', 'brand' => 'AMD', 'price' => 5800000, 'release_year' => 2021, 'specs' => ['GPU' => 'RDNA 2', 'VRAM' => '8GB', 'Memory type' => 'GDDR6', 'Bus memory' => '128-bit', 'Base clock' => '1626 MHz', 'Boost clock' => '2491 MHz', 'Ray tracing' => true, 'Power consumption' => '132W', 'Recommended PSU' => '500W']],
                    ['name' => 'AMD Radeon RX 7800 XT 16GB', 'brand' => 'AMD', 'price' => 15500000, 'release_year' => 2023, 'specs' => ['GPU' => 'RDNA 3', 'VRAM' => '16GB', 'Memory type' => 'GDDR6', 'Bus memory' => '256-bit', 'Base clock' => '2124 MHz', 'Boost clock' => '2430 MHz', 'Ray tracing' => true, 'Power consumption' => '263W', 'Recommended PSU' => '700W']],
                    ['name' => 'AMD Radeon RX 7900 XTX 24GB', 'brand' => 'AMD', 'price' => 29500000, 'release_year' => 2022, 'specs' => ['GPU' => 'RDNA 3', 'VRAM' => '24GB', 'Memory type' => 'GDDR6', 'Bus memory' => '384-bit', 'Base clock' => '1855 MHz', 'Boost clock' => '2499 MHz', 'Ray tracing' => true, 'Power consumption' => '355W', 'Recommended PSU' => '850W']],
                ],
            ],
            'PSU' => [
                'attributes' => ['Công suất', 'Hiệu suất', 'Modular', 'Fan size', 'Input voltage', 'Rail', 'Protections', 'Connectors'],
                'products' => [
                    ['name' => 'Corsair CV450 450W 80 Plus Bronze', 'brand' => 'Corsair', 'price' => 850000, 'release_year' => 2019, 'specs' => ['Công suất' => '450W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x1, PCIe 6+2-pin x2, SATA x3, Molex x2']],
                    ['name' => 'Seasonic S12III 550W 80 Plus Bronze', 'brand' => 'Seasonic', 'price' => 1250000, 'release_year' => 2019, 'specs' => ['Công suất' => '550W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x1, PCIe 6+2-pin x2, SATA x5, Molex x3']],
                    ['name' => 'Corsair CX650 650W 80 Plus Bronze', 'brand' => 'Corsair', 'price' => 1500000, 'release_year' => 2020, 'specs' => ['Công suất' => '650W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x1, PCIe 6+2-pin x4, SATA x7, Molex x2']],
                    ['name' => 'Corsair RM750x 750W 80 Plus Gold', 'brand' => 'Corsair', 'price' => 2850000, 'release_year' => 2021, 'specs' => ['Công suất' => '750W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, SATA x8, Molex x4']],
                    ['name' => 'MSI MPG A1000G PCIE5', 'brand' => 'MSI', 'price' => 6200000, 'release_year' => 2022, 'specs' => ['Công suất' => '1000W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, PCIe 16-pin x1, SATA x12, Molex x4']],
                ],
            ],
            'Case' => [
                'attributes' => ['Form factor support', 'Motherboard support', 'Side panel', 'Front panel', 'Included fans', 'Radiator support', 'Max GPU length', 'Max CPU cooler height', 'PSU form factor'],
                'products' => [
                    ['name' => 'Aigo DarkFlash DLM21 Mesh', 'brand' => 'DarkFlash', 'price' => 650000, 'release_year' => 2020, 'specs' => ['Form factor support' => ['Micro-ATX', 'Mini-ITX'], 'Motherboard support' => ['Micro-ATX', 'Mini-ITX'], 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '240/240/120 mm', 'Max GPU length' => '300 mm', 'Max CPU cooler height' => '160 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Montech X3 Mesh', 'brand' => 'Montech', 'price' => 850000, 'release_year' => 2021, 'specs' => ['Form factor support' => ['ATX', 'Micro-ATX', 'Mini-ITX'], 'Motherboard support' => ['ATX', 'Micro-ATX', 'Mini-ITX'], 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 6, 'Radiator support' => '360/240/120 mm', 'Max GPU length' => '305 mm', 'Max CPU cooler height' => '160 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Corsair 4000D Airflow', 'brand' => 'Corsair', 'price' => 2200000, 'release_year' => 2020, 'specs' => ['Form factor support' => ['ATX', 'Micro-ATX', 'Mini-ITX'], 'Motherboard support' => ['ATX', 'Micro-ATX', 'Mini-ITX'], 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '360/280/120 mm', 'Max GPU length' => '360 mm', 'Max CPU cooler height' => '170 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Lian Li Lancool 216', 'brand' => 'Lian Li', 'price' => 2950000, 'release_year' => 2022, 'specs' => ['Form factor support' => ['ATX', 'Micro-ATX', 'Mini-ITX'], 'Motherboard support' => ['ATX', 'Micro-ATX', 'Mini-ITX'], 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '360/360/140 mm', 'Max GPU length' => '392 mm', 'Max CPU cooler height' => '180 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Lian Li O11 Dynamic EVO', 'brand' => 'Lian Li', 'price' => 4200000, 'release_year' => 2022, 'specs' => ['Form factor support' => ['ATX', 'Micro-ATX', 'Mini-ITX', 'E-ATX'], 'Motherboard support' => ['ATX', 'Micro-ATX', 'Mini-ITX', 'E-ATX'], 'Side panel' => 'Tempered Glass', 'Front panel' => 'Open Air / Glass', 'Included fans' => 0, 'Radiator support' => '360/360/360/120 mm', 'Max GPU length' => '426 mm', 'Max CPU cooler height' => '167 mm', 'PSU form factor' => 'ATX']],
                ],
            ],
        ];
    }

    private function buildDescription(array $product): string
    {
        $specPairs = [];
        foreach ($product['specs'] as $key => $value) {
            $specPairs[] = $key . ': ' . $this->stringifyValue($value);
        }

        return sprintf('%s %s - Released %d. %s.', $product['brand'], $product['name'], $product['release_year'], implode('; ', $specPairs));
    }

    private function stringifyValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this, 'stringifyValue'], $value));
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        return (string) $value;
    }
}
