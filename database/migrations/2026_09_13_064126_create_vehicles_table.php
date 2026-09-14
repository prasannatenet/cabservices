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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('model');
            $table->string('vehicle_type');
            $table->string('registration_number')->unique();
            $table->string('reference_number')->nullable();
            $table->integer('seating_capacity');
            $table->foreignId('city_id')->constrained('cities');
            $table->string('image')->nullable();
            $table->string('features')->nullable();
            $table->string('status')->default('Available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
