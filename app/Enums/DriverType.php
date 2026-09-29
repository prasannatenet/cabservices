<?php

namespace App\Enums;

/**
 * How a driver is engaged, which decides which salary figure applies to him.
 *
 * A Permanent driver is paid a fixed amount every month. A Per Day driver is
 * paid a fixed amount for each day he works.
 */
enum DriverType: string
{
    case Permanent = 'Permanent';
    case PerDay = 'Per Day';

    /**
     * Whether this driver is paid a monthly salary rather than a daily one.
     */
    public function isPermanent(): bool
    {
        return $this === self::Permanent;
    }

    /**
     * Label for the salary field belonging to this driver type.
     */
    public function salaryLabel(): string
    {
        return match ($this) {
            self::Permanent => 'Monthly Salary',
            self::PerDay => 'Per Day Salary',
        };
    }

    /**
     * Short suffix shown next to a stored salary, e.g. "15,000 per month".
     */
    public function salaryPeriodLabel(): string
    {
        return match ($this) {
            self::Permanent => 'per month',
            self::PerDay => 'per day',
        };
    }
}
