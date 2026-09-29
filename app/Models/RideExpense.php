<?php

namespace App\Models;

use App\Enums\RideExpenseCategory;
use Database\Factories\RideExpenseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideExpense extends Model
{
    /** @use HasFactory<RideExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'booking_id', 'driver_id', 'category', 'amount',
        'bill_number', 'bill_photo', 'notes', 'spent_on',
    ];

    protected function casts(): array
    {
        return [
            'category' => RideExpenseCategory::class,
            'amount' => 'decimal:2',
            'spent_on' => 'date',
        ];
    }

    /**
     * Public URL of the uploaded bill photo, or null when none was uploaded.
     */
    public function billPhotoUrl(): ?string
    {
        return $this->bill_photo ? asset('storage/'.$this->bill_photo) : null;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
