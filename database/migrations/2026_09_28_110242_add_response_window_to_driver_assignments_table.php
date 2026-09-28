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
        // A driver has a limited window to accept or refuse a new assignment.
        // The window is stored per assignment so a late answer can be blocked
        // without re-deriving the deadline from the booking.
        Schema::table('driver_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('driver_assignments', 'response_status')) {
                $table->string('response_status')->default('Pending')->after('status');
            }

            if (! Schema::hasColumn('driver_assignments', 'response_deadline')) {
                $table->timestamp('response_deadline')->nullable()->after('response_status');
            }

            if (! Schema::hasColumn('driver_assignments', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('response_deadline');
            }

            if (! Schema::hasColumn('driver_assignments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('responded_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_assignments', function (Blueprint $table) {
            $table->dropColumn(['response_status', 'response_deadline', 'responded_at', 'rejection_reason']);
        });
    }
};
