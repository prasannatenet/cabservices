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
        // Identity details collected when a driver is onboarded. The Aadhaar
        // number is unique so the same person cannot be added twice.
        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'aadhaar_number')) {
                $table->string('aadhaar_number', 12)->nullable()->unique()->after('license_document');
            }

            if (! Schema::hasColumn('drivers', 'aadhaar_photo')) {
                $table->string('aadhaar_photo')->nullable()->after('aadhaar_number');
            }

            if (! Schema::hasColumn('drivers', 'permanent_address')) {
                $table->text('permanent_address')->nullable()->after('aadhaar_photo');
            }

            if (! Schema::hasColumn('drivers', 'current_address')) {
                $table->text('current_address')->nullable()->after('permanent_address');
            }

            if (! Schema::hasColumn('drivers', 'alternate_phone')) {
                $table->string('alternate_phone', 20)->nullable()->after('whatsapp');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'aadhaar_number', 'aadhaar_photo',
                'permanent_address', 'current_address', 'alternate_phone',
            ]);
        });
    }
};
