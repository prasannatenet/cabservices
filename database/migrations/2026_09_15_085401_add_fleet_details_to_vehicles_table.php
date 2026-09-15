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
        // Drop single-path photo columns (added by an earlier hotfix) so they can
        // be recreated as JSON columns supporting multiple photos.
        Schema::table('vehicles', function (Blueprint $table) {
            foreach (['insurance_photo', 'rc_photo'] as $column) {
                if (Schema::hasColumn('vehicles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'price_per_km')) {
                $table->decimal('price_per_km', 10, 2)->nullable()->after('luggage_capacity');
            }

            if (! Schema::hasColumn('vehicles', 'price_per_day')) {
                $table->decimal('price_per_day', 10, 2)->nullable()->after('price_per_km');
            }

            if (! Schema::hasColumn('vehicles', 'fixed_km_per_day')) {
                $table->unsignedInteger('fixed_km_per_day')->nullable()->after('price_per_day');
            }

            if (! Schema::hasColumn('vehicles', 'insurance_photo')) {
                $table->json('insurance_photo')->nullable()->after('fixed_km_per_day');
            }

            if (! Schema::hasColumn('vehicles', 'rc_photo')) {
                $table->json('rc_photo')->nullable()->after('insurance_photo');
            }

            if (! Schema::hasColumn('vehicles', 'last_service_date')) {
                $table->date('last_service_date')->nullable()->after('rc_photo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['price_per_km', 'price_per_day', 'fixed_km_per_day', 'insurance_photo', 'rc_photo', 'last_service_date']);
        });
    }
};
