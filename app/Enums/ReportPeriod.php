<?php

namespace App\Enums;

use Carbon\CarbonImmutable;

/**
 * The stretch of time the dashboard is reporting on.
 *
 * Every card on the admin dashboard answers a question about a period rather
 * than about the whole business, so the admin picks the period once and every
 * card reads from it. Daily is the default, because the dashboard is first and
 * foremost "what is happening right now"; the wider periods are there for
 * looking back over a week, a month or a year.
 *
 * A period is a pair of whole days: "This Week" starts on the Monday of the
 * current week and ends on the following Sunday, and never spills into today
 * mid-way. That keeps the number the same however many times the page is
 * reloaded, and keeps it comparable with the same card in another period.
 */
enum ReportPeriod: string
{
    case Daily = 'daily';
    case ThisWeek = 'this_week';
    case LastWeek = 'last_week';
    case ThisMonth = 'this_month';
    case LastMonth = 'last_month';
    case ThisYear = 'this_year';
    case LastYear = 'last_year';

    /**
     * The text shown on the period button.
     */
    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Daily',
            self::ThisWeek => 'This Week',
            self::LastWeek => 'Last Week',
            self::ThisMonth => 'This Month',
            self::LastMonth => 'Last Month',
            self::ThisYear => 'This Year',
            self::LastYear => 'Last Year',
        };
    }

    /**
     * The first and last day of this period, both covering the whole day.
     *
     * Every card that filters on a date uses the range this gives it, so one
     * period means exactly the same stretch of time on every card rather than
     * each card working out its own idea of "this week".
     *
     * @return array{from: CarbonImmutable, to: CarbonImmutable}
     */
    public function range(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::now();

        [$from, $to] = match ($this) {
            self::Daily => [$today, $today],
            // A week runs Monday to Sunday, the convention Carbon uses, so this
            // week is the one currently in progress and last week is the whole
            // of the one before it.
            self::ThisWeek => [$today->startOfWeek(), $today->endOfWeek()],
            self::LastWeek => [
                $today->subWeek()->startOfWeek(),
                $today->subWeek()->endOfWeek(),
            ],
            self::ThisMonth => [$today->startOfMonth(), $today->endOfMonth()],
            self::LastMonth => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth(),
            ],
            self::ThisYear => [$today->startOfYear(), $today->endOfYear()],
            self::LastYear => [$today->subYear()->startOfYear(), $today->subYear()->endOfYear()],
        };

        return [
            'from' => $from->startOfDay(),
            'to' => $to->endOfDay(),
        ];
    }

    /**
     * The period named in the query string, falling back to Daily.
     *
     * Anything unrecognised, including a missing or hand-typed value, reads as
     * Daily rather than raising an error: the dashboard has to render for any
     * URL that reaches it.
     */
    public static function fromRequest(?string $value): self
    {
        return self::tryFrom((string) $value) ?? self::Daily;
    }
}
