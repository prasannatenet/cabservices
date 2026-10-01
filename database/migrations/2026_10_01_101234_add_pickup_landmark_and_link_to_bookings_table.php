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
            // Where to meet the customer: a landmark to recognise the spot and
            // the Google Maps pin of the pickup, both given on the review page.
            $table->string('pickup_landmark', 255)->nullable()->after('pickup_location');
            $table->string('pickup_location_link', 500)->nullable()->after('pickup_landmark');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['pickup_landmark', 'pickup_location_link']);
        });
    }
};
