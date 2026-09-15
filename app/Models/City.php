<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'state', 'country', 'latitude', 'longitude', 'status', 'description',
    ];

    public function nearbyCities()
    {
        return $this->belongsToMany(City::class, 'city_nearby', 'city_id', 'nearby_city_id')
            ->withPivot('priority', 'is_enabled');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'city_id');
    }

    public function operatingVehicles()
    {
        return $this->hasMany(Vehicle::class, 'operating_city_id');
    }

    public function drivers()
    {
        return $this->hasMany(Driver::class, 'current_city_id');
    }
}
