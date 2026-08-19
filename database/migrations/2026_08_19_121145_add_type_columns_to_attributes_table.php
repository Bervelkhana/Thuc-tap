<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->string('type')->default('string')->after('code');
            $table->boolean('is_required')->default(false)->after('type');
        });
    }

    public function down()
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn(['code', 'type', 'is_required']);
        });
    }
};
