<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\Events\NotificationFailed;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     *
     * If the admin has configured an SMTP host in Settings, applyMailConfig()
     * overrides the .env mail values for the lifetime of every request so all
     * outgoing mail (booking notifications, test emails, tracking links, …)
     * uses those credentials instead of the default "log" mailer.
     */
    public function boot(): void
    {
        try {
            if (Schema::hasTable('settings')) {
                Setting::applyMailConfig();

                // The company name is the brand shown in every layout, page
                // title and email, so it drives the application name for the
                // lifetime of the request.
                $companyName = Setting::string('company.name');

                if ($companyName !== '') {
                    Config::set('app.name', $companyName);
                }
            }
        } catch (\Exception $e) {
            // Ignore during migrations or when the table is not yet created.
        }

        // The web push package dispatches this event when a push service rejects
        // a message and then silently swallows the failure. Logging it is the
        // only way to see why a desktop notification never arrived, for example
        // a stale subscription or a VAPID key mismatch.
        Event::listen(NotificationFailed::class, function (NotificationFailed $event): void {
            Log::error('Web push notification was not delivered', [
                'subscription_id' => $event->subscription->getKey(),
                'endpoint' => $event->subscription->endpoint,
                'reason' => $event->report->getReason(),
                'status_code' => $event->report->getResponse()?->getStatusCode(),
                'expired' => $event->report->isSubscriptionExpired(),
            ]);
        });
    }
}
