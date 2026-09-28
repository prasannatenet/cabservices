<?php

namespace App\Console\Commands;

use App\Services\AssignmentResponseService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Closes the six hour response window on every ride a driver never answered.
 *
 * Scheduled in routes/console.php so unattended assignments are released for
 * reassignment instead of waiting forever.
 */
#[Signature('assignments:reject-expired')]
#[Description('Auto-reject driver assignments whose 6 hour response window has closed')]
class RejectExpiredDriverAssignments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AssignmentResponseService $responses): int
    {
        $rejected = $responses->rejectExpiredAssignments();

        if ($rejected === 0) {
            $this->info('No expired driver assignments found.');

            return self::SUCCESS;
        }

        $this->info("Auto-rejected {$rejected} expired driver assignment(s).");

        return self::SUCCESS;
    }
}
