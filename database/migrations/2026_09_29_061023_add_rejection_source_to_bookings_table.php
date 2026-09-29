<?php

use App\Enums\AssignmentResponseStatus;
use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The remark BookingService::rejectBooking() writes when an admin rejects a
     * ride. Matched loosely so rows written before this column existed resolve
     * to the admin too; the driver's own remark ('Booking rejected by the
     * assigned driver.') never matches it.
     */
    private const ADMIN_REJECTION_REMARK = 'Booking rejected: %';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('rejection_source')->nullable()->after('rejection_reason');
        });

        $this->backfillRejectionSource();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('rejection_source');
        });
    }

    /**
     * Label the rides that already sit in the Rejected state. A ride counts as
     * driver rejected when its newest driver refusal happened after the last
     * admin rejection, because that refusal is what left the booking rejected.
     */
    private function backfillRejectionSource(): void
    {
        DB::table('bookings')
            ->where('status', BookingStatus::REJECTED->value)
            ->chunkById(100, function ($bookings) {
                foreach ($bookings as $booking) {
                    $driverRefusedAt = DB::table('driver_assignments')
                        ->where('booking_id', $booking->id)
                        ->whereIn('response_status', [
                            AssignmentResponseStatus::Rejected->value,
                            AssignmentResponseStatus::AutoRejected->value,
                        ])
                        ->max('responded_at');

                    $adminRejectedAt = DB::table('booking_status_histories')
                        ->where('booking_id', $booking->id)
                        ->where('new_status', BookingStatus::REJECTED->value)
                        ->where('remarks', 'like', self::ADMIN_REJECTION_REMARK)
                        ->max('created_at');

                    $rejectedByDriver = $driverRefusedAt !== null
                        && ($adminRejectedAt === null || $driverRefusedAt >= $adminRejectedAt);

                    DB::table('bookings')
                        ->whereKey($booking->id)
                        ->update([
                            'rejection_source' => $rejectedByDriver
                                ? RejectionSource::Driver->value
                                : RejectionSource::Admin->value,
                        ]);
                }
            });
    }
};
