<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\City;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The customer panel shows a customer his own rides, and only his own rides.
 */
class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private City $city;

    private User $customer;

    private User $otherCustomer;

    private Booking $ownRide;

    private Booking $someoneElsesRide;

    protected function setUp(): void
    {
        parent::setUp();

        $this->city = City::factory()->create();
        $this->customer = User::factory()->customer()->create();
        $this->otherCustomer = User::factory()->customer()->create();

        $this->ownRide = $this->ride($this->customer, 'BKG-OWNRIDE01');

        $this->someoneElsesRide = $this->ride($this->otherCustomer, 'BKG-OTHERIDE');
    }

    /**
     * A confirmed ride booked by the given customer, so the panel has something
     * of his to show.
     */
    private function ride(User $customer, string $bookingNumber): Booking
    {
        return Booking::factory()->create([
            'booking_number' => $bookingNumber,
            'customer_user_id' => $customer->id,
            'pickup_city_id' => $this->city->id,
            'drop_city_id' => $this->city->id,
            'status' => BookingStatus::CONFIRMED->value,
        ]);
    }

    public function test_a_customer_can_open_his_own_dashboard(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee($this->ownRide->booking_number)
            ->assertDontSee($this->someoneElsesRide->booking_number);
    }

    /**
     * Booking starts with the search on the home page. The booking page is the
     * second step of that search and cannot be opened on its own, so the panel
     * must never point a customer straight at it.
     */
    public function test_the_panels_book_a_ride_links_start_the_search(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee(sprintf('href="%s"', route('home')), false)
            ->assertDontSee(sprintf('href="%s"', route('booking.create')), false);
    }

    public function test_a_customer_can_open_one_of_his_own_rides(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.bookings.show', $this->ownRide))
            ->assertOk()
            ->assertSee($this->ownRide->booking_number);
    }

    public function test_a_customer_cannot_open_another_customers_ride(): void
    {
        $this->actingAs($this->customer)
            ->get(route('customer.bookings.show', $this->someoneElsesRide))
            ->assertNotFound();
    }

    public function test_a_ride_that_belongs_to_nobody_is_not_reachable_either(): void
    {
        $booking = $this->ride($this->customer, 'BKG-ORPHANRID');
        $booking->forceFill(['customer_user_id' => null])->save();

        $this->actingAs($this->customer)
            ->get(route('customer.bookings.show', $booking))
            ->assertNotFound();
    }

    public function test_only_a_customer_can_open_the_customer_panel(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_the_dashboard_is_closed_to_guests(): void
    {
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
    }

    public function test_a_customer_is_sent_to_his_own_dashboard_from_the_generic_dashboard_link(): void
    {
        $this->actingAs($this->customer)
            ->get(route('dashboard'))
            ->assertRedirect(route('customer.dashboard'));
    }
}
