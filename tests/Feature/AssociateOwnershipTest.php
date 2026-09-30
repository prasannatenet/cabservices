<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\RideExpenseCategory;
use App\Models\Booking;
use App\Models\City;
use App\Models\Driver;
use App\Models\ServiceType;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCategory;
use App\Notifications\DriverRespondedToAssignment;
use App\Services\AssignmentResponseService;
use App\Services\BookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * A ride belongs to an associate only once the admin puts one of that
 * associate's own drivers or vehicles on it.
 *
 * The city a ride starts in decides nothing: a Udaipur ride stays with the admin
 * even though an associate manages Udaipur, and only moves to him when the admin
 * assigns the driver or vehicle he owns.
 */
class AssociateOwnershipTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $associate;

    private City $udaipur;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();

        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->udaipur = City::factory()->create(['name' => 'Udaipur']);
        $this->associate = User::factory()->associate()->create(['name' => 'Udaipur Manager']);
        $this->associate->assignedCities()->sync([$this->udaipur->id]);
    }

    public function test_a_ride_starting_in_an_associates_city_stays_with_the_admin(): void
    {
        $booking = $this->makeBooking();

        // Nothing has been assigned yet, so the ride is the admin's even though
        // it starts in the city this associate manages.
        $this->assertNull($booking->associate_id);
        $this->assertFalse($this->associate->dispatchesBooking($booking));
        $this->assertTrue($this->admin->dispatchesBooking($booking));

        $this->actingAs($this->associate)
            ->get(route('associate.bookings.index'))
            ->assertOk()
            ->assertDontSee($booking->booking_number);

        $this->actingAs($this->associate)
            ->get(route('associate.bookings.show', $booking))
            ->assertForbidden();
    }

    public function test_assigning_the_associates_driver_hands_the_ride_to_him(): void
    {
        $booking = $this->makeBooking();
        $driver = $this->associateDriver();

        app(BookingService::class)->assignDriver($booking, $driver->id, $this->admin->id);

        $booking->refresh();

        $this->assertSame($this->associate->id, (int) $booking->associate_id);
        $this->assertTrue($this->associate->dispatchesBooking($booking));

        $this->actingAs($this->associate)
            ->get(route('associate.bookings.index'))
            ->assertOk()
            ->assertSee($booking->booking_number);

        $this->actingAs($this->associate)
            ->get(route('associate.bookings.show', $booking))
            ->assertOk();
    }

    public function test_assigning_an_admin_created_driver_keeps_the_ride_with_the_admin(): void
    {
        $booking = $this->makeBooking();
        $adminDriver = Driver::factory()->create([
            'current_city_id' => $this->udaipur->id,
            'associate_id' => null,
        ]);

        app(BookingService::class)->assignDriver($booking, $adminDriver->id, $this->admin->id);

        $this->assertNull($booking->fresh()->associate_id);
        $this->assertFalse($this->associate->dispatchesBooking($booking->fresh()));
    }

    public function test_an_associates_vehicle_alone_is_enough_to_own_the_ride(): void
    {
        $booking = $this->makeBooking();
        $vehicle = Vehicle::factory()->create([
            'city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);

        $booking->update(['vehicle_id' => $vehicle->id]);
        $booking->syncAssociateFromAssignment();

        $this->assertSame($this->associate->id, (int) $booking->fresh()->associate_id);
    }

    public function test_a_ride_goes_back_to_the_admin_when_its_associate_resource_is_swapped_out(): void
    {
        $booking = $this->makeBooking();
        $vehicle = Vehicle::factory()->create([
            'city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);
        $adminVehicle = Vehicle::factory()->create([
            'city_id' => $this->udaipur->id,
            'associate_id' => null,
        ]);

        $booking->update(['vehicle_id' => $vehicle->id]);
        $booking->syncAssociateFromAssignment();
        $this->assertSame($this->associate->id, (int) $booking->fresh()->associate_id);

        // The admin swaps in one of his own vehicles, so the ride is his again.
        $booking->update(['vehicle_id' => $adminVehicle->id]);
        $booking->syncAssociateFromAssignment();

        $this->assertNull($booking->fresh()->associate_id);
    }

    public function test_an_associate_cannot_assign_another_associates_driver_to_a_ride(): void
    {
        $booking = $this->makeBooking();
        $booking->update(['associate_id' => $this->associate->id]);

        $otherAssociate = User::factory()->associate()->create();
        $otherAssociate->assignedCities()->sync([$this->udaipur->id]);
        $foreignDriver = Driver::factory()->create([
            'current_city_id' => $this->udaipur->id,
            'associate_id' => $otherAssociate->id,
        ]);

        $response = $this->actingAs($this->associate)->put(route('associate.bookings.update', $booking), [
            'status' => $booking->status->value,
            'driver_id' => $foreignDriver->id,
        ]);

        $response->assertSessionHasErrors('driver_id');
        $this->assertNull($booking->fresh()->driver_id);
    }

    public function test_only_the_associate_who_owns_the_ride_is_notified(): void
    {
        $booking = $this->makeBooking();
        $driver = $this->associateDriver();

        app(BookingService::class)->assignDriver($booking, $driver->id, $this->admin->id);

        $otherAssociate = User::factory()->associate()->create();
        $otherAssociate->assignedCities()->sync([$this->udaipur->id]);

        $assignment = $booking->fresh()->driverAssignment;
        app(AssignmentResponseService::class)->accept($assignment);

        Notification::assertSentTo($this->associate, DriverRespondedToAssignment::class);
        Notification::assertSentTo($this->admin, DriverRespondedToAssignment::class);
        // The other associate manages the same city but does not own this ride.
        Notification::assertNotSentTo($otherAssociate, DriverRespondedToAssignment::class);
    }

    public function test_an_admin_created_vehicle_is_listed_as_admin_created_and_filterable(): void
    {
        $category = VehicleCategory::factory()->create();
        Vehicle::factory()->create(['name' => 'AdminOwnedCar', 'associate_id' => null]);
        Vehicle::factory()->create([
            'name' => 'AssociateOwnedCar',
            'associate_id' => $this->associate->id,
        ]);

        $this->actingAs($this->admin)->post(route('admin.vehicles.store'), [
            'name' => 'FreshAdminCar',
            'model' => '2024',
            'vehicle_category_id' => $category->id,
            'registration_number' => 'RJ14AD9001',
            'seating_capacity' => 4,
            'city_id' => $this->udaipur->id,
            'status' => 'Available',
            'associate_id' => 'none',
        ])->assertRedirect(route('admin.vehicles.index'));

        $this->assertNull(Vehicle::where('registration_number', 'RJ14AD9001')->firstOrFail()->associate_id);

        // Admin-created records are labelled as such and can be filtered for.
        $this->actingAs($this->admin)
            ->get(route('admin.vehicles.index', ['associate' => 'none']))
            ->assertOk()
            ->assertSee('Admin Created')
            ->assertSee('AdminOwnedCar')
            ->assertDontSee('AssociateOwnedCar');

        // And so can one associate's own vehicles.
        $this->actingAs($this->admin)
            ->get(route('admin.vehicles.index', ['associate' => $this->associate->id]))
            ->assertOk()
            ->assertSee('AssociateOwnedCar')
            ->assertDontSee('AdminOwnedCar');
    }

    public function test_the_assignment_dropdowns_only_offer_the_pickup_city(): void
    {
        $other = City::factory()->create(['name' => 'Nagpur']);

        $localDriver = $this->associateDriver();
        $awayDriver = Driver::factory()->create([
            'name' => 'NagpurDriver',
            'current_city_id' => $other->id,
            'associate_id' => null,
        ]);

        $booking = $this->makeBooking();

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        // The driver standing in the pickup city is offered, the one in another
        // city is not, even though both are free.
        $response->assertSee($localDriver->name);
        $response->assertDontSee($awayDriver->name);
    }

    public function test_an_associates_resources_are_hidden_behind_a_checkbox(): void
    {
        $ownDriver = Driver::factory()->create([
            'name' => 'OwnDriver',
            'current_city_id' => $this->udaipur->id,
            'associate_id' => null,
        ]);
        $associateDriver = Driver::factory()->create([
            'name' => 'HiddenAssociateDriver',
            'current_city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);

        $booking = $this->makeBooking();

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        $response->assertSee($ownDriver->name);
        // The associate's driver is rendered, but only inside the checkbox block
        // that is hidden until the admin ticks it.
        $response->assertSee('name="associate_driver_id"', false);
        $response->assertSee('HiddenAssociateDriver');
        $response->assertSee('x-model="showAssociate"', false);
    }

    public function test_the_checkbox_is_absent_when_the_city_has_no_associate(): void
    {
        // The ride itself has no vehicle, and the only driver in the pickup city
        // is the admin's own, so this city has no associate to offer.
        $booking = Booking::factory()->create([
            'pickup_city_id' => $this->udaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'driver_id' => null,
            'vehicle_id' => null,
            'associate_id' => null,
        ]);

        Driver::factory()->create([
            'current_city_id' => $this->udaipur->id,
            'associate_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();
        $response->assertDontSee('name="associate_driver_id"', false);
        $response->assertDontSee('name="associate_vehicle_id"', false);
        $response->assertDontSee('associate\'s fleet &amp; drivers', false);
    }

    public function test_choosing_an_associate_driver_hands_the_ride_to_that_associate(): void
    {
        $associateDriver = Driver::factory()->create([
            'current_city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);

        $booking = $this->makeBooking();

        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), [
            'status' => $booking->status->value,
            'driver_id' => '',
            'associate_driver_id' => $associateDriver->id,
        ])->assertSessionHasNoErrors();

        $booking->refresh();

        $this->assertSame($associateDriver->id, (int) $booking->driver_id);
        $this->assertSame($this->associate->id, (int) $booking->associate_id);
    }

    public function test_a_driver_from_another_city_cannot_be_forced_onto_a_ride(): void
    {
        $other = City::factory()->create(['name' => 'Nagpur']);
        $awayDriver = Driver::factory()->create([
            'current_city_id' => $other->id,
            'associate_id' => null,
        ]);

        $booking = $this->makeBooking();

        // The dropdowns never offered him, so a forged POST must be refused too.
        $this->actingAs($this->admin)->put(route('admin.bookings.update', $booking), [
            'status' => $booking->status->value,
            'driver_id' => $awayDriver->id,
        ])->assertSessionHas('error');

        $this->assertNull($booking->fresh()->driver_id);
    }

    public function test_the_associates_car_and_driver_never_leak_into_the_own_dropdowns(): void
    {
        $associateVehicle = Vehicle::factory()->create([
            'name' => 'AssociateOnlyCar',
            'city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);
        $associateDriver = Driver::factory()->create([
            'name' => 'AssociateOnlyDriver',
            'current_city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);

        $ownVehicle = Vehicle::factory()->create([
            'name' => 'AdminOnlyCar',
            'city_id' => $this->udaipur->id,
            'associate_id' => null,
        ]);
        $ownDriver = Driver::factory()->create([
            'name' => 'AdminOnlyDriver',
            'current_city_id' => $this->udaipur->id,
            'associate_id' => null,
        ]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $this->udaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'vehicle_id' => $ownVehicle->id,
            'driver_id' => $ownDriver->id,
            'associate_id' => null,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->getContent();

        // Each half lists only its own kind. The assigned admin resources are
        // still offered, but the associate's must not cross over.
        $this->assertStringContainsString('AdminOnlyCar', $html);
        $this->assertStringContainsString('AdminOnlyDriver', $html);

        $associateHalf = substr($html, strpos($html, 'associate_vehicle_id') ?: 0);
        $this->assertStringNotContainsString('AdminOnlyCar', $associateHalf);
        $this->assertStringNotContainsString('AdminOnlyDriver', $associateHalf);
        $this->assertStringContainsString('AssociateOnlyCar', $associateHalf);
        $this->assertStringContainsString('AssociateOnlyDriver', $associateHalf);
    }

    public function test_the_associate_panel_never_shows_the_admins_driver(): void
    {
        $adminDriver = Driver::factory()->create([
            'name' => 'AdminAssignedDriver',
            'current_city_id' => $this->udaipur->id,
            'associate_id' => null,
        ]);
        $myDriver = Driver::factory()->create([
            'name' => 'MyOwnDriver',
            'current_city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);

        // The ride is the associate's, but the driver on it is the admin's.
        $booking = Booking::factory()->create([
            'pickup_city_id' => $this->udaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'driver_id' => $adminDriver->id,
            'vehicle_id' => Vehicle::factory()->create([
                'city_id' => $this->udaipur->id,
                'associate_id' => $this->associate->id,
            ])->id,
            'associate_id' => $this->associate->id,
        ]);

        $response = $this->actingAs($this->associate)->get(route('associate.bookings.show', $booking));

        $response->assertOk();
        $response->assertSee('MyOwnDriver');
        // The admin's driver is not his, so it must not be offered to him.
        $response->assertDontSee('AdminAssignedDriver');
    }

    public function test_the_dashboard_explains_ownership_instead_of_cities(): void
    {
        $this->actingAs($this->associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            ->assertSee('What you manage')
            ->assertSee('you created')
            // The old wording implied a city handed the associate his work.
            ->assertDontSee('You manage')
            ->assertDontSee('Recent Bookings in My Cities')
            ->assertDontSee('No bookings found for your cities');
    }

    public function test_the_dashboard_counts_only_what_the_associate_owns(): void
    {
        $city = City::factory()->create(['name' => 'Jaipur']);

        // One of each, in the same city, but belonging to the admin.
        Vehicle::factory()->create(['city_id' => $city->id, 'associate_id' => null]);
        Driver::factory()->create(['current_city_id' => $city->id, 'associate_id' => null]);
        ServiceType::factory()->create(['city_id' => $city->id, 'associate_id' => null]);

        $mine = Vehicle::factory()->create([
            'city_id' => $city->id,
            'associate_id' => $this->associate->id,
            'status' => 'Available',
        ]);
        Driver::factory()->create([
            'current_city_id' => $city->id,
            'associate_id' => $this->associate->id,
            'status' => 'Available',
        ]);

        $response = $this->actingAs($this->associate)->get(route('associate.dashboard'));

        $response->assertOk();
        // One vehicle, one driver and no services are his. The admin's records in
        // the very same city are not counted, so these stay at 1 and 0.
        $response->assertSee('1 vehicle', false);
        $response->assertSee('1 driver', false);
        $response->assertSee('0 services', false);
    }

    public function test_the_dashboard_links_every_count_to_the_list_behind_it(): void
    {
        $this->actingAs($this->associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            ->assertSee(route('associate.vehicles.index'), false)
            ->assertSee(route('associate.drivers.index'), false)
            ->assertSee(route('associate.service-types.index'), false)
            ->assertSee(route('associate.bookings.index'), false);
    }

    public function test_the_dashboard_invites_a_new_associate_to_wait_for_work(): void
    {
        $this->actingAs($this->associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            ->assertSee('Nothing has been assigned to you yet');
    }

    public function test_the_booking_page_shows_the_driver_and_the_vehicle_in_full(): void
    {
        $city = City::factory()->create(['name' => 'Udaipur']);
        $driver = Driver::factory()->create([
            'name' => 'Ramesh Kumar',
            'phone' => '+919000000001',
            'whatsapp' => '+919000000002',
            'license_number' => 'DL-RAJ-2019',
            'current_city_id' => $city->id,
            'associate_id' => null,
        ]);
        $vehicle = Vehicle::factory()->create([
            'name' => 'Toyota Innova',
            'registration_number' => 'RJ14AA1234',
            'city_id' => $city->id,
            'associate_id' => null,
        ]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'customer_whatsapp' => '+919000000003',
            'associate_id' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking));

        $response->assertOk();

        // The driver was not shown at all before, only named in the expenses table.
        $response->assertSee('Assigned Driver');
        $response->assertSee('Ramesh Kumar');
        $response->assertSee('+919000000001');
        $response->assertSee('DL-RAJ-2019');
        $response->assertSee('Licence Expiry');

        // The vehicle gained its registration, city and owner.
        $response->assertSee('RJ14AA1234');
        $response->assertSee('Udaipur');
        $response->assertSee('Admin Created');

        // And the ride itself is described in full.
        $response->assertSee('Passengers');
        $response->assertSee('Current Status');
        $response->assertSee('Handled By');
        $response->assertSee('+919000000003');

        // Both link through to their own record.
        $response->assertSee(route('admin.drivers.show', $driver), false);
        $response->assertSee(route('admin.vehicles.show', $vehicle), false);
    }

    public function test_the_booking_page_shows_every_money_figure_for_the_ride(): void
    {
        $city = City::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'city_id' => $city->id,
            'price_per_day' => 5500,
            'price_per_km' => 13,
            'fixed_km_per_day' => 500,
        ]);
        // A driver paid per day, so his pay for the ride is a known figure.
        $driver = Driver::factory()->perDay()->create([
            'current_city_id' => $city->id,
            'per_day_salary' => 1200,
            'associate_id' => null,
        ]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'associate_id' => null,
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'start_odometer_km' => 1000,
            'end_odometer_km' => 1120,
            'total_amount' => 7060,
            'billed_days' => 1,
            'billed_included_km' => 500,
            'billed_price_per_day' => 5500,
            'billed_price_per_km' => 13,
            'extra_km' => 120,
            'base_amount' => 5500,
            'extra_km_amount' => 1560,
        ]);

        $booking->rideExpenses()->create([
            'driver_id' => $driver->id,
            'category' => RideExpenseCategory::Diesel,
            'amount' => 900,
            'spent_on' => now(),
            'bill_photo' => UploadedFile::fake()->create('bill.jpg', 30, 'image/jpeg'),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking->fresh()));

        $response->assertOk();
        $response->assertSee('Money On This Ride');
        $response->assertSee('Billed To Customer');
        $response->assertSee('7,060.00');
        $response->assertSee('Driver Earnings');
        $response->assertSee('1,200.00');
        $response->assertSee('Driver Expenses');
        $response->assertSee('900.00');
        $response->assertSee('Left For The Operator');
        // 7060 billed, less 1200 pay, less 900 expenses.
        $response->assertSee('4,960.00');
    }

    public function test_a_salaried_driver_gets_no_made_up_profit_figure(): void
    {
        $city = City::factory()->create();
        $vehicle = Vehicle::factory()->create([
            'city_id' => $city->id,
            'price_per_day' => 5500,
            'price_per_km' => 13,
            'fixed_km_per_day' => 500,
        ]);
        $driver = Driver::factory()->permanent()->create([
            'current_city_id' => $city->id,
            'monthly_salary' => 25000,
            'associate_id' => null,
        ]);

        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'associate_id' => null,
            'status' => BookingStatus::TRIP_COMPLETED->value,
            'start_odometer_km' => 1000,
            'end_odometer_km' => 1060,
            'total_amount' => 5500,
            'billed_days' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.bookings.show', $booking->fresh()));

        // A monthly salary has no per-ride cost to subtract, so the leftover is
        // reported as unavailable rather than as a made-up profit.
        $response->assertOk();
        $response->assertSee('No per-ride pay');
        $response->assertSee('Not available');
        $response->assertDontSee('Left For The Operator</p>');
    }

    public function test_the_booking_page_says_so_when_no_driver_is_assigned(): void
    {
        $city = City::factory()->create();
        $booking = Booking::factory()->create([
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'driver_id' => null,
            'associate_id' => null,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bookings.show', $booking))
            ->assertOk()
            ->assertSee('No driver is currently assigned to this ride.')
            ->assertSee('No vehicle assigned to this ride yet.');
    }

    public function test_the_dashboard_renders_inside_the_content_column(): void
    {
        // A stray closing tag in the page escapes the slot, closes the layout's
        // content column early and drops the rest of the page into the sidebar's
        // flex row, which renders it as a second column beside the dashboard.
        // Asserting on the parsed DOM catches that, where counting the "Save
        // Changes" text would not.
        $html = $this->actingAs($this->associate)
            ->get(route('associate.dashboard'))
            ->assertOk()
            ->getContent();

        $document = new \DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($document);

        // The flex row holds the sidebar backdrop, the sidebar itself and the
        // content column, and nothing else. A stray closing tag in the page makes
        // the rest of it a direct child of this row, which is what pushed the
        // desktop notifications card into a second column beside the dashboard.
        $rows = $xpath->query("//div[contains(concat(' ', normalize-space(@class), ' '), ' h-screen ')]");

        $this->assertGreaterThan(0, $rows->length, 'The layout flex row was not found.');

        $elementChildren = [];
        foreach ($rows->item(0)->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $elementChildren[] = $child;
            }
        }

        $this->assertCount(3, $elementChildren, 'The page content escaped the layout content column.');

        // The content column is the last of them, and it is the one that holds
        // the page rather than the sidebar.
        $this->assertStringContainsString('flex-1', $elementChildren[2]->getAttribute('class'));

        $mains = $xpath->query('//main');
        $this->assertSame(1, $mains->length, 'The page should render exactly one content area.');

        // And the whole dashboard, notifications card included, lives inside it.
        $this->assertStringContainsString('Desktop notifications', $mains->item(0)->ownerDocument->saveHTML($mains->item(0)));
    }

    /**
     * A fresh ride starting in the associate's city, with nobody assigned yet.
     */
    private function makeBooking(): Booking
    {
        return Booking::factory()->create([
            'pickup_city_id' => $this->udaipur->id,
            'drop_city_id' => $this->udaipur->id,
            'driver_id' => null,
            // A ride is dispatched with a vehicle already picked out; the
            // assignment row requires one.
            'vehicle_id' => Vehicle::factory()->create([
                'city_id' => $this->udaipur->id,
                'associate_id' => null,
            ])->id,
            'associate_id' => null,
        ]);
    }

    private function associateDriver(): Driver
    {
        return Driver::factory()->create([
            'current_city_id' => $this->udaipur->id,
            'associate_id' => $this->associate->id,
        ]);
    }
}
