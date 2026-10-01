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

            // The cities, dates and times were settled on the search page and
            // ride along as hidden fields; the review page is where the customer
            // gives the actual places: where to pick him up, the landmark, the
            // Google Maps pin and where he is going.
            'pickup_city_id' => 'nullable|exists:cities,id',
            'pickup_location' => 'required|string|max:255',
            'pickup_landmark' => 'nullable|string|max:255',
            'pickup_location_link' => 'nullable|url|max:500',
            'drop_city_id' => 'nullable|exists:cities,id',
            'drop_city' => 'nullable|string|max:255',
            'drop_location' => 'required|string|max:255',
            'pickup_date' => 'required|date|after_or_equal:today',
            'pickup_time' => 'required|date_format:H:i',
            'drop_date' => 'nullable|date|after_or_equal:pickup_date',
            'drop_time' => 'nullable|date_format:H:i',
            'passengers' => 'nullable|integer|min:1',
            'service_type_id' => 'nullable|exists:service_types,id',

            'vehicle_id' => 'required|exists:vehicles,id',
            'vehicle_reference' => 'nullable|string|max:255',
        ];
    }
}
