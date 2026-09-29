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
        // How a driver is engaged. Existing drivers are treated as permanent so
        // they keep a sensible type before the admin has reviewed them.
        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'driver_type')) {
                $table->string('driver_type')->default('Permanent')->after('status')->index();
            }

            // Only the column matching the driver type is used: monthly_salary
            // for permanent drivers, per_day_salary for per day drivers.
            if (! Schema::hasColumn('drivers', 'monthly_salary')) {
                $table->decimal('monthly_salary', 12, 2)->nullable()->after('driver_type');
            }

            if (! Schema::hasColumn('drivers', 'per_day_salary')) {
                $table->decimal('per_day_salary', 12, 2)->nullable()->after('monthly_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['driver_type', 'monthly_salary', 'per_day_salary'],
                fn (string $column) => Schema::hasColumn('drivers', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
