<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAssociate;
use Database\Factories\ServiceTypeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceType extends Model
{
    /** @use HasFactory<ServiceTypeFactory> */
    use BelongsToAssociate, HasFactory;

    protected $fillable = ['city_id', 'created_by', 'associate_id', 'name', 'description', 'image', 'status', 'is_approved', 'display_order'];

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
     * The admin or associate who created the service. Distinct from
     * "associate": a service an admin added can still be handed to an
     * associate, in which case the admin stays the creator.
     */
    /**
     * The vehicles that provide this service.
     */
    public function vehicles()
    {
        return $this->belongsToMany(Vehicle::class, 'service_type_vehicle');
    }

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
