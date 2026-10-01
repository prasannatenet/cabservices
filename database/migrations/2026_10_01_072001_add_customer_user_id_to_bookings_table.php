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
            // The account the customer logs in with to follow this ride. It is
            // created only once the ride is confirmed, so it stays nullable for
            // requests that were never confirmed and for rides booked before
            // customer accounts existed.
            $table->foreignId('customer_user_id')
                ->nullable()
                ->after('customer_whatsapp')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_user_id');
        });
    }
};
