<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ownership by an associate, as opposed to geography.
 *
 * The fleet, the drivers and the services belong to whoever created them: a
 * record an associate created is his, and one the admin created belongs to the
 * admin (no associate at all). A record is never handed to an associate just
 * because it sits in a city that associate manages, and a ride is only his once
 * the admin assigns one of his drivers or vehicles to it.
 */
trait BelongsToAssociate
{
    /**
     * The associate owning this record, or null when the admin created it.
     */
    public function associate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'associate_id');
    }

    /**
     * Restrict the query to the records owned by the given associate.
     * A null id keeps only the admin's own records.
     */
    public function scopeOwnedByAssociate(Builder $query, ?int $associateId): Builder
    {
        return $associateId === null
            ? $query->whereNull('associate_id')
            : $query->where('associate_id', $associateId);
    }

    /**
     * Whether this record is owned by the given associate.
     */
    public function isOwnedByAssociate(?int $associateId): bool
    {
        return $associateId !== null && (int) $this->associate_id === $associateId;
    }

    /**
     * Whether the admin created this record himself, i.e. no associate owns it.
     */
    public function isAdminCreated(): bool
    {
        return $this->associate_id === null;
    }

    /**
     * Who owns this record, worded for a table cell or a dropdown: the
     * associate's name, or "Admin Created" for the admin's own records.
     */
    public function ownerLabel(): string
    {
        return $this->associate?->name ?? 'Admin Created';
    }
}
