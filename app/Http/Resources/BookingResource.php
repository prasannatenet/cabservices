<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_number' => $this->booking_number,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'customer_whatsapp' => $this->customer_whatsapp,

            'pickup_location' => $this->pickup_location,
            'drop_location' => $this->drop_location,
            'pickup_date' => $this->pickup_date,
            'pickup_time' => $this->pickup_time,
            'passengers' => $this->passengers,

            'pickup_city' => new CityResource($this->whenLoaded('pickupCity')),
            'drop_city' => new CityResource($this->whenLoaded('dropCity')),
            'service_type' => new ServiceTypeResource($this->whenLoaded('serviceType')),
            'vehicle' => new VehicleResource($this->whenLoaded('vehicle')),

            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at,
        ];
    }
}
