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
            // The first page picks the pickup city, writes the drop city - either
            // one of ours or any place at all - and gives the dates. The
            // addresses themselves are asked for later, on the review page.
            'pickup_city_id' => 'required|exists:cities,id',
            'pickup_location' => 'nullable|string|max:255',
            'drop_city' => 'required|string|max:255',
            'drop_city_id' => 'nullable|exists:cities,id',
            'drop_location' => 'nullable|string|max:255',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|date_format:H:i',
            'drop_date' => 'nullable|date|after_or_equal:pickup_date',
            'drop_time' => 'nullable|date_format:H:i',
            'passengers' => 'nullable|integer|min:1',
            'service_type_id' => 'nullable|exists:service_types,id',
            'vehicle_preference' => 'nullable|string|max:255',
        ];
    }
}
