<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('snapshot')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('snapshot');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('snapshot')->nullable()->after('price');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['snapshot', 'cancelled_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('snapshot');
        });
    }
};
