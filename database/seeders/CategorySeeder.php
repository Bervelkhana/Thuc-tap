<?php

namespace Database\Seeders;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $data = [
            'CPU' => [
                'keys' => ['Socket', 'Số nhân', 'Số luồng', 'Xung nhịp', 'TDP'],
                'products' => [
                    ['Intel Core i5-13400F', 3690000, ['LGA1700', '10 (6P+4E)', '16', '2.5 / 4.6 GHz', '65W']],
                    ['Intel Core i7-13700K', 9990000, ['LGA1700', '16 (8P+8E)', '24', '3.4 / 5.4 GHz', '125W']],
                    ['AMD Ryzen 5 7600', 4590000, ['AM5', '6', '12', '3.8 / 5.1 GHz', '65W']],
                    ['AMD Ryzen 7 7800X3D', 11990000, ['AM5', '8', '16', '4.2 / 5.0 GHz', '120W']],
                ],
            ],
            'RAM' => [
                'keys' => ['Chuẩn', 'Dung lượng', 'Bus', 'Độ trễ (CAS)'],
                'products' => [
                    ['Corsair Vengeance DDR5 16GB', 1490000, ['DDR5', '16GB (2x8)', '5600 MHz', 'CL36']],
                    ['G.Skill Trident Z5 DDR5 32GB', 3290000, ['DDR5', '32GB (2x16)', '6000 MHz', 'CL30']],
                    ['Kingston Fury Beast DDR4 16GB', 890000, ['DDR4', '16GB (2x8)', '3200 MHz', 'CL16']],
                    ['Crucial Ballistix DDR5 32GB', 2790000, ['DDR5', '32GB (2x16)', '5200 MHz', 'CL40']],
                ],
            ],
            'Mainboard' => [
                'keys' => ['Socket', 'Chipset', 'Chuẩn', 'RAM hỗ trợ', 'Khe M.2'],
                'products' => [
                    ['ASUS ROG STRIX B760-A', 5990000, ['LGA1700', 'B760', 'ATX', 'DDR5', '2']],
                    ['MSI MAG B650 TOMAHAWK', 5490000, ['AM5', 'B650', 'ATX', 'DDR5', '3']],
                    ['Gigabyte B550 AORUS ELITE', 2990000, ['AM4', 'B550', 'ATX', 'DDR4', '2']],
                    ['ASRock B760M Steel Legend', 3490000, ['LGA1700', 'B760', 'mATX', 'DDR5', '2']],
                ],
            ],
            'VGA' => [
                'keys' => ['GPU', 'VRAM', 'Bus bộ nhớ', 'Xung nhịp', 'Nguồn đề xuất'],
                'products' => [
                    ['NVIDIA RTX 4060 8GB', 7990000, ['AD107', '8GB GDDR6', '128-bit', '2.46 GHz', '550W']],
                    ['NVIDIA RTX 4070 Ti 12GB', 18990000, ['AD104', '12GB GDDR6X', '192-bit', '2.61 GHz', '700W']],
                    ['AMD RX 7800 XT 16GB', 15990000, ['Navi 32', '16GB GDDR6', '256-bit', '2.43 GHz', '700W']],
                    ['NVIDIA RTX 4090 24GB', 49990000, ['AD102', '24GB GDDR6X', '384-bit', '2.52 GHz', '850W']],
                ],
            ],
            'SSD' => [
                'keys' => ['Loại', 'Dung lượng', 'Tốc độ đọc', 'Tốc độ ghi'],
                'products' => [
                    ['Samsung 980 PRO 1TB', 2290000, ['NVMe PCIe 4.0', '1TB', '7000 MB/s', '5000 MB/s']],
                    ['WD Black SN770 1TB', 1590000, ['NVMe PCIe 4.0', '1TB', '5150 MB/s', '4900 MB/s']],
                    ['Crucial MX500 1TB', 1290000, ['SATA III', '1TB', '560 MB/s', '510 MB/s']],
                    ['Seagate Barracuda 2TB', 1490000, ['SATA III HDD', '2TB', '7200 RPM', '-']],
                ],
            ],
            'PSU' => [
                'keys' => ['Công suất', 'Chuẩn hiệu suất', 'Modular'],
                'products' => [
                    ['Corsair RM750e', 2190000, ['750W', '80+ Gold', 'Full Modular']],
                    ['Seasonic Focus GX-850', 2890000, ['850W', '80+ Gold', 'Full Modular']],
                    ['FSP Hydro G 650W', 1490000, ['650W', '80+ Gold', 'Semi Modular']],
                ],
            ],
            'Case' => [
                'keys' => ['Chuẩn', 'Hỗ trợ main', 'Kính cường lực'],
                'products' => [
                    ['NZXT H5 Flow', 2090000, ['Mid Tower', 'ATX / mATX', 'Có']],
                    ['Lian Li O11 Dynamic', 3490000, ['Mid Tower', 'ATX', 'Có']],
                    ['Cooler Master TD500', 1690000, ['Mid Tower', 'ATX / mATX', 'Có']],
                ],
            ],
        ];

        // Cache attribute theo tên để tái sử dụng (tránh tạo trùng)
        $attributeCache = [];

        foreach ($data as $catName => $cat) {
            $category = Category::create(['name' => $catName]);

            // Tạo (hoặc lấy) các Attribute và gắn vào category (pivot category_attribute)
            $attributeIds = [];
            foreach ($cat['keys'] as $key) {
                if (!isset($attributeCache[$key])) {
                    $attributeCache[$key] = Attribute::firstOrCreate(['name' => $key])->id;
                }
                $attributeIds[] = $attributeCache[$key];
            }
            $category->attributes()->syncWithoutDetaching($attributeIds);

            foreach ($cat['products'] as [$name, $price, $values]) {
                $product = Product::factory()->create([
                    'category_id' => $category->id,
                    'name' => $name,
                    'sku' => Str::upper(Str::slug($name)),
                    'price' => $price,
                    'stock_quantity' => rand(5, 40),
                ]);

                // Ghi giá trị EAV: (product, attribute) => value qua pivot product_attribute_values
                $sync = [];
                foreach ($cat['keys'] as $i => $key) {
                    $sync[$attributeCache[$key]] = ['value' => $values[$i]];
                }
                $product->attributes()->sync($sync);
            }
        }
    }
}
