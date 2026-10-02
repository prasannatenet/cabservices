<?php

namespace Tests\Feature;

use App\Models\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeTimeInputTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_offers_12_hour_pickup_and_drop_time_wheels(): void
    {
        City::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('name="pickup_time"', false)
            ->assertSee('name="drop_time"', false)
            // The native 24-hour input is gone in favour of the three wheels.
            ->assertDontSee('type="time"', false)
            ->assertSee('meridiems: JSON.parse', false)
            ->assertSee('\u0022AM\u0022', false)
            ->assertSee('\u0022PM\u0022', false)
            ->assertSee('x-ref="hour"', false)
            ->assertSee('x-ref="minute"', false)
            ->assertSee('x-ref="meridiem"', false);
    }

    public function test_a_stored_pickup_time_is_shown_in_12_hour_parts(): void
    {
        City::factory()->create();

        $response = $this->withSession([
            '_old_input' => [
                'pickup_time' => '18:05',
                'drop_time' => '00:30',
            ],
        ])->get('/');

        $response->assertStatus(200)
            // 18:05 is the '06' row of the hour wheel and the PM row,
            // 00:30 is the '12' row and the AM row.
            ->assertSee('hourIndex: 6', false)
            ->assertSee('minuteIndex: 5', false)
            ->assertSee('meridiemIndex: 1', false)
            ->assertSee('hourIndex: 0', false)
            ->assertSee('minuteIndex: 30', false);
    }

    public function test_the_time_wheels_stay_hidden_until_the_field_is_clicked(): void
    {
        City::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200)
            // Both fields start closed, so neither wheel is on screen on load.
            ->assertSee('open: false', false)
            ->assertSee('x-show="open"', false)
            ->assertSee('x-on:click="toggle()"', false);
    }

    public function test_the_time_picker_closes_again_on_outside_click_and_escape(): void
    {
        City::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('@click.outside="open = false"', false)
            ->assertSee('@keydown.escape.window="open = false"', false);
    }

    public function test_an_empty_field_defers_to_the_browser_clock(): void
    {
        City::factory()->create();

        $this->travelTo(now()->setTime(9, 5));

        $response = $this->get('/');

        $response->assertStatus(200)
            // Nothing stored, so the wheels are told to read the visitor's own
            // clock rather than trusting the time the server rendered at.
            ->assertSee('hasStoredTime: false', false)
            ->assertSee('this.useClock()', false)
            ->assertSee('new Date()', false)
            // An empty row was removed because it centred as invisible padding.
            ->assertDontSee('\u0022--\u0022', false);
    }

    public function test_a_time_the_visitor_already_chosen_is_kept(): void
    {
        City::factory()->create();

        $response = $this->withSession([
            '_old_input' => ['pickup_time' => '18:05'],
        ])->get('/');

        $response->assertStatus(200)
            // A stored choice is never overwritten by the clock.
            ->assertSee('hasStoredTime: true', false)
            ->assertSee('hourIndex: 6', false)
            ->assertSee('minuteIndex: 5', false)
            ->assertSee('meridiemIndex: 1', false);
    }

    public function test_the_clock_maps_midnight_and_noon_onto_the_twelve_row(): void
    {
        City::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200)
            // `hour % 12 || 12` is what keeps midnight and noon on the '12' row
            // instead of wrapping to an invalid row.
            ->assertSee('now.getHours() % 12 || 12', false)
            ->assertSee('this.meridiemIndex = now.getHours() >= 12 ? 1 : 0', false);
    }

    public function test_the_public_layout_hides_x_cloak_panels_before_alpine_boots(): void
    {
        City::factory()->create();

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('[x-cloak] { display: none !important; }', false);
    }
}
