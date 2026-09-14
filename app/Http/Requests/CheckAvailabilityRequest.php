<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
            'vehicle_preference' => 'nullable|string|max:255',
        ];
    }
}
