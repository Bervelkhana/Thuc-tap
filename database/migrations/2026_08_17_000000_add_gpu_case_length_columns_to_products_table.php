<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add GPU length and Case max GPU length columns to products table
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('gpu_length_mm')->nullable()->after('memory_speed')->comment('GPU length in mm for VGA products');
            $table->unsignedInteger('max_gpu_length_mm')->nullable()->after('gpu_length_mm')->comment('Max GPU length supported by Case products');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['gpu_length_mm', 'max_gpu_length_mm']);
        });
    }
};
