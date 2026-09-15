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
        Schema::table('service_types', function (Blueprint $table) {
            // A null city_id means the service is global (created by the admin).
            $table->foreignId('city_id')
                ->nullable()
                ->after('id')
                ->constrained('cities')
                ->nullOnDelete();

            // Audit trail: which associate (or admin) created the service.
            $table->foreignId('created_by')
                ->nullable()
                ->after('city_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
