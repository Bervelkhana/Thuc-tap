<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->string('value_string')->nullable()->after('value');
            $table->integer('value_number')->nullable()->after('value_string');
            $table->boolean('value_boolean')->nullable()->after('value_number');
            $table->dateTime('value_date')->nullable()->after('value_boolean');
            $table->json('value_json')->nullable()->after('value_date');
        });
    }

    public function down()
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropColumn([
                'value_string',
                'value_number',
                'value_boolean',
                'value_date',
                'value_json',
            ]);
        });
    }
};
