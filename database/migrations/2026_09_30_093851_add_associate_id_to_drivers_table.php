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
        Schema::table('drivers', function (Blueprint $table) {
            // The associate this driver belongs to, or null when the admin
            // created him himself. Ownership, not the driver's current city,
            // decides which panel he is managed from.
            $table->foreignId('associate_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('drivers')
            ->whereIn('created_by', DB::table('users')->where('role', 'associate')->select('id'))
            ->update(['associate_id' => DB::raw('created_by')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('associate_id');
        });
    }
};
