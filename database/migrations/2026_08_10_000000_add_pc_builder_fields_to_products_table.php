<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add PC-related columns to products table for compatibility checking
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Brand information
            $table->string('brand')->nullable()->after('name')->comment('Brand: Intel, AMD, NVIDIA, Corsair, etc.');
            
            // Socket/Interface (for CPU & Mainboard)
            $table->string('socket_type')->nullable()->after('brand')->comment('LGA1700, AM5, AM4, DDR4, DDR5, PCIe 4.0, etc.');
            
            // Chipset (for Mainboard)
            $table->string('chipset')->nullable()->after('socket_type')->comment('H610, Z790, B650, X870, etc.');
            
            // Platform (Intel vs AMD)
            $table->enum('platform', ['intel', 'amd', 'universal', 'other'])->nullable()->after('chipset')->comment('CPU/Mainboard platform');
            
            // Tier (Performance Level)
            $table->enum('tier', ['entry', 'mid', 'high', 'ultra'])->nullable()->after('platform')->comment('Entry=Tier3, Mid=Tier2, High=Tier1, Ultra=Flagship');
            
            // TDP (Thermal Design Power) in Watts
            $table->unsignedInteger('tdp')->nullable()->after('tier')->comment('Power consumption: CPU/GPU TDP in watts');
            
            // Memory Type (for RAM)
            $table->string('memory_type')->nullable()->after('tdp')->comment('DDR4, DDR5, LPDDR5, etc.');
            
            // Memory Speed (for RAM)
            $table->unsignedInteger('memory_speed')->nullable()->after('memory_type')->comment('RAM speed in MHz: 3200, 3600, 5600, etc.');
            
            // Indices for performance
            $table->index('brand');
            $table->index('socket_type');
            $table->index('platform');
            $table->index('tier');
            $table->index('memory_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['brand']);
            $table->dropIndex(['socket_type']);
            $table->dropIndex(['platform']);
            $table->dropIndex(['tier']);
            $table->dropIndex(['memory_type']);
            
            $table->dropColumn([
                'brand',
                'socket_type',
                'chipset',
                'platform',
                'tier',
                'tdp',
                'memory_type',
                'memory_speed',
            ]);
        });
    }
};
