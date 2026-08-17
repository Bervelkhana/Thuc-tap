<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'CPU', 'slug' => 'cpu'],
            ['name' => 'MAIN', 'slug' => 'main'],
            ['name' => 'RAM', 'slug' => 'ram'],
            ['name' => 'SSD', 'slug' => 'ssd'],
            ['name' => 'VGA', 'slug' => 'vga'],
            ['name' => 'CASE', 'slug' => 'case'],
            ['name' => 'COOLER', 'slug' => 'cooler'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
