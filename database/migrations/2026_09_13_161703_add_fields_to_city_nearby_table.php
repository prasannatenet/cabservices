<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('city_nearby', function (Blueprint $table) {
            $table->integer('priority')->default(0)->after('nearby_city_id');
            $table->boolean('is_enabled')->default(true)->after('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('city_nearby', function (Blueprint $table) {
            //
        });
    }
};
