<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ReportPeriod;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * What the admin dashboard reports when the admin picks a period: a finished
 * ride is counted on the day it finished, a booking on the day it was asked
 * for, and a refusal on the day the driver turned it down. The fleet cards are
 * deliberately left alone, because a vehicle added in March is still active
 * today and "Daily" reporting zero of them would say nothing true.
 */
class AdminDashboardPeriodTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Pinned to the middle of a month: every period here is relative to
        // today, and on the 1st or 2nd a ride from a few days ago would fall
        // into the previous month and quietly change what "this month" means.
        $this->travelTo(Carbon::create(2026, 6, 15, 10, 0));

        $this->admin = User::factory()->create();
    }

    public function test_the_dashboard_shows_today_by_default(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('range', fn (array $range): bool => $range['from']->isToday() && ! $range['custom']);
        $response->assertSee('Daily');
    }

    public function test_every_quick_period_is_offered_as_a_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        // All seven presets are one click away, each linking to its own range.
        foreach (ReportPeriod::cases() as $option) {
            $response->assertSee(route('admin.dashboard', ['period' => $option->value]), false);
        }
    }

    public function test_the_calendar_opens_on_the_days_being_reported(): void
    {
        // Whatever route got the admin here, the trigger has to show the days in
        // hand, or opening the calendar would contradict the cards below it.
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', ['period' => 'this_month']));

        $response->assertOk();
        $response->assertSee("from: '".now()->startOfMonth()->format('Y-m-d')."'", false);
    }

    public function test_a_hand_picked_range_overrides_the_quick_period(): void
    {
        // A range sent by the calendar wins over a preset that is also in the
        // query string, so a stale preset can never fight the chosen days.
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'period' => 'this_year',
            'date_from' => '2026-06-02',
            'date_to' => '2026-06-04',
        ]));

        $response->assertOk();
        $response->assertViewHas('range', fn (array $range): bool => $range['custom']
            && $range['from']->format('Y-m-d') === '2026-06-02'
            && $range['to']->format('Y-m-d') === '2026-06-04');
    }

    public function test_a_range_typed_the_wrong_way_round_is_read_the_other_way_up(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'date_from' => '2026-06-04',
            'date_to' => '2026-06-02',
        ]));

        $response->assertOk();
        $response->assertViewHas('range', fn (array $range): bool => $range['from']->format('Y-m-d') === '2026-06-02'
            && $range['to']->format('Y-m-d') === '2026-06-04');
    }

    public function test_a_rubbish_date_is_ignored_rather_than_reaching_the_query(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'date_from' => 'not-a-date',
            'date_to' => '2026-06-02',
        ]));

        $response->assertOk();
        // Falls back to today rather than reporting an empty range.
        $response->assertViewHas('range', fn (array $range): bool => ! $range['custom']);
    }

    public function test_only_the_days_inside_a_hand_picked_range_are_counted(): void
    {
        $this->completedRideFinishedAt(Carbon::create(2026, 6, 2, 11, 0));
        $this->completedRideFinishedAt(Carbon::create(2026, 6, 4, 11, 0));
        $this->completedRideFinishedAt(Carbon::create(2026, 6, 10, 11, 0));

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'date_from' => '2026-06-02',
            'date_to' => '2026-06-04',
        ]));

        $response->assertOk();
        // The 2nd and the 4th are inside the range; the 10th is not.
        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['completed_rides'] === 2
            && $metrics['total_trip_km'] === 1240);
    }

    public function test_a_ride_finished_earlier_is_left_out_of_the_daily_report(): void
    {
        $this->completedRideFinishedAt(now()->subDays(3));

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['completed_rides'] === 0);
    }

    public function test_this_month_brings_in_a_ride_finished_earlier_in_the_month(): void
    {
        // The first of the month, so it is always inside the current month
        // rather than spilling back into the one before when today is early.
        $this->completedRideFinishedAt(now()->startOfMonth());

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'period' => 'this_month',
        ]));

        $response->assertOk();
        $response->assertViewHas('range', fn (array $range): bool => $range['from']->isSameDay(now()->startOfMonth()) && ! $range['custom']);
        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['completed_rides'] === 1);
    }

    public function test_last_year_reports_the_year_before_this_one(): void
    {
        $this->completedRideFinishedAt(now()->subYear()->startOfYear()->addDays(2));
        $this->completedRideFinishedAt(now()->startOfYear()->addDay());

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'period' => 'last_year',
        ]));

        $response->assertOk();
        // Only the ride finished in last year belongs in last year's report.
        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['completed_rides'] === 1
            && $metrics['total_trip_km'] === 620);
    }

    public function test_an_unrecognised_period_falls_back_to_today(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'period' => 'not-a-period',
        ]));

        $response->assertOk();
        $response->assertViewHas('range', fn (array $range): bool => $range['from']->isToday() && ! $range['custom']);
    }

    public function test_the_fleet_cards_ignore_the_period(): void
    {
        Vehicle::factory()->create(['status' => 'Available']);
        Vehicle::factory()->create(['status' => 'Maintenance']);
        Driver::factory()->create(['status' => 'Available']);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard', [
            'period' => 'daily',
        ]));
        $response->assertOk();
        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['active_vehicles'] === 1
            && $metrics['available_drivers'] === 1);
    }

    public function test_bookings_are_counted_on_the_day_they_were_asked_for(): void
    {
        $city = City::factory()->create();

        Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'status' => BookingStatus::PENDING->value,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $daily = $this->actingAs($this->admin)->get(route('admin.dashboard', ['period' => 'daily']));
        $daily->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total_bookings'] === 0
            && $metrics['pending_bookings'] === 0);

        $month = $this->actingAs($this->admin)->get(route('admin.dashboard', ['period' => 'this_month']));
        $month->assertViewHas('metrics', fn (array $metrics): bool => $metrics['total_bookings'] === 1
            && $metrics['pending_bookings'] === 1);
    }

    public function test_a_ride_the_admin_closed_without_readings_is_placed_by_the_day_it_was_closed(): void
    {
        // No trip_ended_at at all: the booking was closed by hand, so updated_at
        // is the only day on record. It must still be counted.
        $city = City::factory()->create();

        Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => Vehicle::factory()->create([
                'city_id' => $city->id,
                'price_per_day' => '5500.00',
                'fixed_km_per_day' => 500,
                'price_per_km' => '13.00',
            ])->id,
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'start_odometer_km' => 145320,
            'end_odometer_km' => 145320 + 620,
            'trip_ended_at' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('metrics', fn (array $metrics): bool => $metrics['completed_rides'] === 1
            && $metrics['total_trip_km'] === 620);
    }

    /**
     * A finished ride of a known distance, closed on a given day.
     */
    private function completedRideFinishedAt(Carbon $endedAt): Booking
    {
        $city = City::factory()->create();

        return Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => Vehicle::factory()->create([
                'city_id' => $city->id,
                'price_per_day' => '5500.00',
                'fixed_km_per_day' => 500,
                'price_per_km' => '13.00',
            ])->id,
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'start_odometer_km' => 145320,
            'end_odometer_km' => 145320 + 620,
            'trip_started_at' => $endedAt->copy()->subHour(),
            'trip_ended_at' => $endedAt,
            'updated_at' => $endedAt,
        ]);
    }
}
