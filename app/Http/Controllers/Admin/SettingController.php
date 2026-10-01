<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminAccountRequest;
use App\Http\Requests\Admin\UpdateCompanySettingsRequest;
use App\Http\Requests\Admin\UpdateMailSettingsRequest;
use App\Http\Requests\Admin\UpdatePushSettingsRequest;
use App\Mail\SettingsTestMail;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'companyName' => Setting::string('company.name', (string) config('app.name')),
            'mailEnabled' => Setting::boolean('mail.enabled', true),
            'fromAddress' => Setting::string('mail.from_address', (string) config('mail.from.address')),
            'fromName' => Setting::string('mail.from_name', (string) config('mail.from.name')),
            'notifyOnBookingRequest' => Setting::boolean('mail.notify_on_booking_request', true),
            'notifyCustomer' => Setting::boolean('mail.notify_customer', true),
            'notifyCustomerOnDriverAssigned' => Setting::boolean('mail.notify_customer_on_driver_assigned', true),
            'recipients' => Setting::list('mail.booking_notification_recipients'),
            'mailer' => config('mail.default'),
            'smtpHost' => Setting::string('mail.smtp_host'),
            'smtpPort' => Setting::string('mail.smtp_port', '587'),
            'smtpUsername' => Setting::string('mail.smtp_username'),
            'smtpEncryption' => Setting::string('mail.smtp_encryption', 'tls'),
            // Password is intentionally not sent to the view to avoid exposing it.
            'smtpPasswordIsSet' => Setting::string('mail.smtp_password') !== '',
            'pushEnabled' => Setting::boolean('push.enabled', true),
            'pushNotifyOnAccept' => Setting::boolean('push.notify_on_accept', true),
            'pushNotifyOnReject' => Setting::boolean('push.notify_on_reject', true),
        ]);
    }

    public function update(UpdateMailSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $data = [
            'mail.enabled' => $request->boolean('mail_enabled'),
            'mail.from_address' => $validated['mail_from_address'] ?? config('mail.from.address'),
            'mail.from_name' => $validated['mail_from_name'] ?? config('mail.from.name'),
            'mail.notify_on_booking_request' => $request->boolean('mail_notify_on_booking_request'),
            'mail.notify_customer' => $request->boolean('mail_notify_customer'),
            'mail.notify_customer_on_driver_assigned' => $request->boolean('mail_notify_customer_on_driver_assigned'),
            'mail.booking_notification_recipients' => implode("\n", $validated['mail_booking_notification_recipients'] ?? []),
            'mail.smtp_host' => $validated['mail_smtp_host'] ?? '',
            'mail.smtp_port' => $validated['mail_smtp_port'] ?? '',
            'mail.smtp_username' => $validated['mail_smtp_username'] ?? '',
            'mail.smtp_encryption' => $validated['mail_smtp_encryption'] ?? '',
        ];

        // Preserve the existing encrypted password when the user leaves the
        // field blank — an empty submission must not wipe a stored API key.
        $newPassword = $validated['mail_smtp_password'] ?? '';

        if ($newPassword !== '') {
            $data['mail.smtp_password'] = $newPassword;
        }

        Setting::putMany($data);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Mail settings updated successfully.');
    }

    /**
     * The company name is the application brand: it is applied to the app name
     * so every layout, page title and email shows it.
     */
    public function updateCompany(UpdateCompanySettingsRequest $request): RedirectResponse
    {
        Setting::put('company.name', $request->validated()['company_name']);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Company name updated successfully.');
    }

    /**
     * Change the signed in admin's own email and, when a new password is given,
     * that password too.
     */
    public function updateAccount(UpdateAdminAccountRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $user->email = $validated['admin_email'];

        if (filled($validated['admin_password'] ?? null)) {
            $user->password = $validated['admin_password'];
        }

        $user->save();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Your admin account was updated successfully.');
    }

    /**
     * The opt-in switches for the desktop notifications pushed to the browser.
     */
    public function updatePush(UpdatePushSettingsRequest $request): RedirectResponse
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
     * Send a real message through the configured SMTP so the admin can verify
     * the credentials work before a customer relies on them.
     *
     * applyMailConfig() is called explicitly here so the test travels through
     * exactly the same path that real booking emails use.
     */
    public function sendTestMail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // Re-apply config in case the admin just saved new credentials in this
        // same request — the provider's boot() ran before the save happened.
        Setting::applyMailConfig();

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
