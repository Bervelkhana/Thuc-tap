<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop foreign key constraint
        DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_user_id_foreign');
        
        // Make user_id nullable and remove constraint
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        });
    }
};
