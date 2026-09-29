<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Models\City;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A customer sees what a vehicle costs before committing to it: the day and per
 * kilometre rate on every card of the search results, and the full price
 * structure of the chosen vehicle on the booking page.
 *
 * The Tata Nexon of the fleet is on 5,500 for a day of 500 km and 13 for every
 * kilometre above that, so a three day hire quotes 16,500 with 1,500 km included.
 */
class BookingPriceDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const PRICE_PER_DAY = '5500.00';

    private const FIXED_KM_PER_DAY = 500;

    private const PRICE_PER_KM = '13.00';

    public function test_search_results_cards_show_the_day_and_per_km_rate()
    {
        $response = $this->post(route('booking.search'), $this->searchPayload());

        $response->assertStatus(200);
        $response->assertViewIs('booking.results');
        $response->assertSee('5,500');
        $response->assertSee('/day');
        $response->assertSee('13.00/km');
        $response->assertSee('500 km included per day.');
    }

    public function test_booking_page_shows_the_full_price_structure()
    {
        $response = $this->get(route('booking.create', $this->searchPayload() + [
            'vehicle_id' => Vehicle::first()->id,
            'drop_date' => now()->addDays(4)->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('booking.create');
        $response->assertSee('Price Structure');
        $response->assertSee('3 day hire');
        // The rate card is quoted per day, so it reads 5,500 a day and 500 km a
        // day. The hire is then the rate card times the days: 3 days at 5,500 is
        // 16,500 and covers 3 x 500 = 1,500 km in total.
        $response->assertSee('Rate card');
        $response->assertSee('5,500.00 per day');
        $response->assertSee('km included per day');
        $response->assertSee('per extra km');
        $response->assertSee('3 day(s) hire');
        $response->assertSee('1,500 km included in total');
        $response->assertSee('Estimated Total');
        $response->assertSee('3 day(s) at');
        $response->assertSee('16,500.00');
    }

    public function test_booking_page_falls_back_when_the_vehicle_has_no_rate_card()
    {
        $response = $this->get(route('booking.create', $this->searchPayload() + [
            'vehicle_id' => Vehicle::factory()->create([
                'price_per_day' => null,
                'price_per_km' => null,
            ])->id,
        ]));

        $response->assertStatus(200);
        $response->assertDontSee('Price Structure');
        $response->assertSee('Pricing for this vehicle is confirmed by our team');
    }

    /**
     * A search that returns the one rate-carded vehicle of the fleet.
     *
     * @return array<string, mixed>
     */
    private function searchPayload(): array
    {
        $city = City::factory()->create();
        $service = ServiceType::factory()->create();

        Vehicle::factory()->create([
            'name' => 'Tata Nexon',
            'city_id' => $city->id,
            'status' => VehicleStatus::AVAILABLE->value,
            'seating_capacity' => 4,
            'price_per_day' => self::PRICE_PER_DAY,
            'price_per_km' => self::PRICE_PER_KM,
            'fixed_km_per_day' => self::FIXED_KM_PER_DAY,
        ]);

        return [
            'pickup_city_id' => $city->id,
            'drop_city_id' => $city->id,
            'pickup_location' => 'Airport',
            'drop_location' => 'Hotel',
            'pickup_date' => now()->addDays(2)->format('Y-m-d'),
            'pickup_time' => '10:00',
            'passengers' => 2,
            'service_type_id' => $service->id,
        ];
    }
}
