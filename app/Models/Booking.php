<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_number',        'customer_name', 'customer_phone', 'customer_email', 'customer_whatsapp',
        'pickup_city_id', 'pickup_location', 'drop_city_id', 'drop_location',
        'pickup_date', 'pickup_time', 'drop_date', 'drop_time', 'passengers',
        'service_type_id', 'vehicle_id', 'driver_id', 'vehicle_reference',
        'status', 'rejection_reason', 'admin_notes',
    ];

    protected $casts = [
        'status' => BookingStatus::class,
        'pickup_date' => 'date',
        'drop_date' => 'date',
    ];

    public function pickupCity()
    {
        return $this->belongsTo(City::class, 'pickup_city_id');
    }

    public function dropCity()
    {
        return $this->belongsTo(City::class, 'drop_city_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function statusHistory()
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function driverAssignments()
    {
        return $this->hasMany(DriverAssignment::class);
    }

    public function driverAssignment()
    {
        return $this->hasOne(DriverAssignment::class)->latestOfMany();
    }
}
