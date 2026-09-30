<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'status', 'username', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_ASSOCIATE = 'associate';

    public const ROLE_DRIVER = 'driver';

    public const STATUS_ACTIVE = 'Active';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * An associate is the admin of the cities assigned to him. He manages the
     * fleet, drivers, services and bookings of those cities only.
     */
    public function isAssociate(): bool
    {
        return $this->role === self::ROLE_ASSOCIATE;
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The cities an associate manages.
     */
    public function assignedCities(): BelongsToMany
    {
        return $this->belongsToMany(City::class, 'city_user')->withTimestamps();
    }

    /**
     * Ids of the cities he manages (empty when he manages none).
     *
     * @return list<int>
     */
    public function assignedCityIds(): array
    {
        return $this->assignedCities()
            ->pluck('cities.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * Whether this associate manages the given city.
     */
    public function managesCity(?int $cityId): bool
    {
        if ($cityId === null) {
            return false;
        }

        return in_array($cityId, $this->assignedCityIds(), true);
    }

    /**
     * The dashboard of the panel this user belongs to.
     */
    public function homeRouteName(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'admin.dashboard',
            self::ROLE_ASSOCIATE => 'associate.dashboard',
            default => 'driver.dashboard',
        };
    }

    /**
     * Whether this user dispatches rides for the given booking, i.e. he is
     * responsible for the ride and must be told how the driver answered it.
     *
     * An admin dispatches every ride. An associate only dispatches the rides
     * that start in one of the cities he manages, and a driver never does.
     */
    public function dispatchesBooking(Booking $booking): bool
    {
        if ($this->isDriver()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->isAssociate() && $this->managesCity($booking->pickup_city_id);
    }
}
