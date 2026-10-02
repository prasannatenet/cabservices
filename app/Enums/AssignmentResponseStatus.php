<?php

namespace App\Enums;

/**
 * Whether a driver has answered an assignment within the response window.
 */
enum AssignmentResponseStatus: string
{
    case Pending = 'Pending';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
    case AutoRejected = 'Auto Rejected';

    /**
     * The admin gave the ride to another driver before this one drove it.
     *
     * It is kept apart from a refusal on purpose: the driver did not turn the
     * ride down, the dispatcher took it off him, so the ride must not be counted
     * against him and must never read as a rejection of his own.
     */
    case Superseded = 'Superseded';

    /**
     * The driver has not answered yet, so the ride is still waiting on him.
     */
    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    /**
     * The assignment was refused, either by the driver or because the response
     * window closed without an answer.
     */
    public function isRejected(): bool
    {
        return in_array($this, [self::Rejected, self::AutoRejected], true);
    }

    /**
     * The ride was taken off this driver and given to somebody else.
     */
    public function isSuperseded(): bool
    {
        return $this === self::Superseded;
    }
}
