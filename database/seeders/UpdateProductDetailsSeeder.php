<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class UpdateProductDetailsSeeder extends Seeder
{
    public function run()
    {
        $productDetails = [
            // CPU Intel
            1 => [
                'description' => 'Intel Core i9-13900KS - Processor high-end tới 6.0 GHz. 24 cores, 32 threads. TDP 150W. Socket LGA1700. Hiệu năng tuyệt vời cho gaming, streaming, content creation.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&q=80',
            ],
            2 => [
                'description' => 'Intel Core i5-13400F - 10 cores (6P+4E), 16 threads. Hiệu năng tốt cho gaming và ứng dụng đa luồng. Không có iGPU. TDP 65W. Giá cả hợp lý.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&q=80',
            ],
            // CPU AMD
            3 => [
                'description' => 'AMD Ryzen 7 7700X - 8 cores, 16 threads. Socket AM5. Tốc độ lên tới 5.4 GHz. TDP 105W. Hiệu năng mạnh mẽ cho gaming và workstation.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&q=80',
            ],
            4 => [
                'description' => 'AMD Ryzen 5 5600X - 6 cores, 12 threads. Socket AM4. Tốc độ 4.6 GHz boost. TDP 65W. Phổ biến, giá rẻ, hiệu năng ổn định.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=800&q=80',
            ],
            // GPU NVIDIA
            5 => [
                'description' => 'NVIDIA RTX 4090 - GPU top hiệu năng. 16384 CUDA cores, 24GB GDDR6X. Dành cho 4K gaming, AI, professional workloads. PCIe 4.0.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587829191301-a0f19eaa4cfe?w=800&q=80',
            ],
            6 => [
                'description' => 'NVIDIA RTX 4060 Ti - GPU entry-level powerful. 8GB GDDR6, 2535 CUDA cores. Tốt cho 1440p gaming, hiệu năng-giá cả hợp lý.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587829191301-a0f19eaa4cfe?w=800&q=80',
            ],
            // GPU AMD
            7 => [
                'description' => 'AMD Radeon RX 7900 XTX - 24GB GDDR6. Cạnh tranh với RTX 4090. Tốt cho 4K gaming, ray tracing, RDNA 3 architecture.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587829191301-a0f19eaa4cfe?w=800&q=80',
            ],
            8 => [
                'description' => 'AMD Radeon RX 7600 - 16GB GDDR6. Entry-level GPU. Tốt cho esports, 1080p gaming. Tiết kiệm điện.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587829191301-a0f19eaa4cfe?w=800&q=80',
            ],
            // RAM DDR5
            9 => [
                'description' => 'Kingston Fury Beast DDR5 32GB (2x16GB) 5600MHz - Tốc độ cao, CAS 28. Tích hợp tản nhiệt. Hỗ trợ EXPO/DOCP.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1584771869002-87e5e37a69f1?w=800&q=80',
            ],
            10 => [
                'description' => 'Corsair Vengeance DDR5 64GB (2x32GB) 6000MHz - Bộ nhớ cao cấp. XMP 3.0 profiles. RGB lighting. Hiệu năng cao.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1584771869002-87e5e37a69f1?w=800&q=80',
            ],
            // RAM DDR4
            11 => [
                'description' => 'G.Skill Trident Z RGB DDR4 32GB (2x16GB) 3600MHz - DDR4 giá cả hợp lý. Hiệu năng tốt. RGB có thể tắt.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1584771869002-87e5e37a69f1?w=800&q=80',
            ],
            12 => [
                'description' => 'Crucial Ballistix DDR4 16GB (2x8GB) 3200MHz - RAM ngân sách. Ổn định, đáng tin cậy cho gaming và workstation.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1584771869002-87e5e37a69f1?w=800&q=80',
            ],
            // Storage NVMe
            13 => [
                'description' => 'Samsung 980 Pro NVMe 1TB - PCIe 4.0. Tốc độ đọc lên tới 7100 MB/s. Chuyên cho gaming, 4K video editing.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587831990051-3e4be014cb13?w=800&q=80',
            ],
            14 => [
                'description' => 'WD Black SN850X NVMe 2TB - PCIe 4.0. Tốc độ 7100 MB/s. Heatsink tích hợp. Gaming killer SSD.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587831990051-3e4be014cb13?w=800&q=80',
            ],
            // Storage SATA
            15 => [
                'description' => 'Crucial MX500 SSD 1TB - SATA 3.0. Tốc độ 560 MB/s. Độ bền cao. Giá cả rẻ, phù hợp storage thứ cấp.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587831990051-3e4be014cb13?w=800&q=80',
            ],
            16 => [
                'description' => 'Samsung 870 EVO SSD 2TB - SATA 3.0. Tốc độ 560 MB/s. V-NAND 3D. Đáng tin cậy cho backup, storage.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1587831990051-3e4be014cb13?w=800&q=80',
            ],
            // Motherboard Intel
            17 => [
                'description' => 'ASUS ROG Strix Z790-E Gaming Wifi - Intel LGA1700. PCIe 5.0. Thiết kế cao cấp. Wi-Fi 6E. Tích hợp Thunderbolt 4.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            18 => [
                'description' => 'MSI MPG B760 EDGE Wifi - Intel LGA1700. PCIe 5.0 support. Giá cả hợp lý. Tốt cho mid-range build.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            // Motherboard AMD
            19 => [
                'description' => 'ASUS ROG Strix X870-E - AMD Socket AM5. PCIe 5.0. Cao cấp, đầy đủ tính năng. Hỗ trợ 3D V-Cache.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            20 => [
                'description' => 'MSI MPG B850 Edge Wifi - AMD Socket AM5. Cân bằng tốt giữa tính năng và giá cả. VRM mạnh mẽ.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            // PSU
            21 => [
                'description' => 'Corsair RM1000e Gold Modular 1000W - 80+ Gold certified. Modular cables. Tốt cho high-end system.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            22 => [
                'description' => 'EVGA SuperNOVA G6 850W - 80+ Gold. Fully modular. Compact size. Tốt cho midrange đến high-end.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            // Case
            23 => [
                'description' => 'Lian Li Lancool 216 RGB - Mid-tower. 2 pre-installed RGB fan. Tempered glass. Good airflow. Giá rẻ.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            24 => [
                'description' => 'Corsair 5000T RGB - Premium mid-tower. 3 pre-installed RGB fan. Excellent build quality. Cable management tốt.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1591290630903-fa8a5307dc88?w=800&q=80',
            ],
            // Cooler CPU Air
            25 => [
                'description' => 'Noctua NH-D15S - High-end air cooler. 2 PWM fans. Tương thích với LGA1700, AM5. Ít tiếng, hiệu quả.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1599849229973-2b2003cdc4d5?w=800&q=80',
            ],
            26 => [
                'description' => 'be quiet! Dark Rock Pro 4 - Premium air cooler. Tốt cho overclocking. Đen sang trọng. Tương thích rộng.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1599849229973-2b2003cdc4d5?w=800&q=80',
            ],
            // Cooler CPU Liquid
            27 => [
                'description' => 'NZXT Kraken X73 - 360mm AIO. Pump RGB, 3 fan RGB. Display trên pump. Tối ưu cho overclocking.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1599849229973-2b2003cdc4d5?w=800&q=80',
            ],
            28 => [
                'description' => 'Corsair H150i Elite - 360mm AIO. Pre-installed RGB fans. Tương thích AM5, LGA1700. Hiệu năng tốt.',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1599849229973-2b2003cdc4d5?w=800&q=80',
            ],
        ];

        foreach ($productDetails as $id => $details) {
            Product::where('id', $id)->update($details);
        }

        $this->command->info('Product details updated successfully!');
    }
}
