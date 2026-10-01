<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TestWebPushNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * Sends a test desktop notification to an account so a broken push subscription
 * can be told apart from the booking flow that normally raises the push.
 *
 * Every subscription's delivery report is printed, and a rejection is also
 * written to the log by the listener registered in AppServiceProvider.
 */
#[Signature('push:test {user : Username or id of the account to push to}')]
#[Description('Send a test desktop notification to an account and report the result')]
class SendTestPushNotification extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(WebPushChannel $channel): int
    {
        $needle = (string) $this->argument('user');

        $user = User::query()
            ->where('username', $needle)
            ->orWhere('id', $needle)
            ->first();

        if (! $user) {
            $this->error("No account found for [{$needle}].");

            return self::FAILURE;
        }

        $subscriptions = $user->pushSubscriptions()->count();

        if ($subscriptions === 0) {
            $this->error("{$user->name} has no push subscriptions.");
            $this->line('Open the panels in a supported browser, click "Enable notifications", allow the prompt, then run this again.');

            return self::FAILURE;
        }

        $this->info("Sending a test push to {$subscriptions} subscription(s) for {$user->name}...");

        $reports = $channel->send($user, new TestWebPushNotification);

        $failed = 0;

        foreach ($reports as $report) {
            $status = $report->getResponse()?->getStatusCode();

            if ($report->isSuccess()) {
                $this->info('  OK    '.$report->getEndpoint());

                continue;
            }

            $failed++;

            $this->error('  FAIL  '.$report->getEndpoint());
            $this->error('        '.$report->getReason().($status ? " (HTTP {$status})" : ''));
        }

        if ($failed > 0) {
            $this->warn('See storage/logs/laravel.log for the logged failure.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
