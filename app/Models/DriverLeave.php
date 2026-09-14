<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverLeave extends Model
{
    protected $fillable = [
        'driver_id',
        'start_date',
        'end_date',
        'reason',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
