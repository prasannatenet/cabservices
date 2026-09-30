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
            $table->uuid('tracking_id')->nullable()->unique()->after('status');
            $table->decimal('current_latitude', 10, 8)->nullable()->after('tracking_id');
            $table->decimal('current_longitude', 11, 8)->nullable()->after('current_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['tracking_id', 'current_latitude', 'current_longitude']);
        });
    }
};
