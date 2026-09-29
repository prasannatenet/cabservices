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
            // What the finished ride costs. It is worked out from the vehicle's
            // rate card the moment the trip is closed and stored next to the
            // figures it came out of, so a later change to the rate card cannot
            // rewrite a bill that was already handed over.
            $table->unsignedSmallInteger('billed_days')->nullable()->after('trip_ended_at');
            $table->unsignedInteger('billed_included_km')->nullable()->after('billed_days');
            $table->decimal('billed_price_per_day', 10, 2)->nullable()->after('billed_included_km');
            $table->decimal('billed_price_per_km', 10, 2)->nullable()->after('billed_price_per_day');
            $table->unsignedInteger('extra_km')->nullable()->after('billed_price_per_km');
            $table->decimal('base_amount', 10, 2)->nullable()->after('extra_km');
            $table->decimal('extra_km_amount', 10, 2)->nullable()->after('base_amount');
            $table->decimal('total_amount', 10, 2)->nullable()->after('extra_km_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'billed_days', 'billed_included_km', 'billed_price_per_day', 'billed_price_per_km',
                'extra_km', 'base_amount', 'extra_km_amount', 'total_amount',
            ]);
        });
    }
};
