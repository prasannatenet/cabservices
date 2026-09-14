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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->string('customer_whatsapp')->nullable();
            $table->foreignId('pickup_city_id')->constrained('cities');
            $table->string('pickup_location');
            $table->foreignId('drop_city_id')->constrained('cities');
            $table->string('drop_location');
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->integer('passengers');
            $table->foreignId('service_type_id')->constrained('service_types');
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles');
            $table->foreignId('driver_id')->nullable()->constrained('drivers');
            $table->string('vehicle_reference')->nullable();
            $table->string('status')->default('Pending');
            $table->text('rejection_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
