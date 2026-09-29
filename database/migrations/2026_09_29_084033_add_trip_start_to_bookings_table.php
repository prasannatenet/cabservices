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
        Schema::table('bookings', function (Blueprint $table) {
            // Proof of the vehicle's state at pickup: the odometer reading the
            // driver typed in and his photo of the meter, captured when he
            // starts the ride.
            $table->unsignedInteger('start_odometer_km')->nullable()->after('rejection_source');
            $table->string('start_odometer_photo')->nullable()->after('start_odometer_km');
            $table->timestamp('trip_started_at')->nullable()->after('start_odometer_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['start_odometer_km', 'start_odometer_photo', 'trip_started_at']);
        });
    }
};
