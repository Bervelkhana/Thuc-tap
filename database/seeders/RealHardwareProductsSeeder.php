<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RealHardwareProductsSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = $this->catalog();

        foreach ($catalog as $categoryName => $config) {
            $category = Category::updateOrCreate(
                ['name' => $categoryName],
                ['parent_id' => null]
            );

            foreach ($config['attributes'] as $attributeName => $type) {
                $code = Str::slug($attributeName);
                Attribute::updateOrCreate(
                    ['name' => $attributeName],
                    ['code' => $code, 'type' => $type, 'is_required' => false]
                );
                $category->attributes()->syncWithoutDetaching([Attribute::where('name', $attributeName)->first()->id]);
            }

            foreach ($config['products'] as $productData) {
                $sku = Str::upper(Str::slug($productData['brand'] . '-' . $productData['name']));
                $description = $this->buildDescription($productData);

                $product = Product::updateOrCreate(
                    ['sku' => $sku],
                    [
                        'category_id' => $category->id,
                        'name' => $productData['name'],
                        'price' => $productData['price'],
                        'stock_quantity' => 20,
                        'description' => $description,
                        'thumbnail_url' => null,
                        'brand' => $productData['brand'],
                        'socket_type' => $productData['socket_type'] ?? null,
                        'chipset' => $productData['chipset'] ?? null,
                        'platform' => $productData['platform'] ?? null,
                        'tier' => $productData['tier'] ?? null,
                        'tdp' => $productData['tdp'] ?? null,
                        'memory_type' => $productData['memory_type'] ?? null,
                        'memory_speed' => $productData['memory_speed'] ?? null,
                        'gpu_length_mm' => $productData['gpu_length_mm'] ?? null,
                        'max_gpu_length_mm' => $productData['max_gpu_length_mm'] ?? null,
                    ]
                );

                foreach ($productData['specs'] as $attributeName => $value) {
                    $attribute = Attribute::firstOrCreate(
                        ['name' => $attributeName],
                        ['code' => Str::slug($attributeName), 'type' => $this->guessType($value), 'is_required' => false]
                    );

                    ProductAttributeValue::updateOrCreate(
                        [
                            'product_id' => $product->id,
                            'attribute_id' => $attribute->id,
                        ],
                        ['value' => $this->stringifyValue($value)]
                    );
                }
            }
        }
    }

    private function catalog(): array
    {
        return [
            'CPU' => [
                'attributes' => [
                    'Socket' => 'string',
                    'Số nhân' => 'number',
                    'Số luồng' => 'number',
                    'Xung nhịp gốc' => 'string',
                    'Xung nhịp boost' => 'string',
                    'Cache' => 'string',
                    'TDP' => 'number',
                    'iGPU' => 'boolean',
                    'Tiến trình' => 'string',
                ],
                'products' => [
                    ['name' => 'Intel Core i3-10100F', 'brand' => 'Intel', 'price' => 1800000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1200', 'tdp' => 65, 'specs' => ['Socket' => 'LGA1200', 'Số nhân' => 4, 'Số luồng' => 8, 'Xung nhịp gốc' => '3.6 GHz', 'Xung nhịp boost' => '4.3 GHz', 'Cache' => '6 MB', 'TDP' => 65, 'iGPU' => false, 'Tiến trình' => '14 nm']],
                    ['name' => 'Intel Core i3-12100F', 'brand' => 'Intel', 'price' => 2200000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 58, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 4, 'Số luồng' => 8, 'Xung nhịp gốc' => '3.3 GHz', 'Xung nhịp boost' => '4.3 GHz', 'Cache' => '12 MB', 'TDP' => 58, 'iGPU' => false, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i3-13100', 'brand' => 'Intel', 'price' => 2500000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 60, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 4, 'Số luồng' => 8, 'Xung nhịp gốc' => '3.4 GHz', 'Xung nhịp boost' => '4.5 GHz', 'Cache' => '12 MB', 'TDP' => 60, 'iGPU' => true, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i3-14100F', 'brand' => 'Intel', 'price' => 2300000, 'release_year' => 2024, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 58, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 4, 'Số luồng' => 8, 'Xung nhịp gốc' => '3.5 GHz', 'Xung nhịp boost' => '4.7 GHz', 'Cache' => '12 MB', 'TDP' => 58, 'iGPU' => false, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i5-12400F', 'brand' => 'Intel', 'price' => 3200000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 65, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 6, 'Số luồng' => 12, 'Xung nhịp gốc' => '2.5 GHz', 'Xung nhịp boost' => '4.4 GHz', 'Cache' => '18 MB', 'TDP' => 65, 'iGPU' => false, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i5-13400F', 'brand' => 'Intel', 'price' => 4300000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 65, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 10, 'Số luồng' => 16, 'Xung nhịp gốc' => '2.5 GHz', 'Xung nhịp boost' => '4.6 GHz', 'Cache' => '20 MB', 'TDP' => 65, 'iGPU' => false, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i5-14400', 'brand' => 'Intel', 'price' => 3800000, 'release_year' => 2024, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 65, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 10, 'Số luồng' => 16, 'Xung nhịp gốc' => '2.5 GHz', 'Xung nhịp boost' => '4.7 GHz', 'Cache' => '20 MB', 'TDP' => 65, 'iGPU' => true, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i5-14600K', 'brand' => 'Intel', 'price' => 7500000, 'release_year' => 2024, 'tier' => 'high', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 125, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 14, 'Số luồng' => 20, 'Xung nhịp gốc' => '3.5 GHz', 'Xung nhịp boost' => '5.3 GHz', 'Cache' => '24 MB', 'TDP' => 125, 'iGPU' => true, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i7-14700K', 'brand' => 'Intel', 'price' => 12000000, 'release_year' => 2024, 'tier' => 'high', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 125, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 20, 'Số luồng' => 28, 'Xung nhịp gốc' => '3.4 GHz', 'Xung nhịp boost' => '5.6 GHz', 'Cache' => '28 MB', 'TDP' => 125, 'iGPU' => true, 'Tiến trình' => '10 nm']],
                    ['name' => 'Intel Core i9-14900K', 'brand' => 'Intel', 'price' => 18000000, 'release_year' => 2024, 'tier' => 'ultra', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'tdp' => 125, 'specs' => ['Socket' => 'LGA1700', 'Số nhân' => 24, 'Số luồng' => 32, 'Xung nhịp gốc' => '3.2 GHz', 'Xung nhịp boost' => '6.0 GHz', 'Cache' => '32 MB', 'TDP' => 125, 'iGPU' => true, 'Tiến trình' => '10 nm']],
                    ['name' => 'AMD Ryzen 5 5600', 'brand' => 'AMD', 'price' => 3200000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'amd', 'socket_type' => 'AM4', 'tdp' => 65, 'specs' => ['Socket' => 'AM4', 'Số nhân' => 6, 'Số luồng' => 12, 'Xung nhịp gốc' => '3.5 GHz', 'Xung nhịp boost' => '4.4 GHz', 'Cache' => '35 MB', 'TDP' => 65, 'iGPU' => false, 'Tiến trình' => '7 nm']],
                    ['name' => 'AMD Ryzen 5 7600', 'brand' => 'AMD', 'price' => 4800000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'amd', 'socket_type' => 'AM5', 'tdp' => 65, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 6, 'Số luồng' => 12, 'Xung nhịp gốc' => '3.8 GHz', 'Xung nhịp boost' => '5.1 GHz', 'Cache' => '32 MB', 'TDP' => 65, 'iGPU' => true, 'Tiến trình' => '5 nm']],
                    ['name' => 'AMD Ryzen 5 9600X', 'brand' => 'AMD', 'price' => 5500000, 'release_year' => 2024, 'tier' => 'mid', 'platform' => 'amd', 'socket_type' => 'AM5', 'tdp' => 65, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 6, 'Số luồng' => 12, 'Xung nhịp gốc' => '3.9 GHz', 'Xung nhịp boost' => '5.4 GHz', 'Cache' => '32 MB', 'TDP' => 65, 'iGPU' => true, 'Tiến trình' => '4 nm']],
                    ['name' => 'AMD Ryzen 7 7800X3D', 'brand' => 'AMD', 'price' => 9800000, 'release_year' => 2023, 'tier' => 'high', 'platform' => 'amd', 'socket_type' => 'AM5', 'tdp' => 120, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 8, 'Số luồng' => 16, 'Xung nhịp gốc' => '4.2 GHz', 'Xung nhịp boost' => '5.0 GHz', 'Cache' => '104 MB', 'TDP' => 120, 'iGPU' => true, 'Tiến trình' => '5 nm']],
                    ['name' => 'AMD Ryzen 7 9700X', 'brand' => 'AMD', 'price' => 8200000, 'release_year' => 2024, 'tier' => 'high', 'platform' => 'amd', 'socket_type' => 'AM5', 'tdp' => 65, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 8, 'Số luồng' => 16, 'Xung nhịp gốc' => '3.8 GHz', 'Xung nhịp boost' => '5.5 GHz', 'Cache' => '40 MB', 'TDP' => 65, 'iGPU' => true, 'Tiến trình' => '4 nm']],
                    ['name' => 'AMD Ryzen 9 7950X', 'brand' => 'AMD', 'price' => 14500000, 'release_year' => 2022, 'tier' => 'ultra', 'platform' => 'amd', 'socket_type' => 'AM5', 'tdp' => 170, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 16, 'Số luồng' => 32, 'Xung nhịp gốc' => '4.5 GHz', 'Xung nhịp boost' => '5.7 GHz', 'Cache' => '80 MB', 'TDP' => 170, 'iGPU' => true, 'Tiến trình' => '5 nm']],
                    ['name' => 'AMD Ryzen 9 7900X3D', 'brand' => 'AMD', 'price' => 15000000, 'release_year' => 2023, 'tier' => 'ultra', 'platform' => 'amd', 'socket_type' => 'AM5', 'tdp' => 120, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 12, 'Số luồng' => 24, 'Xung nhịp gốc' => '4.4 GHz', 'Xung nhịp boost' => '5.6 GHz', 'Cache' => '120 MB', 'TDP' => 120, 'iGPU' => true, 'Tiến trình' => '5 nm']],
                    ['name' => 'AMD Ryzen 9 9950X', 'brand' => 'AMD', 'price' => 22000000, 'release_year' => 2024, 'tier' => 'ultra', 'platform' => 'amd', 'socket_type' => 'AM5', 'tdp' => 170, 'specs' => ['Socket' => 'AM5', 'Số nhân' => 16, 'Số luồng' => 32, 'Xung nhịp gốc' => '4.3 GHz', 'Xung nhịp boost' => '5.7 GHz', 'Cache' => '80 MB', 'TDP' => 170, 'iGPU' => true, 'Tiến trình' => '4 nm']],
                ],
            ],
            'MAIN' => [
                'attributes' => [
                    'Socket' => 'string',
                    'Chipset' => 'string',
                    'Chuẩn' => 'string',
                    'RAM hỗ trợ' => 'string',
                    'Khe M.2' => 'number',
                    'PCIe x16' => 'number',
                    'SATA' => 'number',
                    'LAN' => 'string',
                    'Wi-Fi' => 'string',
                    'Audio' => 'string',
                ],
                'products' => [
                    ['name' => 'Gigabyte H410M H V2', 'brand' => 'Gigabyte', 'price' => 1450000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1200', 'chipset' => 'Intel H410', 'specs' => ['Socket' => 'LGA1200', 'Chipset' => 'Intel H410', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 1, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC887']],
                    ['name' => 'ASUS Prime H610M-A D4', 'brand' => 'ASUS', 'price' => 1800000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel H610', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel H610', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 1, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'MSI B560M PRO-VDH', 'brand' => 'MSI', 'price' => 2300000, 'release_year' => 2021, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1200', 'chipset' => 'Intel B560', 'specs' => ['Socket' => 'LGA1200', 'Chipset' => 'Intel B560', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'ASUS Prime B660M-A WIFI D4', 'brand' => 'ASUS', 'price' => 2800000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel B660', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel B660', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'Wi-Fi 5', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'MSI MAG B660M MORTAR WIFI DDR4', 'brand' => 'MSI', 'price' => 4200000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel B660', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel B660', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6', 'Audio' => 'Realtek ALC1200']],
                    ['name' => 'MSI PRO B760M-A WIFI', 'brand' => 'MSI', 'price' => 3900000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel B760', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel B760', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'MSI MAG B760M MORTAR WIFI', 'brand' => 'MSI', 'price' => 4200000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel B760', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel B760', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'Gigabyte B760 AORUS ELITE AX', 'brand' => 'Gigabyte', 'price' => 4900000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel B760', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel B760', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'ASRock B760M Steel Legend WiFi', 'brand' => 'ASRock', 'price' => 3950000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel B760', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel B760', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'ASUS Prime A620M-K', 'brand' => 'ASUS', 'price' => 2200000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'amd', 'socket_type' => 'AM5', 'chipset' => 'AMD A620', 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD A620', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 1GbE', 'Wi-Fi' => 'No', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'MSI PRO B650M-A WIFI', 'brand' => 'MSI', 'price' => 4300000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'amd', 'socket_type' => 'AM5', 'chipset' => 'AMD B650', 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD B650', 'Chuẩn' => 'Micro-ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 1, 'SATA' => 4, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'Realtek ALC897']],
                    ['name' => 'Gigabyte X670 AORUS ELITE AX', 'brand' => 'Gigabyte', 'price' => 7850000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'amd', 'socket_type' => 'AM5', 'chipset' => 'AMD X670', 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD X670', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 2, 'PCIe x16' => 2, 'SATA' => 6, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'Realtek ALC4080']],
                    ['name' => 'MSI MPG X670E CARBON WIFI', 'brand' => 'MSI', 'price' => 9250000, 'release_year' => 2023, 'tier' => 'high', 'platform' => 'amd', 'socket_type' => 'AM5', 'chipset' => 'AMD X670E', 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD X670E', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 4, 'PCIe x16' => 2, 'SATA' => 6, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'Realtek ALC4080']],
                    ['name' => 'ASUS ROG STRIX B650E-F GAMING WIFI', 'brand' => 'ASUS', 'price' => 8500000, 'release_year' => 2023, 'tier' => 'high', 'platform' => 'amd', 'socket_type' => 'AM5', 'chipset' => 'AMD B650E', 'specs' => ['Socket' => 'AM5', 'Chipset' => 'AMD B650E', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 4, 'PCIe x16' => 2, 'SATA' => 4, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'ROG SupremeFX ALC4080']],
                    ['name' => 'ASUS ROG STRIX Z790-E GAMING WIFI', 'brand' => 'ASUS', 'price' => 8600000, 'release_year' => 2023, 'tier' => 'high', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel Z790', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel Z790', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 5, 'PCIe x16' => 2, 'SATA' => 6, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'ROG SupremeFX ALC4080']],
                    ['name' => 'ASUS ROG STRIX Z790-F GAMING WIFI', 'brand' => 'ASUS', 'price' => 7500000, 'release_year' => 2023, 'tier' => 'high', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel Z790', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel Z790', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR5', 'Khe M.2' => 4, 'PCIe x16' => 2, 'SATA' => 6, 'LAN' => 'Intel 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'ROG SupremeFX ALC4080']],
                    ['name' => 'MSI PRO Z790-P WIFI DDR4', 'brand' => 'MSI', 'price' => 5800000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'intel', 'socket_type' => 'LGA1700', 'chipset' => 'Intel Z790', 'specs' => ['Socket' => 'LGA1700', 'Chipset' => 'Intel Z790', 'Chuẩn' => 'ATX', 'RAM hỗ trợ' => 'DDR4', 'Khe M.2' => 4, 'PCIe x16' => 1, 'SATA' => 6, 'LAN' => 'Realtek 2.5GbE', 'Wi-Fi' => 'Wi-Fi 6E', 'Audio' => 'Realtek ALC897']],
                ],
            ],
            'RAM' => [
                'attributes' => [
                    'Loại' => 'string',
                    'Dung lượng' => 'string',
                    'Số module' => 'number',
                    'Tốc độ' => 'string',
                    'CAS Latency' => 'string',
                    'Điện áp' => 'string',
                    'Form factor' => 'string',
                    'ECC' => 'boolean',
                    'RGB' => 'boolean',
                ],
                'products' => [
                    ['name' => 'Kingston FURY Beast 8GB DDR4 3200MHz', 'brand' => 'Kingston', 'price' => 450000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3200, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '8GB', 'Số module' => 1, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Kingston FURY Beast 16GB DDR4 3200MHz', 'brand' => 'Kingston', 'price' => 800000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3200, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '16GB', 'Số module' => 2, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'G.Skill Ripjaws V 16GB DDR4 3200MHz', 'brand' => 'G.Skill', 'price' => 650000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3200, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '16GB', 'Số module' => 2, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Corsair Vengeance LPX 16GB DDR4 3600MHz', 'brand' => 'Corsair', 'price' => 950000, 'release_year' => 2020, 'tier' => 'mid', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3600, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '16GB', 'Số module' => 2, 'Tốc độ' => '3600 MHz', 'CAS Latency' => 'CL18', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Corsair Vengeance RGB Pro 32GB DDR4 3200MHz', 'brand' => 'Corsair', 'price' => 1650000, 'release_year' => 2020, 'tier' => 'mid', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3200, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                    ['name' => 'G.Skill Ripjaws V 32GB DDR4 3600MHz', 'brand' => 'G.Skill', 'price' => 1400000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3600, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '3600 MHz', 'CAS Latency' => 'CL18', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Corsair Vengeance DDR5 16GB DDR5 5600MHz', 'brand' => 'Corsair', 'price' => 1200000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'memory_type' => 'DDR5', 'memory_speed' => 5600, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '16GB', 'Số module' => 2, 'Tốc độ' => '5600 MHz', 'CAS Latency' => 'CL36', 'Điện áp' => '1.25V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'G.Skill Trident Z5 RGB 32GB DDR5 6000MHz', 'brand' => 'G.Skill', 'price' => 2900000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'memory_type' => 'DDR5', 'memory_speed' => 6000, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '6000 MHz', 'CAS Latency' => 'CL30', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                    ['name' => 'Kingston FURY Beast 32GB DDR5 5600MHz', 'brand' => 'Kingston', 'price' => 1900000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'universal', 'memory_type' => 'DDR5', 'memory_speed' => 5600, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '5600 MHz', 'CAS Latency' => 'CL40', 'Điện áp' => '1.25V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'ADATA XPG Lancer 16GB DDR5 6000MHz', 'brand' => 'ADATA', 'price' => 850000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'universal', 'memory_type' => 'DDR5', 'memory_speed' => 6000, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '16GB', 'Số module' => 2, 'Tốc độ' => '6000 MHz', 'CAS Latency' => 'CL30', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Corsair Vengeance RGB 64GB DDR5 5600MHz', 'brand' => 'Corsair', 'price' => 5400000, 'release_year' => 2023, 'tier' => 'ultra', 'platform' => 'universal', 'memory_type' => 'DDR5', 'memory_speed' => 5600, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '64GB', 'Số module' => 2, 'Tốc độ' => '5600 MHz', 'CAS Latency' => 'CL40', 'Điện áp' => '1.25V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                    ['name' => 'G.Skill Trident Z5 RGB 64GB DDR5 6400MHz', 'brand' => 'G.Skill', 'price' => 4800000, 'release_year' => 2023, 'tier' => 'ultra', 'platform' => 'universal', 'memory_type' => 'DDR5', 'memory_speed' => 6400, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '64GB', 'Số module' => 2, 'Tốc độ' => '6400 MHz', 'CAS Latency' => 'CL32', 'Điện áp' => '1.4V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                    ['name' => 'TeamGroup Elite Plus 32GB DDR4 3200MHz', 'brand' => 'TeamGroup', 'price' => 950000, 'release_year' => 2021, 'tier' => 'entry', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3200, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Crucial Ballistix 16GB DDR4 3200MHz', 'brand' => 'Crucial', 'price' => 700000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'memory_type' => 'DDR4', 'memory_speed' => 3200, 'specs' => ['Loại' => 'DDR4', 'Dung lượng' => '16GB', 'Số module' => 2, 'Tốc độ' => '3200 MHz', 'CAS Latency' => 'CL16', 'Điện áp' => '1.35V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => false]],
                    ['name' => 'Corsair Dominator Platinum RGB 32GB DDR5 6400MHz', 'brand' => 'Corsair', 'price' => 4500000, 'release_year' => 2023, 'tier' => 'ultra', 'platform' => 'universal', 'memory_type' => 'DDR5', 'memory_speed' => 6400, 'specs' => ['Loại' => 'DDR5', 'Dung lượng' => '32GB', 'Số module' => 2, 'Tốc độ' => '6400 MHz', 'CAS Latency' => 'CL32', 'Điện áp' => '1.4V', 'Form factor' => 'UDIMM', 'ECC' => false, 'RGB' => true]],
                ],
            ],
            'VGA' => [
                'attributes' => [
                    'GPU' => 'string',
                    'VRAM' => 'string',
                    'Memory type' => 'string',
                    'Bus memory' => 'string',
                    'Base clock' => 'string',
                    'Boost clock' => 'string',
                    'Ray tracing' => 'boolean',
                    'Power consumption' => 'number',
                    'Recommended PSU' => 'string',
                ],
                'products' => [
                    ['name' => 'NVIDIA GeForce RTX 3050 8GB', 'brand' => 'NVIDIA', 'price' => 5500000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'other', 'tdp' => 130, 'gpu_length_mm' => 242, 'specs' => ['GPU' => 'Ampere', 'VRAM' => '8GB', 'Memory type' => 'GDDR6', 'Bus memory' => '128-bit', 'Base clock' => '1550 MHz', 'Boost clock' => '1777 MHz', 'Ray tracing' => true, 'Power consumption' => 130, 'Recommended PSU' => '550W']],
                    ['name' => 'NVIDIA GeForce RTX 3060 12GB', 'brand' => 'NVIDIA', 'price' => 7200000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'other', 'tdp' => 170, 'gpu_length_mm' => 242, 'specs' => ['GPU' => 'Ampere', 'VRAM' => '12GB', 'Memory type' => 'GDDR6', 'Bus memory' => '192-bit', 'Base clock' => '1320 MHz', 'Boost clock' => '1777 MHz', 'Ray tracing' => true, 'Power consumption' => 170, 'Recommended PSU' => '550W']],
                    ['name' => 'NVIDIA GeForce RTX 4060 8GB', 'brand' => 'NVIDIA', 'price' => 7900000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'other', 'tdp' => 115, 'gpu_length_mm' => 242, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '8GB', 'Memory type' => 'GDDR6', 'Bus memory' => '128-bit', 'Base clock' => '1830 MHz', 'Boost clock' => '2460 MHz', 'Ray tracing' => true, 'Power consumption' => 115, 'Recommended PSU' => '550W']],
                    ['name' => 'NVIDIA GeForce RTX 4060 Ti 16GB', 'brand' => 'NVIDIA', 'price' => 11000000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'other', 'tdp' => 160, 'gpu_length_mm' => 244, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '16GB', 'Memory type' => 'GDDR6', 'Bus memory' => '128-bit', 'Base clock' => '1665 MHz', 'Boost clock' => '2535 MHz', 'Ray tracing' => true, 'Power consumption' => 160, 'Recommended PSU' => '550W']],
                    ['name' => 'NVIDIA GeForce RTX 4070 12GB', 'brand' => 'NVIDIA', 'price' => 14600000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'other', 'tdp' => 200, 'gpu_length_mm' => 244, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '12GB', 'Memory type' => 'GDDR6X', 'Bus memory' => '192-bit', 'Base clock' => '1920 MHz', 'Boost clock' => '2475 MHz', 'Ray tracing' => true, 'Power consumption' => 200, 'Recommended PSU' => '650W']],
                    ['name' => 'NVIDIA GeForce RTX 4070 SUPER 12GB', 'brand' => 'NVIDIA', 'price' => 14500000, 'release_year' => 2024, 'tier' => 'mid', 'platform' => 'other', 'tdp' => 220, 'gpu_length_mm' => 244, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '12GB', 'Memory type' => 'GDDR6X', 'Bus memory' => '192-bit', 'Base clock' => '1980 MHz', 'Boost clock' => '2475 MHz', 'Ray tracing' => true, 'Power consumption' => 220, 'Recommended PSU' => '650W']],
                    ['name' => 'NVIDIA GeForce RTX 4070 Ti SUPER 16GB', 'brand' => 'NVIDIA', 'price' => 20800000, 'release_year' => 2024, 'tier' => 'high', 'platform' => 'other', 'tdp' => 285, 'gpu_length_mm' => 336, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '16GB', 'Memory type' => 'GDDR6X', 'Bus memory' => '256-bit', 'Base clock' => '2340 MHz', 'Boost clock' => '2610 MHz', 'Ray tracing' => true, 'Power consumption' => 285, 'Recommended PSU' => '700W']],
                    ['name' => 'NVIDIA GeForce RTX 4080 SUPER 16GB', 'brand' => 'NVIDIA', 'price' => 24600000, 'release_year' => 2024, 'tier' => 'high', 'platform' => 'other', 'tdp' => 320, 'gpu_length_mm' => 304, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '16GB', 'Memory type' => 'GDDR6X', 'Bus memory' => '256-bit', 'Base clock' => '2290 MHz', 'Boost clock' => '2550 MHz', 'Ray tracing' => true, 'Power consumption' => 320, 'Recommended PSU' => '750W']],
                    ['name' => 'NVIDIA GeForce RTX 4090 24GB', 'brand' => 'NVIDIA', 'price' => 32000000, 'release_year' => 2022, 'tier' => 'ultra', 'platform' => 'other', 'tdp' => 450, 'gpu_length_mm' => 336, 'specs' => ['GPU' => 'Ada Lovelace', 'VRAM' => '24GB', 'Memory type' => 'GDDR6X', 'Bus memory' => '384-bit', 'Base clock' => '2235 MHz', 'Boost clock' => '2520 MHz', 'Ray tracing' => true, 'Power consumption' => 450, 'Recommended PSU' => '850W']],
                    ['name' => 'AMD Radeon RX 6600 8GB', 'brand' => 'AMD', 'price' => 5800000, 'release_year' => 2021, 'tier' => 'entry', 'platform' => 'other', 'tdp' => 132, 'gpu_length_mm' => 247, 'specs' => ['GPU' => 'RDNA 2', 'VRAM' => '8GB', 'Memory type' => 'GDDR6', 'Bus memory' => '128-bit', 'Base clock' => '1626 MHz', 'Boost clock' => '2491 MHz', 'Ray tracing' => true, 'Power consumption' => 132, 'Recommended PSU' => '500W']],
                    ['name' => 'AMD Radeon RX 7600 8GB', 'brand' => 'AMD', 'price' => 6600000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'other', 'tdp' => 165, 'gpu_length_mm' => 260, 'specs' => ['GPU' => 'RDNA 3', 'VRAM' => '8GB', 'Memory type' => 'GDDR6', 'Bus memory' => '128-bit', 'Base clock' => '1880 MHz', 'Boost clock' => '2655 MHz', 'Ray tracing' => true, 'Power consumption' => 165, 'Recommended PSU' => '550W']],
                    ['name' => 'AMD Radeon RX 7800 XT 16GB', 'brand' => 'AMD', 'price' => 15500000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'other', 'tdp' => 263, 'gpu_length_mm' => 287, 'specs' => ['GPU' => 'RDNA 3', 'VRAM' => '16GB', 'Memory type' => 'GDDR6', 'Bus memory' => '256-bit', 'Base clock' => '2124 MHz', 'Boost clock' => '2430 MHz', 'Ray tracing' => true, 'Power consumption' => 263, 'Recommended PSU' => '700W']],
                    ['name' => 'AMD Radeon RX 7900 XT 20GB', 'brand' => 'AMD', 'price' => 21500000, 'release_year' => 2023, 'tier' => 'high', 'platform' => 'other', 'tdp' => 315, 'gpu_length_mm' => 287, 'specs' => ['GPU' => 'RDNA 3', 'VRAM' => '20GB', 'Memory type' => 'GDDR6', 'Bus memory' => '320-bit', 'Base clock' => '1855 MHz', 'Boost clock' => '2499 MHz', 'Ray tracing' => true, 'Power consumption' => 315, 'Recommended PSU' => '750W']],
                    ['name' => 'AMD Radeon RX 7900 XTX 24GB', 'brand' => 'AMD', 'price' => 29500000, 'release_year' => 2022, 'tier' => 'ultra', 'platform' => 'other', 'tdp' => 355, 'gpu_length_mm' => 287, 'specs' => ['GPU' => 'RDNA 3', 'VRAM' => '24GB', 'Memory type' => 'GDDR6', 'Bus memory' => '384-bit', 'Base clock' => '1855 MHz', 'Boost clock' => '2499 MHz', 'Ray tracing' => true, 'Power consumption' => 355, 'Recommended PSU' => '850W']],
                ],
            ],
            'SSD' => [
                'attributes' => [
                    'Dung lượng' => 'string',
                    'Interface' => 'string',
                    'Tốc độ đọc' => 'string',
                    'Tốc độ ghi' => 'string',
                    'Form factor' => 'string',
                ],
                'products' => [
                    ['name' => 'Samsung 980 PRO 1TB NVMe PCIe 4.0', 'brand' => 'Samsung', 'price' => 2350000, 'release_year' => 2020, 'tier' => 'high', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7000 MB/s', 'Tốc độ ghi' => '5000 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Samsung 980 PRO 2TB NVMe PCIe 4.0', 'brand' => 'Samsung', 'price' => 3800000, 'release_year' => 2020, 'tier' => 'ultra', 'platform' => 'universal', 'specs' => ['Dung lượng' => '2TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7000 MB/s', 'Tốc độ ghi' => '5000 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Samsung 990 PRO 1TB NVMe PCIe 4.0', 'brand' => 'Samsung', 'price' => 2800000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7450 MB/s', 'Tốc độ ghi' => '6900 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Samsung 990 PRO 2TB NVMe PCIe 4.0', 'brand' => 'Samsung', 'price' => 4200000, 'release_year' => 2022, 'tier' => 'ultra', 'platform' => 'universal', 'specs' => ['Dung lượng' => '2TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7450 MB/s', 'Tốc độ ghi' => '6900 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'WD Black SN770 1TB NVMe PCIe 4.0', 'brand' => 'Western Digital', 'price' => 1650000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '5150 MB/s', 'Tốc độ ghi' => '4900 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'WD Black SN850X 1TB NVMe PCIe 4.0', 'brand' => 'Western Digital', 'price' => 2200000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7300 MB/s', 'Tốc độ ghi' => '6300 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'WD Blue SN580 1TB NVMe PCIe 4.0', 'brand' => 'Western Digital', 'price' => 1250000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '4150 MB/s', 'Tốc độ ghi' => '4150 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Crucial P3 Plus 1TB NVMe PCIe 4.0', 'brand' => 'Crucial', 'price' => 1450000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '4700 MB/s', 'Tốc độ ghi' => '1900 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Crucial P3 Plus 2TB NVMe PCIe 4.0', 'brand' => 'Crucial', 'price' => 2200000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Dung lượng' => '2TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '4700 MB/s', 'Tốc độ ghi' => '1900 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Kingston NV2 1TB NVMe PCIe 4.0', 'brand' => 'Kingston', 'price' => 1100000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '3500 MB/s', 'Tốc độ ghi' => '2100 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Kingston NV2 2TB NVMe PCIe 4.0', 'brand' => 'Kingston', 'price' => 1900000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Dung lượng' => '2TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '3500 MB/s', 'Tốc độ ghi' => '2800 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'ADATA XPG GAMMIX S70 Blade 1TB', 'brand' => 'ADATA', 'price' => 1500000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7400 MB/s', 'Tốc độ ghi' => '3400 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'ADATA XPG GAMMIX S70 Blade 2TB', 'brand' => 'ADATA', 'price' => 2500000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Dung lượng' => '2TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7400 MB/s', 'Tốc độ ghi' => '6500 MB/s', 'Form factor' => 'M.2 2280']],
                    ['name' => 'Samsung 870 EVO 1TB SATA III', 'brand' => 'Samsung', 'price' => 1800000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'SATA III', 'Tốc độ đọc' => '560 MB/s', 'Tốc độ ghi' => '530 MB/s', 'Form factor' => '2.5 inch']],
                    ['name' => 'Crucial MX500 1TB SATA III', 'brand' => 'Crucial', 'price' => 1500000, 'release_year' => 2020, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Dung lượng' => '1TB', 'Interface' => 'SATA III', 'Tốc độ đọc' => '560 MB/s', 'Tốc độ ghi' => '510 MB/s', 'Form factor' => '2.5 inch']],
                    ['name' => 'WD Black SN850X 2TB NVMe PCIe 4.0', 'brand' => 'Western Digital', 'price' => 3800000, 'release_year' => 2023, 'tier' => 'ultra', 'platform' => 'universal', 'specs' => ['Dung lượng' => '2TB', 'Interface' => 'PCIe 4.0 x4', 'Tốc độ đọc' => '7300 MB/s', 'Tốc độ ghi' => '6300 MB/s', 'Form factor' => 'M.2 2280']],
                ],
            ],
            'PSU' => [
                'attributes' => [
                    'Công suất' => 'string',
                    'Hiệu suất' => 'string',
                    'Modular' => 'boolean',
                    'Fan size' => 'string',
                    'Input voltage' => 'string',
                    'Rail' => 'string',
                    'Protections' => 'string',
                    'Connectors' => 'string',
                ],
                'products' => [
                    ['name' => 'Corsair CV450 450W 80 Plus Bronze', 'brand' => 'Corsair', 'price' => 850000, 'release_year' => 2019, 'tier' => 'entry', 'platform' => 'universal', 'specs' => ['Công suất' => '450W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x1, PCIe 6+2-pin x2, SATA x3, Molex x2']],
                    ['name' => 'Corsair CX550 550W 80 Plus Bronze', 'brand' => 'Corsair', 'price' => 1000000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'specs' => ['Công suất' => '550W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x1, PCIe 6+2-pin x2, SATA x5, Molex x3']],
                    ['name' => 'Corsair CX650 650W 80 Plus Bronze', 'brand' => 'Corsair', 'price' => 1500000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'specs' => ['Công suất' => '650W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x1, PCIe 6+2-pin x4, SATA x7, Molex x2']],
                    ['name' => 'Seasonic S12III 550W 80 Plus Bronze', 'brand' => 'Seasonic', 'price' => 1250000, 'release_year' => 2019, 'tier' => 'entry', 'platform' => 'universal', 'specs' => ['Công suất' => '550W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x1, PCIe 6+2-pin x2, SATA x5, Molex x3']],
                    ['name' => 'Seasonic FOCUS GX-650 650W 80 Plus Gold', 'brand' => 'Seasonic', 'price' => 2400000, 'release_year' => 2020, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Công suất' => '650W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x4, SATA x8, Molex x3']],
                    ['name' => 'Corsair RM650x 650W 80 Plus Gold', 'brand' => 'Corsair', 'price' => 2500000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Công suất' => '650W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x4, SATA x6, Molex x2']],
                    ['name' => 'Corsair RM750x 750W 80 Plus Gold', 'brand' => 'Corsair', 'price' => 2850000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Công suất' => '750W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, SATA x8, Molex x4']],
                    ['name' => 'Corsair RM850x 850W 80 Plus Gold', 'brand' => 'Corsair', 'price' => 3500000, 'release_year' => 2021, 'tier' => 'high', 'platform' => 'universal', 'specs' => ['Công suất' => '850W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, PCIe 12VHPWR x1, SATA x8, Molex x4']],
                    ['name' => 'MSI MPG A750G PCIE5 750W 80 Plus Gold', 'brand' => 'MSI', 'price' => 2900000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Công suất' => '750W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, PCIe 16-pin x1, SATA x12, Molex x4']],
                    ['name' => 'MSI MPG A1000G PCIE5 1000W 80 Plus Gold', 'brand' => 'MSI', 'price' => 6200000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'specs' => ['Công suất' => '1000W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, PCIe 16-pin x1, SATA x12, Molex x4']],
                    ['name' => 'be quiet! System Power 10 650W 80 Plus Bronze', 'brand' => 'be quiet!', 'price' => 2000000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Công suất' => '650W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x4, SATA x8, Molex x4']],
                    ['name' => 'be quiet! Straight Power 12 750W 80 Plus Platinum', 'brand' => 'be quiet!', 'price' => 4500000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'specs' => ['Công suất' => '750W', 'Hiệu suất' => '80 Plus Platinum', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, PCIe 12VHPWR x1, SATA x10, Molex x4']],
                    ['name' => 'ASUS TUF Gaming 650B 650W 80 Plus Bronze', 'brand' => 'ASUS', 'price' => 1800000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Công suất' => '650W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x4, SATA x8, Molex x4']],
                    ['name' => 'ASUS ROG Strix 750W 80 Plus Gold', 'brand' => 'ASUS', 'price' => 3200000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'specs' => ['Công suất' => '750W', 'Hiệu suất' => '80 Plus Gold', 'Modular' => true, 'Fan size' => '135mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP, OCP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x6, PCIe 12VHPWR x1, SATA x8, Molex x4']],
                    ['name' => 'Cooler Master MWE 650 Bronze V2 650W', 'brand' => 'Cooler Master', 'price' => 1600000, 'release_year' => 2021, 'tier' => 'mid', 'platform' => 'universal', 'specs' => ['Công suất' => '650W', 'Hiệu suất' => '80 Plus Bronze', 'Modular' => false, 'Fan size' => '120mm', 'Input voltage' => '100-240V', 'Rail' => '+12V single rail', 'Protections' => 'OVP, UVP, OPP, SCP, OTP', 'Connectors' => 'ATX 24-pin x1, EPS 8-pin x2, PCIe 6+2-pin x4, SATA x8, Molex x4']],
                ],
            ],
            'CASE' => [
                'attributes' => [
                    'Form factor' => 'string',
                    'Motherboard support' => 'string',
                    'Side panel' => 'string',
                    'Front panel' => 'string',
                    'Included fans' => 'number',
                    'Radiator support' => 'string',
                    'Max GPU length' => 'string',
                    'Max CPU cooler height' => 'string',
                    'PSU form factor' => 'string',
                ],
                'products' => [
                    ['name' => 'Aigo DarkFlash DLM21 Mesh', 'brand' => 'DarkFlash', 'price' => 650000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'max_gpu_length_mm' => 300, 'specs' => ['Form factor' => 'Micro-ATX', 'Motherboard support' => 'Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '240/240/120 mm', 'Max GPU length' => '300 mm', 'Max CPU cooler height' => '160 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Montech X3 Mesh', 'brand' => 'Montech', 'price' => 850000, 'release_year' => 2021, 'tier' => 'entry', 'platform' => 'universal', 'max_gpu_length_mm' => 305, 'specs' => ['Form factor' => 'Micro-ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 6, 'Radiator support' => '360/240/120 mm', 'Max GPU length' => '305 mm', 'Max CPU cooler height' => '160 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Montech X5 Mesh', 'brand' => 'Montech', 'price' => 1200000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'universal', 'max_gpu_length_mm' => 320, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 4, 'Radiator support' => '360/240/120 mm', 'Max GPU length' => '320 mm', 'Max CPU cooler height' => '165 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'DarkFlash DK431 MESH', 'brand' => 'DarkFlash', 'price' => 1100000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'universal', 'max_gpu_length_mm' => 350, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 4, 'Radiator support' => '360/240/120 mm', 'Max GPU length' => '350 mm', 'Max CPU cooler height' => '165 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Corsair 4000D Airflow', 'brand' => 'Corsair', 'price' => 2200000, 'release_year' => 2020, 'tier' => 'mid', 'platform' => 'universal', 'max_gpu_length_mm' => 360, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '360/280/120 mm', 'Max GPU length' => '360 mm', 'Max CPU cooler height' => '170 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Corsair 5000D Airflow', 'brand' => 'Corsair', 'price' => 3200000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'max_gpu_length_mm' => 420, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '360/280/240/120 mm', 'Max GPU length' => '420 mm', 'Max CPU cooler height' => '170 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'NZXT H5 Flow', 'brand' => 'NZXT', 'price' => 2150000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'max_gpu_length_mm' => 360, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '280/240/120 mm', 'Max GPU length' => '360 mm', 'Max CPU cooler height' => '165 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'NZXT H7 Flow', 'brand' => 'NZXT', 'price' => 2800000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'universal', 'max_gpu_length_mm' => 435, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '360/280/240/120 mm', 'Max GPU length' => '435 mm', 'Max CPU cooler height' => '185 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'NZXT H9 Elite', 'brand' => 'NZXT', 'price' => 4250000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'max_gpu_length_mm' => 435, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, E-ATX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 4, 'Radiator support' => '360/360/280/120 mm', 'Max GPU length' => '435 mm', 'Max CPU cooler height' => '185 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Lian Li Lancool 216', 'brand' => 'Lian Li', 'price' => 2950000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'max_gpu_length_mm' => 380, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '360/360/140 mm', 'Max GPU length' => '392 mm', 'Max CPU cooler height' => '180 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Lian Li O11 Dynamic EVO', 'brand' => 'Lian Li', 'price' => 4200000, 'release_year' => 2022, 'tier' => 'high', 'platform' => 'universal', 'max_gpu_length_mm' => 426, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX, E-ATX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Open Air / Glass', 'Included fans' => 0, 'Radiator support' => '360/360/360/120 mm', 'Max GPU length' => '426 mm', 'Max CPU cooler height' => '167 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Fractal Design North', 'brand' => 'Fractal Design', 'price' => 3500000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'max_gpu_length_mm' => 360, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh/Wood', 'Included fans' => 2, 'Radiator support' => '360/280/240 mm', 'Max GPU length' => '360 mm', 'Max CPU cooler height' => '170 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Fractal Design Torrent', 'brand' => 'Fractal Design', 'price' => 4800000, 'release_year' => 2021, 'tier' => 'high', 'platform' => 'universal', 'max_gpu_length_mm' => 360, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '360/280/240 mm', 'Max GPU length' => '360 mm', 'Max CPU cooler height' => '185 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'be quiet! Pure Base 500', 'brand' => 'be quiet!', 'price' => 1800000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'max_gpu_length_mm' => 320, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Solid / Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 2, 'Radiator support' => '280/240 mm', 'Max GPU length' => '320 mm', 'Max CPU cooler height' => '165 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'be quiet! Dark Base 700', 'brand' => 'be quiet!', 'price' => 3800000, 'release_year' => 2021, 'tier' => 'high', 'platform' => 'universal', 'max_gpu_length_mm' => 350, 'specs' => ['Form factor' => 'ATX', 'Motherboard support' => 'ATX, Micro-ATX, Mini-ITX, E-ATX', 'Side panel' => 'Solid / Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 3, 'Radiator support' => '360/280/240 mm', 'Max GPU length' => '350 mm', 'Max CPU cooler height' => '170 mm', 'PSU form factor' => 'ATX']],
                    ['name' => 'Corsair iCUE 7000X RGB', 'brand' => 'Corsair', 'price' => 6850000, 'release_year' => 2022, 'tier' => 'ultra', 'platform' => 'universal', 'max_gpu_length_mm' => 450, 'specs' => ['Form factor' => 'Full Tower', 'Motherboard support' => 'E-ATX, ATX, Micro-ATX, Mini-ITX', 'Side panel' => 'Tempered Glass', 'Front panel' => 'Mesh', 'Included fans' => 3, 'Radiator support' => '360/280/240/120 mm', 'Max GPU length' => '450 mm', 'Max CPU cooler height' => '190 mm', 'PSU form factor' => 'ATX']],
                ],
            ],
            'COOLER' => [
                'attributes' => [
                    'Socket support' => 'string',
                    'TDP' => 'number',
                    'Type' => 'string',
                    'Fans' => 'string',
                    'Height/Radiator' => 'string',
                ],
                'products' => [
                    ['name' => 'Thermalright Phantom Spirit 120', 'brand' => 'Thermalright', 'price' => 1250000, 'release_year' => 2022, 'tier' => 'entry', 'platform' => 'universal', 'tdp' => 255, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 255, 'Type' => 'air', 'Fans' => '2x 120mm', 'Height/Radiator' => 'Height 155 mm']],
                    ['name' => 'Thermalright PA120', 'brand' => 'Thermalright', 'price' => 1100000, 'release_year' => 2021, 'tier' => 'entry', 'platform' => 'universal', 'tdp' => 250, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 250, 'Type' => 'air', 'Fans' => '2x 120mm', 'Height/Radiator' => 'Height 152 mm']],
                    ['name' => 'Deepcool AK620', 'brand' => 'Deepcool', 'price' => 1550000, 'release_year' => 2022, 'tier' => 'mid', 'platform' => 'universal', 'tdp' => 260, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 260, 'Type' => 'air', 'Fans' => '2x 120mm', 'Height/Radiator' => 'Height 160 mm']],
                    ['name' => 'Deepcool AG400', 'brand' => 'Deepcool', 'price' => 900000, 'release_year' => 2023, 'tier' => 'entry', 'platform' => 'universal', 'tdp' => 220, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 220, 'Type' => 'air', 'Fans' => '1x 120mm', 'Height/Radiator' => 'Height 154 mm']],
                    ['name' => 'Noctua NH-D15S', 'brand' => 'Noctua', 'price' => 2150000, 'release_year' => 2020, 'tier' => 'high', 'platform' => 'universal', 'tdp' => 250, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 250, 'Type' => 'air', 'Fans' => '2x 140mm', 'Height/Radiator' => 'Height 160 mm']],
                    ['name' => 'Noctua NH-U12S redux', 'brand' => 'Noctua', 'price' => 1450000, 'release_year' => 2020, 'tier' => 'entry', 'platform' => 'universal', 'tdp' => 180, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 180, 'Type' => 'air', 'Fans' => '1x 120mm', 'Height/Radiator' => 'Height 158 mm']],
                    ['name' => 'Noctua NH-D15 G2', 'brand' => 'Noctua', 'price' => 3200000, 'release_year' => 2024, 'tier' => 'ultra', 'platform' => 'universal', 'tdp' => 300, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 300, 'Type' => 'air', 'Fans' => '2x 140mm', 'Height/Radiator' => 'Height 163 mm']],
                    ['name' => 'be quiet! Dark Rock Pro 4', 'brand' => 'be quiet!', 'price' => 2450000, 'release_year' => 2019, 'tier' => 'high', 'platform' => 'universal', 'tdp' => 250, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 250, 'Type' => 'air', 'Fans' => '2x 120mm', 'Height/Radiator' => 'Height 163 mm']],
                    ['name' => 'be quiet! Dark Rock 5', 'brand' => 'be quiet!', 'price' => 2600000, 'release_year' => 2023, 'tier' => 'high', 'platform' => 'universal', 'tdp' => 270, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 270, 'Type' => 'air', 'Fans' => '2x 120mm', 'Height/Radiator' => 'Height 163 mm']],
                    ['name' => 'Arctic Liquid Freezer II 280', 'brand' => 'Arctic', 'price' => 2850000, 'release_year' => 2020, 'tier' => 'mid', 'platform' => 'universal', 'tdp' => 300, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 300, 'Type' => 'aio', 'Fans' => '2x 140mm', 'Height/Radiator' => 'Radiator 280 mm']],
                    ['name' => 'Arctic Liquid Freezer II 360', 'brand' => 'Arctic', 'price' => 3200000, 'release_year' => 2020, 'tier' => 'mid', 'platform' => 'universal', 'tdp' => 300, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 300, 'Type' => 'aio', 'Fans' => '3x 120mm', 'Height/Radiator' => 'Radiator 360 mm']],
                    ['name' => 'Arctic Liquid Freezer III 360', 'brand' => 'Arctic', 'price' => 3500000, 'release_year' => 2024, 'tier' => 'mid', 'platform' => 'universal', 'tdp' => 350, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 350, 'Type' => 'aio', 'Fans' => '3x 120mm', 'Height/Radiator' => 'Radiator 360 mm']],
                    ['name' => 'Cooler Master MasterLiquid 360L Core', 'brand' => 'Cooler Master', 'price' => 1950000, 'release_year' => 2023, 'tier' => 'mid', 'platform' => 'universal', 'tdp' => 350, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 350, 'Type' => 'aio', 'Fans' => '3x 120mm', 'Height/Radiator' => 'Radiator 360 mm']],
                    ['name' => 'Corsair iCUE H150i Elite LCD', 'brand' => 'Corsair', 'price' => 5500000, 'release_year' => 2022, 'tier' => 'ultra', 'platform' => 'universal', 'tdp' => 400, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 400, 'Type' => 'aio', 'Fans' => '3x 120mm', 'Height/Radiator' => 'Radiator 360 mm']],
                    ['name' => 'NZXT Kraken X63 280mm', 'brand' => 'NZXT', 'price' => 4200000, 'release_year' => 2020, 'tier' => 'high', 'platform' => 'universal', 'tdp' => 350, 'specs' => ['Socket support' => 'LGA1700, LGA1200, AM5, AM4', 'TDP' => 350, 'Type' => 'aio', 'Fans' => '2x 140mm', 'Height/Radiator' => 'Radiator 280 mm']],
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

    private function guessType(mixed $value): string
    {
        if (is_bool($value)) {
            return Attribute::TYPE_BOOLEAN;
        }

        if (is_int($value)) {
            return Attribute::TYPE_NUMBER;
        }

        if (is_array($value)) {
            return Attribute::TYPE_JSON;
        }

        return Attribute::TYPE_STRING;
    }
}
