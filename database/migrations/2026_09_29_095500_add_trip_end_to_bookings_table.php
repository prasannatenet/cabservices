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
            // Proof of the vehicle's state at the drop point: the closing
            // odometer reading and the driver's photo of the meter. The total
            // distance of the ride is the difference between the two readings.
            $table->unsignedInteger('end_odometer_km')->nullable()->after('trip_started_at');
            $table->string('end_odometer_photo')->nullable()->after('end_odometer_km');
            $table->timestamp('trip_ended_at')->nullable()->after('end_odometer_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['end_odometer_km', 'end_odometer_photo', 'trip_ended_at']);
        });
    }
};
