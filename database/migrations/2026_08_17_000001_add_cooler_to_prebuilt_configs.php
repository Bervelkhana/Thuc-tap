<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $config = \App\Models\PrebuiltConfig::where('slug', 'zzz')->first();

        if ($config) {
            $existingCooler = \App\Models\PrebuiltConfigProduct::where('prebuilt_config_id', $config->id)
                ->whereHas('product', function ($q) {
                    $q->whereHas('category', function ($q2) {
                        $q2->where('name', 'COOLER');
                    });
                })
                ->exists();

            if (!$existingCooler) {
                \App\Models\PrebuiltConfigProduct::create([
                    'prebuilt_config_id' => $config->id,
                    'product_id' => 59,
                    'quantity' => 1,
                ]);
            }
        }
    }

    public function down(): void
    {
        $config = \App\Models\PrebuiltConfig::where('slug', 'zzz')->first();

        if ($config) {
            \App\Models\PrebuiltConfigProduct::where('prebuilt_config_id', $config->id)
                ->where('product_id', 59)
                ->delete();
        }
    }
};
