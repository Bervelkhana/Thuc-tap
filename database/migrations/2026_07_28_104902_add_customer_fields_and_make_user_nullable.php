<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add customer fields using raw SQL
        DB::statement('ALTER TABLE orders ADD COLUMN customer_name VARCHAR(255) AFTER payment_method');
        DB::statement('ALTER TABLE orders ADD COLUMN customer_email VARCHAR(255) AFTER customer_name');
        DB::statement('ALTER TABLE orders ADD COLUMN customer_phone VARCHAR(20) AFTER customer_email');
        DB::statement('ALTER TABLE orders ADD COLUMN delivery_address TEXT AFTER customer_phone');
        DB::statement('ALTER TABLE orders ADD COLUMN notes TEXT AFTER delivery_address');
        
        // Drop foreign key and make user_id nullable
        DB::statement('ALTER TABLE orders DROP FOREIGN KEY orders_user_id_foreign');
        DB::statement('ALTER TABLE orders MODIFY user_id BIGINT UNSIGNED NULLABLE');
    }

    public function down()
    {
        DB::statement('ALTER TABLE orders DROP COLUMN customer_name');
        DB::statement('ALTER TABLE orders DROP COLUMN customer_email');
        DB::statement('ALTER TABLE orders DROP COLUMN customer_phone');
        DB::statement('ALTER TABLE orders DROP COLUMN delivery_address');
        DB::statement('ALTER TABLE orders DROP COLUMN notes');
        
        DB::statement('ALTER TABLE orders MODIFY user_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }
};
