<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
            }
        } catch (\Exception $e) {
            // Ignore during migrations or when the table is not yet created.
        }
    }
}
