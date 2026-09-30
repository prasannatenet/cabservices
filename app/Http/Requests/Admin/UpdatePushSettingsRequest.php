<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePushSettingsRequest extends FormRequest
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
            'push_enabled' => ['nullable', 'boolean'],
            'push_notify_on_accept' => ['nullable', 'boolean'],
            'push_notify_on_reject' => ['nullable', 'boolean'],
        ];
    }
}
