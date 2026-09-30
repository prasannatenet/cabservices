<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMailSettingsRequest extends FormRequest
{
    /**
     * The route already sits behind the admin role middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'mail_enabled' => ['nullable', 'boolean'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:255'],
            'mail_notify_on_booking_request' => ['nullable', 'boolean'],
            'mail_notify_customer' => ['nullable', 'boolean'],
            'mail_notify_customer_on_driver_assigned' => ['nullable', 'boolean'],
            'mail_booking_notification_recipients' => ['nullable', 'array', 'max:50'],
            'mail_booking_notification_recipients.*' => ['required', 'email', 'max:255'],
            'mail_smtp_host' => ['nullable', 'string', 'max:255'],
            'mail_smtp_port' => ['nullable', 'string', 'max:255'],
            'mail_smtp_username' => ['nullable', 'string', 'max:1024'],
            'mail_smtp_password' => ['nullable', 'string', 'max:2048'],
            'mail_smtp_encryption' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * The recipients are typed as one address per line, so the textarea value
     * is split before the per-address rules can run.
     */
    protected function prepareForValidation(): void
    {
        $recipients = $this->input('mail_booking_notification_recipients');

        if (is_string($recipients)) {
            $this->merge([
                'mail_booking_notification_recipients' => array_values(array_filter(array_map(
                    'trim',
                    preg_split('/[\r\n,]+/', $recipients) ?: []
                ), fn (string $address): bool => $address !== '')),
            ]);
        }
    }
}
