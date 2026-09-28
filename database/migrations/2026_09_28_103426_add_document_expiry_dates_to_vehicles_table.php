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
        // Every vehicle carries an RC and an insurance policy. Both are tracked
        // with their issue and expiry date so the panel can warn the admin
        // before a document lapses.
        Schema::table('vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('vehicles', 'rc_issue_date')) {
                $table->date('rc_issue_date')->nullable()->after('rc_photo');
            }

            if (! Schema::hasColumn('vehicles', 'rc_expiry_date')) {
                $table->date('rc_expiry_date')->nullable()->after('rc_issue_date');
            }

            if (! Schema::hasColumn('vehicles', 'insurance_issue_date')) {
                $table->date('insurance_issue_date')->nullable()->after('insurance_photo');
            }

            if (! Schema::hasColumn('vehicles', 'insurance_expiry_date')) {
                $table->date('insurance_expiry_date')->nullable()->after('insurance_issue_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn(['rc_issue_date', 'rc_expiry_date', 'insurance_issue_date', 'insurance_expiry_date']);
        });
    }
};
