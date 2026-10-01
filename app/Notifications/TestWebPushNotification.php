<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * A throwaway desktop notification used by the `push:test` command.
 *
 * It exists only to exercise the web push path on demand, so a broken browser
 * subscription can be told apart from the booking flow that normally raises the
 * push.
 */
class TestWebPushNotification extends Notification
{
    /**
     * The channels the test travels through.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    /**
     * The operating system toast shown on the desktop.
     */
    public function toWebPush(object $notifiable): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Test notification')
            ->body('Desktop notifications are working on this browser.')
            ->data(['url' => url('/')]);
    }
}
