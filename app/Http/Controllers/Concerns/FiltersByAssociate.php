<?php

namespace App\Http\Controllers\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The admin's side of associate ownership: which associate a record belongs to,
 * and the "who owns this" filter the fleet, driver and service lists share.
 *
 * Ownership is explicit. A record the admin creates is the admin's own until he
 * hands it to an associate, and he can take it back at any time by choosing
 * "Admin Created" again. A record an associate created is listed under that
 * associate, so the admin can see at a glance whose is whose.
 */
trait FiltersByAssociate
{
    /**
     * The filter and form value standing for "the admin's own records".
     *
     * Null is the real stored value for those records; this string only exists
     * so a select element can offer the option and an empty value stays free to
     * mean "do not filter".
     */
    public const ADMIN_OWNER = 'none';

    /**
     * Narrow a query down to one owner, as asked for by the list's filter.
     *
     * An empty filter leaves the list alone. The literal "none" keeps only the
     * records the admin created himself, which is why an empty string and a
     * missing parameter are treated differently from an explicit "none".
     */
    protected function applyAssociateFilter($query, Request $request, string $parameter = 'associate')
    {
        $owner = $request->query($parameter);

        if ($owner === null || $owner === '') {
            return $query;
        }

        return $owner === self::ADMIN_OWNER
            ? $query->ownedByAssociate(null)
            : $query->ownedByAssociate((int) $owner);
    }

    /**
     * The "who owns this record" dropdown data every admin list needs.
     *
     * @return array{associates: Collection<int, User>, adminOwner: string}
     */
    protected function associateFilterOptions(): array
    {
        return [
            'associates' => User::associateOptions(),
            'adminOwner' => self::ADMIN_OWNER,
        ];
    }

    /**
     * Turn a submitted owner into an id, or null for the admin's own records.
     *
     * A value that no longer points at a live associate is treated as the
     * admin's own, so a deleted associate can never leave a record pointing at a
     * missing owner.
     */
    protected function resolveAssociateId(mixed $submitted): ?int
    {
        if (blank($submitted) || $submitted === self::ADMIN_OWNER) {
            return null;
        }

        return User::query()
            ->associates()
            ->whereKey((int) $submitted)
            ->exists()
            ? (int) $submitted
            : null;
    }

    /**
     * The "who owns this record" validation rule for the admin forms.
     *
     * @return array<int, mixed>
     */
    protected function associateOwnerRules(): array
    {
        return [
            'nullable',
            Rule::in(array_merge(
                [self::ADMIN_OWNER],
                User::query()->associates()->pluck('id')->map(fn (int $id): string => (string) $id)->all(),
            )),
        ];
    }
}
