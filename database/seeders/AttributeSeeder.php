<?php

namespace Database\Seeders;

use App\Models\Attribute;
use Illuminate\Database\Seeder;

class AttributeSeeder extends Seeder
{
    public function run()
    {
        Attribute::create(['name' => 'Socket', 'code' => 'socket', 'type' => Attribute::TYPE_STRING, 'is_required' => true]);
        Attribute::create(['name' => 'TDP', 'code' => 'tdp', 'type' => Attribute::TYPE_NUMBER, 'is_required' => false]);
        Attribute::create(['name' => 'RAM Type', 'code' => 'memory_type', 'type' => Attribute::TYPE_STRING, 'is_required' => true]);
        Attribute::create(['name' => 'Wattage', 'code' => 'wattage', 'type' => Attribute::TYPE_NUMBER, 'is_required' => false]);
        Attribute::create(['name' => 'RGB', 'code' => 'rgb', 'type' => Attribute::TYPE_BOOLEAN, 'is_required' => false]);
        Attribute::create(['name' => 'Release Date', 'code' => 'release_date', 'type' => Attribute::TYPE_DATE, 'is_required' => false]);
        Attribute::create(['name' => 'Specifications', 'code' => 'specifications', 'type' => Attribute::TYPE_JSON, 'is_required' => false]);
    }
}
