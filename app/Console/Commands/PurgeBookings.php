<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Empties the ride history so the project can be handed over with a clean slate.
 *
 * A booking is not a row on its own: it carries a customer's name, phone and
 * address, a status trail, the assignments it produced, the money the driver
 * spent on it, and photographs of the odometer and the fuel receipts. Removing
 * only the booking row would leave the customer's details behind in the history
 * tables, and would leave real photographs on disk, so the whole trail goes
 * with it.
 *
 * What is deliberately kept: the admin and staff accounts, the fleet, the
 * drivers, the cities, the categories and the service types. Only the rides and
 * the customer accounts that were created to follow them are removed.
 *
 * The bookings' side effects on the fleet are undone too. The booking flow owns
 * the "Assigned", "On Trip" and "Booked" statuses and hands them back when a
 * ride ends, so once the rides are gone nothing would ever hand them back and
 * those drivers and vehicles would stay marked as busy forever.
 */
#[Signature('app:purge-bookings {--force : Run without the production confirmation} {--dry-run : Report what would be removed without touching anything}')]
#[Description('Delete every booking, its expenses, its history and the customer accounts that followed them')]
class PurgeBookings extends Command
{
    /**
     * The upload directories that hold nothing but booking artefacts. The driver
     * and vehicle folders beside them belong to records that are being kept.
     *
     * @var list<string>
     */
    private const BOOKING_UPLOAD_DIRECTORIES = [
        'trips/odometer',
        'trips/expenses',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if (! $this->confirmProductionRun($dryRun)) {
            return self::FAILURE;
        }

        if (Booking::query()->count() === 0) {
            $this->components->info('There are no bookings to remove.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->reportWhatWouldGo();

            return self::SUCCESS;
        }

        $removedFiles = $this->purge();

        $this->reportWhatWent($removedFiles);

        return self::SUCCESS;
    }

    /**
     * Refuse to run against a production database unless it was asked for
     * explicitly, because this cannot be undone once the rows are gone.
     */
    private function confirmProductionRun(bool $dryRun): bool
    {
        if (! app()->environment('production') || $this->option('force') || $dryRun) {
            return true;
        }

        $this->components->error(
            'This deletes every booking for good. Take a database backup, then re-run with --force to confirm.'
        );

        return false;
    }

    // __NEXT__
}
