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
        Schema::table('vehicles', function (Blueprint $table) {
            // The associate this vehicle belongs to, or null when the admin
            // created it himself. This is what decides who may manage the
            // vehicle: the pickup city no longer hands a vehicle to whichever
            // associate happens to manage that city.
            $table->foreignId('associate_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Vehicles that already existed were created either by the admin or by
        // an associate, and "created_by" records exactly that, so ownership is
        // backfilled from it. Only an associate can own a vehicle, so anything
        // created by another role stays with the admin.
        DB::table('vehicles')
            ->whereIn('created_by', DB::table('users')->where('role', 'associate')->select('id'))
            ->update(['associate_id' => DB::raw('created_by')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('associate_id');
        });
    }
};
