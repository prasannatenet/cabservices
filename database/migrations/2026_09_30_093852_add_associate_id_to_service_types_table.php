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
        Schema::table('service_types', function (Blueprint $table) {
            // Same ownership rule as the fleet and the drivers: a service an
            // associate created is his, one the admin created stays the admin's.
            $table->foreignId('associate_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        DB::table('service_types')
            ->whereIn('created_by', DB::table('users')->where('role', 'associate')->select('id'))
            ->update(['associate_id' => DB::raw('created_by')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('associate_id');
        });
    }
};
