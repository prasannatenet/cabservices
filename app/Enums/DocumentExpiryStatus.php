<?php

namespace App\Enums;

use Carbon\CarbonInterface;

/**
 * How urgent a vehicle document (RC or insurance) is, based on the number of
 * days left before it expires.
 */
enum DocumentExpiryStatus: string
{
    case Missing = 'Missing';
    case Expired = 'Expired';
    case Critical = 'Critical';
    case Expiring = 'Expiring';
    case Valid = 'Valid';

    /**
     * A document expiring in this many days or fewer is shown in red.
     */
    public const CRITICAL_DAYS = 7;

    /**
     * A document expiring within this many days is shown in yellow.
     */
    public const EXPIRING_DAYS = 30;

    /**
     * Classify a document from the number of days left before it expires.
     * A negative number of days means the document already lapsed.
     */
    public static function fromDaysRemaining(?int $daysRemaining): self
    {
        return match (true) {
            $daysRemaining === null => self::Missing,
            $daysRemaining < 0 => self::Expired,
            $daysRemaining <= self::CRITICAL_DAYS => self::Critical,
            $daysRemaining <= self::EXPIRING_DAYS => self::Expiring,
            default => self::Valid,
        };
    }

    /**
     * Classify a document from its expiry date.
     */
    public static function fromExpiryDate(mixed $expiryDate): self
    {
        if (! $expiryDate instanceof CarbonInterface) {
            return self::Missing;
        }

        return self::fromDaysRemaining(self::daysRemaining($expiryDate));
    }

    /**
     * Whole days between today and the expiry date. A document expiring today
     * has 0 days left, one that lapsed yesterday has -1.
     */
    public static function daysRemaining(CarbonInterface $expiryDate): int
    {
        return (int) now()->startOfDay()->diffInDays($expiryDate->startOfDay(), false);
    }

    /**
     * Whether the document needs attention from the admin.
     */
    public function needsAttention(): bool
    {
        return in_array($this, [self::Missing, self::Expired, self::Critical], true);
    }

    /**
     * Urgency rank, lowest being the most urgent. Lets a vehicle report the
     * worst of its RC and insurance statuses in a single comparison.
     */
    public function priority(): int
    {
        return match ($this) {
            self::Missing => 0,
            self::Expired => 1,
            self::Critical => 2,
            self::Expiring => 3,
            self::Valid => 4,
        };
    }
}
