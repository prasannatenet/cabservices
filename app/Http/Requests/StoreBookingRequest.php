<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'customer_whatsapp' => 'nullable|string|max:20',

            'pickup_city_id' => 'required|exists:cities,id',
            'pickup_location' => 'required|string|max:255',
            'drop_city_id' => 'required|exists:cities,id',
            'drop_location' => 'required|string|max:255',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|date_format:H:i',
            'drop_date' => 'nullable|date|after_or_equal:pickup_date',
            'drop_time' => 'nullable|date_format:H:i',
            'passengers' => 'required|integer|min:1',
            'service_type_id' => 'required|exists:service_types,id',

            'vehicle_id' => 'required|exists:vehicles,id',
            'vehicle_reference' => 'nullable|string|max:255',
        ];
    }
}
