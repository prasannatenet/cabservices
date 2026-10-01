<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\City;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The first page asks which city the ride starts in, which city it ends in -
 * either one of ours or any place the customer writes out - and the dates. The
 * review page then asks where exactly to pick him up: the address, a landmark,
 * a Google Maps pin, and where he is going.
 */
class BookingSearchFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_search_form_asks_for_the_two_cities_the_dates_and_a_preference(): void
    {
        City::factory()->create(['name' => 'Jaipur']);

        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee('Pickup City');
        $response->assertSee('Drop City');
        $response->assertSee('Pickup Date');
        $response->assertSee('Pickup Time');
        $response->assertSee('Drop Date');
        $response->assertSee('Drop Time');
        $response->assertSee('Vehicle Preference (Optional)');
        $response->assertSee('Search Available Cabs');

        // The drop city is a box he can write in as well as pick from.
        $response->assertSee('name="drop_city"', false);
        $response->assertSee('list="drop-city-names"', false);

        // The addresses, the party size and the service are asked for later.
        $response->assertDontSee('name="pickup_location"');
        $response->assertDontSee('name="drop_location"');
        $response->assertDontSee('name="passengers"');
        $response->assertDontSee('name="service_type_id"');
    }

    public function test_a_search_runs_from_the_pickup_city_and_the_written_drop_city(): void
    {
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $udaipur = City::factory()->create(['name' => 'Udaipur']);
        ServiceType::factory()->create();

        Vehicle::factory()->create([
            'name' => 'Tata Nexon',
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $response = $this->post(route('booking.search'), $this->searchPayload($jaipur, 'Udaipur'));

        $response->assertStatus(200);
        $response->assertViewIs('booking.results');
        $response->assertViewHas('vehicles', fn ($vehicles) => $vehicles->count() === 1);
        $response->assertViewHas('dropCity', fn ($dropCity) => $dropCity->is($udaipur));
        $response->assertViewHas('dropCityIsOurs', true);

        // Both cities, the party of one and the service are put on the request,
        // so every page after the search can carry the whole summary without
        // asking again.
        $response->assertViewHas('searchParams', function (array $searchParams) use ($jaipur, $udaipur) {
            return (int) $searchParams['pickup_city_id'] === $jaipur->id
                && (int) $searchParams['drop_city_id'] === $udaipur->id
                && $searchParams['drop_city'] === 'Udaipur'
                && (int) $searchParams['passengers'] === 1
                && ! empty($searchParams['service_type_id']);
        });
    }

    public function test_a_drop_city_we_do_not_run_in_is_still_bookable(): void
    {
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        ServiceType::factory()->create();

        Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $response = $this->post(route('booking.search'), $this->searchPayload($jaipur, 'Kishangarh'));

        $response->assertStatus(200);
        $response->assertViewIs('booking.results');

        // The ride is served from the pickup city. The destination the customer
        // wrote is shown, and no marker is attached to it: being outside our
        // network is simply a ride, not a warning.
        $response->assertViewHas('dropCity', fn ($dropCity) => $dropCity->is($jaipur));
        $response->assertViewHas('dropCityIsOurs', false);
        $response->assertSee('Kishangarh');
        $response->assertDontSee('Outside our network');
    }

    public function test_a_search_without_a_pickup_city_is_sent_back(): void
    {
        City::factory()->create(['name' => 'Jaipur']);
        ServiceType::factory()->create();

        $response = $this->from(route('home'))->post(route('booking.search'), [
            'pickup_location' => 'Station Road',
            'drop_city' => 'Jaipur',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHasErrors('pickup_city_id');
    }

    public function test_the_review_page_asks_where_to_pick_him_up_and_shows_the_total(): void
    {
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $udaipur = City::factory()->create(['name' => 'Udaipur']);
        ServiceType::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'name' => 'Tata Nexon',
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
            'price_per_day' => '5500.00',
            'price_per_km' => '13.00',
            'fixed_km_per_day' => 500,
        ]);

        $response = $this->get(route('booking.create', [
            'pickup_city_id' => $jaipur->id,
            'drop_city_id' => $udaipur->id,
            'drop_city' => 'Kishangarh',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'drop_date' => now()->addDays(4)->format('Y-m-d'),
            'drop_time' => '18:00',
            'vehicle_preference' => 'SUV',
            'passengers' => 1,
            'service_type_id' => ServiceType::first()->id,
            'vehicle_id' => $vehicle->id,
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('booking.create');

        // The cab he picked, described in full.
        $response->assertSee('Your Cab');
        $response->assertSee('Tata Nexon');

        // Where to pick him up: the address, a landmark and the map pin, then
        // where he is going.
        $response->assertSee('Trip Details');
        $response->assertSee('name="pickup_location"', false);
        $response->assertSee('Pickup Landmark');
        $response->assertSee('name="pickup_landmark"', false);
        $response->assertSee('Pickup Location Link (Google Maps)');
        $response->assertSee('name="pickup_location_link"', false);
        $response->assertSee('name="drop_location"', false);

        // The schedule and the cities he settled on the first page ride along.
        $response->assertSee('name="pickup_date" value="'.now()->addDays(2)->format('Y-m-d').'"', false);
        $response->assertSee('Kishangarh');

        // Below the form: the amount, the warning that it can still move, and
        // the button that sends the request.
        $response->assertSee('Total Amount');
        $response->assertSee('Estimated Total');
        $response->assertSee('Amount may vary at the end.');
        $response->assertSee('Request Booking');
    }

    public function test_a_booking_request_keeps_the_pickup_details_and_the_written_drop_city(): void
    {
        $jaipur = City::factory()->create(['name' => 'Jaipur']);
        $service = ServiceType::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'city_id' => $jaipur->id,
            'status' => VehicleStatus::AVAILABLE->value,
        ]);

        $response = $this->post(route('booking.store'), [
            'customer_name' => 'Asha Sharma',
            'customer_phone' => '9876543210',
            'customer_email' => 'asha@example.com',
            'pickup_city_id' => $jaipur->id,
            'drop_city_id' => $jaipur->id,
            'drop_city' => 'Kishangarh',
            'pickup_location' => 'Station Road',
            'pickup_landmark' => 'Opposite City Mall gate',
            'pickup_location_link' => 'https://maps.google.com/?q=26.9124,75.7873',
            'drop_location' => 'Hawa Mahal Road',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'drop_date' => now()->addDays(4)->format('Y-m-d'),
            'drop_time' => '18:00',
            'vehicle_id' => $vehicle->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('bookings', [
            'pickup_city_id' => $jaipur->id,
            'drop_city_id' => $jaipur->id,
            'drop_city_label' => 'Kishangarh',
            'pickup_location' => 'Station Road',
            'pickup_landmark' => 'Opposite City Mall gate',
            'pickup_location_link' => 'https://maps.google.com/?q=26.9124,75.7873',
            'drop_location' => 'Hawa Mahal Road',
            'passengers' => 1,
            'service_type_id' => $service->id,
        ]);
    }

    /**
     * A search that picks the pickup city from our list and writes the drop
     * city out, whatever it is.
     *
     * @return array<string, mixed>
     */
    private function searchPayload(City $pickupCity, string $dropCity): array
    {
        return [
            'pickup_city_id' => $pickupCity->id,
            'drop_city' => $dropCity,
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'drop_date' => now()->addDays(4)->format('Y-m-d'),
            'drop_time' => '18:00',
            'vehicle_preference' => '',
        ];
    }
}
