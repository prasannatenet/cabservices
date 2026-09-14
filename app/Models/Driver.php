<?php

namespace App\Models;

use Database\Factories\DriverFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    /** @use HasFactory<DriverFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'whatsapp', 'email', 'address',
        'license_number', 'license_expiry', 'license_document', 'experience_years', 'profile_photo',
        'current_city_id', 'status',
    ];

    public function currentCity()
    {
        return $this->belongsTo(City::class, 'current_city_id');
    }

    public function leaves()
    {
        return $this->hasMany(DriverLeave::class);
    }
}
