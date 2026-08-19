<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = [
            ['name' => 'Socket', 'code' => 'socket', 'type' => Attribute::TYPE_STRING, 'is_required' => true],
            ['name' => 'TDP', 'code' => 'tdp', 'type' => Attribute::TYPE_NUMBER, 'is_required' => false],
            ['name' => 'RAM Type', 'code' => 'memory_type', 'type' => Attribute::TYPE_STRING, 'is_required' => true],
            ['name' => 'Wattage', 'code' => 'wattage', 'type' => Attribute::TYPE_NUMBER, 'is_required' => false],
            ['name' => 'RGB', 'code' => 'rgb', 'type' => Attribute::TYPE_BOOLEAN, 'is_required' => false],
            ['name' => 'Release Date', 'code' => 'release_date', 'type' => Attribute::TYPE_DATE, 'is_required' => false],
            ['name' => 'Specifications', 'code' => 'specifications', 'type' => Attribute::TYPE_JSON, 'is_required' => false],
        ];

        foreach ($attributes as $attribute) {
            Attribute::updateOrCreate(['name' => $attribute['name']], $attribute);
        }
    }
}
