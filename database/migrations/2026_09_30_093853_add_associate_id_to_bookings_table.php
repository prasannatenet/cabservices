<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // The associate the admin handed this ride to, or null while the
            // admin runs it himself. A ride is never attached to an associate
            // because of the city it starts in: only an explicit assignment of
            // that associate's driver or vehicle sets this.
            $table->foreignId('associate_id')
                ->nullable()
                ->after('service_type_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('associate_id');
        });

        // Rides already running for an associate-owned driver belong to that
        // associate, so his history is not lost. Anything driven by an
        // admin-created driver stays with the admin.
        DB::table('bookings')
            ->whereNull('associate_id')
            ->whereNotNull('driver_id')
            ->whereIn('driver_id', DB::table('drivers')->whereNotNull('associate_id')->select('id'))
            ->update([
                'associate_id' => DB::table('drivers')
                    ->select('associate_id')
                    ->whereColumn('drivers.id', 'bookings.driver_id')
                    ->limit(1),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['associate_id']);
            $table->dropConstrainedForeignId('associate_id');
        });
    }
};
