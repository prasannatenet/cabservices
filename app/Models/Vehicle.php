<?php

namespace App\Models;

use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name', 'model', 'vehicle_category_id', 'registration_number',
        'reference_number', 'seating_capacity', 'city_id', 'operating_city_id',
        'image', 'features', 'status', 'fuel_type', 'has_ac', 'luggage_capacity',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function operatingCity()
    {
        return $this->belongsTo(City::class, 'operating_city_id');
    }

    public function category()
    {
        return $this->belongsTo(VehicleCategory::class, 'vehicle_category_id');
    }

    public function images()
    {
        return $this->hasMany(VehicleImage::class);
    }

    public function getVehicleTypeAttribute()
    {
        return $this->category ? $this->category->name : 'Standard';
    }
}
