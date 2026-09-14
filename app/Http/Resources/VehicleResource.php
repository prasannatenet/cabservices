<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model' => $this->model,
            'vehicle_type' => $this->vehicle_type,
            'registration_number' => $this->registration_number,
            'reference_number' => $this->reference_number,
            'seating_capacity' => $this->seating_capacity,
            'city_id' => $this->city_id,
            'city' => new CityResource($this->whenLoaded('city')),
            'image' => $this->image ? url('storage/'.$this->image) : null,
            'features' => $this->features,
            'status' => $this->status,
        ];
    }
}
