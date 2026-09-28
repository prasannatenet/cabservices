<?php

namespace App\Models;

use Database\Factories\ServiceTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceType extends Model
{
    /** @use HasFactory<ServiceTypeFactory> */
    use HasFactory;

    protected $fillable = ['city_id', 'created_by', 'name', 'description', 'image', 'status', 'is_approved', 'display_order'];

    /**
     * Restrict the query to services visible to customers: status Active and approved by admin.
     */
    public function scopeVisibleToCustomers(Builder $query): Builder
    {
        return $query->where('status', 'Active')->where('is_approved', true);
    }

    /**
     * The city this service belongs to (null = a global service created by the admin).
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * The admin or associate who created the service.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Restrict the query to services belonging to the given cities.
     *
     * @param  list<int>  $cityIds
     */
    public function scopeInCities(Builder $query, array $cityIds): Builder
    {
        return $query->whereIn('city_id', $cityIds);
    }

    protected static function booted()
    {
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('display_order', 'asc');
        });
    }
}
