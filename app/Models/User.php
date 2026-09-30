<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
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
     * An associate is a sub-admin for a set of cities, but what he actually runs
     * is decided by ownership rather than by geography: the fleet, drivers and
     * services he created are his, and the rides the admin assigned to him by
     * putting one of those on them are his too.
     */
    public function isAssociate(): bool
    {
        return $this->role === self::ROLE_ASSOCIATE;
    }

    public function isDriver(): bool
    {
        return $this->role === self::ROLE_DRIVER;
    }

    /**
     * Restrict the query to the associate accounts.
     */
    public function scopeAssociates(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_ASSOCIATE);
    }

    /**
     * Every associate, for the dropdowns that let the admin hand a record to one.
     *
     * @return Collection<int, User>
     */
    public static function associateOptions(): Collection
    {
        return self::query()->associates()->orderBy('name')->get();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * The cities an associate may create records in. His cities decide where a
     * new record may be based, never who owns it: ownership follows whoever
     * created the record.
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
     * An admin dispatches every ride. An associate only dispatches the rides the
     * admin handed to him by putting one of his own drivers or vehicles on
     * them: a ride that merely starts in one of his cities is not his, so he
     * is not told about it and cannot open it.
     */
    public function dispatchesBooking(Booking $booking): bool
    {
        if ($this->isDriver()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->isAssociate() && $booking->isOwnedByAssociate($this->id);
    }
}
