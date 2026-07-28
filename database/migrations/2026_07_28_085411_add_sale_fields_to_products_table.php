<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_on_sale')->default(false)->after('price');
            $table->decimal('sale_price', 12, 2)->nullable()->after('is_on_sale');
            $table->unsignedTinyInteger('discount_percentage')->default(0)->after('sale_price');
            $table->dateTime('sale_start_date')->nullable()->after('discount_percentage');
            $table->dateTime('sale_end_date')->nullable()->after('sale_start_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_on_sale', 'sale_price', 'sale_start_date', 'sale_end_date']);
        });
    }
};
