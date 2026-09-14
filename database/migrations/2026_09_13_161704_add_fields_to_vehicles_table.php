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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('fuel_type')->nullable()->after('seating_capacity');
            $table->boolean('has_ac')->default(true)->after('fuel_type');
            $table->integer('luggage_capacity')->nullable()->after('has_ac');
            $table->foreignId('operating_city_id')->nullable()->constrained('cities')->after('city_id');
            // Drop old vehicle_type string and add category
            $table->dropColumn('vehicle_type');
            $table->foreignId('vehicle_category_id')->nullable()->constrained('vehicle_categories')->after('model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            //
        });
    }
};
