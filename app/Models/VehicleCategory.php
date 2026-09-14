<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'default_seating_capacity',
        'image',
        'status',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }
}
