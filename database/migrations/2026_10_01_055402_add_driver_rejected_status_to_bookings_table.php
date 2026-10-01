<?php

use App\Enums\BookingStatus;
use App\Enums\RejectionSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Driver Rejected" used to be a label painted over the Rejected status using
 * the rejection_source column, so the database itself could not tell a driver's
 * refusal from an admin's rejection. It is a status in its own right now, so
 * the rides a driver refused are moved onto it.
 *
 * The status column is already a varchar, so nothing about the schema changes
 * here: only the stored value does.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->moveBookingsTo(
            from: BookingStatus::REJECTED,
            to: BookingStatus::DRIVER_REJECTED,
            onlyWhereSource: RejectionSource::Driver,
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->moveBookingsTo(
            from: BookingStatus::DRIVER_REJECTED,
            to: BookingStatus::REJECTED,
            onlyWhereSource: RejectionSource::Driver,
        );
    }

    /**
     * Rewrite the bookings sitting in one status into another, and keep their
     * history in step so the trail reads the same way the booking does.
     *
     * The history is rewritten for the whole booking rather than per row,
     * because the same ride can be rejected more than once: the first time by
     * the driver and later by the admin. Only the rows carrying the old status
     * are touched, so a ride the admin rejected keeps its own status.
     *
     * @param  RejectionSource|null  $onlyWhereSource  Restricts the move to
     *                                                 bookings rejected by this
     *                                                 source, or to every
     *                                                 booking when null.
     */
    private function moveBookingsTo(
        BookingStatus $from,
        BookingStatus $to,
        ?RejectionSource $onlyWhereSource = null,
    ): void {
        $query = DB::table('bookings')->where('status', $from->value);

        if ($onlyWhereSource !== null) {
            $query->where('rejection_source', $onlyWhereSource->value);
        }

        $query->chunkById(100, function ($bookings) use ($from, $to): void {
            foreach ($bookings as $booking) {
                DB::table('bookings')->whereKey($booking->id)->update(['status' => $to->value]);

                // Each column is rewritten on its own rather than both at once, because a
                // row only carries the old status in one of them: rewriting both
                // would overwrite the status the other column legitimately
                // holds.
                DB::table('booking_status_histories')
                    ->where('booking_id', $booking->id)
                    ->where('new_status', $from->value)
                    ->update(['new_status' => $to->value]);

                DB::table('booking_status_histories')
                    ->where('booking_id', $booking->id)
                    ->where('old_status', $from->value)
                    ->update(['old_status' => $to->value]);
            }
        });
    }
};
