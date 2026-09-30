<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateMailSettingsRequest;
use App\Http\Requests\Admin\UpdatePushSettingsRequest;
use App\Mail\SettingsTestMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'mailEnabled' => Setting::boolean('mail.enabled', true),
            'fromAddress' => Setting::string('mail.from_address', (string) config('mail.from.address')),
            'fromName' => Setting::string('mail.from_name', (string) config('mail.from.name')),
            'notifyOnBookingRequest' => Setting::boolean('mail.notify_on_booking_request', true),
            'notifyCustomer' => Setting::boolean('mail.notify_customer', true),
            'notifyCustomerOnDriverAssigned' => Setting::boolean('mail.notify_customer_on_driver_assigned', true),
            'recipients' => Setting::list('mail.booking_notification_recipients'),
            'mailer' => config('mail.default'),
            'pushEnabled' => Setting::boolean('push.enabled', true),
            'pushNotifyOnAccept' => Setting::boolean('push.notify_on_accept', true),
            'pushNotifyOnReject' => Setting::boolean('push.notify_on_reject', true),
        ]);
    }

    public function update(UpdateMailSettingsRequest $request)
    {
        $validated = $request->validated();

        Setting::putMany([
            'mail.enabled' => $request->boolean('mail_enabled'),
            'mail.from_address' => $validated['mail_from_address'] ?? config('mail.from.address'),
            'mail.from_name' => $validated['mail_from_name'] ?? config('mail.from.name'),
            'mail.notify_on_booking_request' => $request->boolean('mail_notify_on_booking_request'),
            'mail.notify_customer' => $request->boolean('mail_notify_customer'),
            'mail.notify_customer_on_driver_assigned' => $request->boolean('mail_notify_customer_on_driver_assigned'),
            'mail.booking_notification_recipients' => implode("\n", $validated['mail_booking_notification_recipients'] ?? []),
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Mail settings updated successfully.');
    }

    /**
     * The opt-in switches for the desktop notifications pushed to the browser.
     */
    public function updatePush(UpdatePushSettingsRequest $request)
    {
        Setting::putMany([
            'push.enabled' => $request->boolean('push_enabled'),
            'push.notify_on_accept' => $request->boolean('push_notify_on_accept'),
            'push.notify_on_reject' => $request->boolean('push_notify_on_reject'),
        ]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Desktop notification settings updated successfully.');
    }

    /**
     * Send a real message to the admin so the configuration can be verified
     * before a customer relies on it.
     */
    public function sendTestMail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        try {
            Mail::to($validated['email'])->send(new SettingsTestMail($request->user()->name));

            return redirect()->route('admin.settings.index')
                ->with('success', 'Test mail sent to '.$validated['email'].'.');
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('admin.settings.index')
                ->with('error', 'Could not send the test mail: '.$exception->getMessage());
        }
    }
}
