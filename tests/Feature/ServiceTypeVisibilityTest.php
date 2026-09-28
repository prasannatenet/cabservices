<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ServiceType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_services_show_on_homepage(): void
    {
        $city = City::factory()->create();

        $approvedService = ServiceType::factory()->create([
            'name' => 'Approved Service',
            'status' => 'Active',
            'is_approved' => true,
            'city_id' => $city->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Approved Service');
    }

    public function test_pending_services_dont_show_on_homepage(): void
    {
        $city = City::factory()->create();

        // Create a pending (unapproved) service - this is what associates create
        $pendingService = ServiceType::factory()->create([
            'name' => 'Pending Service',
            'status' => 'Active',
            'is_approved' => false,
            'city_id' => $city->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertDontSee('Pending Service');
    }

    public function test_inactive_services_dont_show_on_homepage(): void
    {
        $city = City::factory()->create();

        $inactiveService = ServiceType::factory()->create([
            'name' => 'Inactive Service',
            'status' => 'Inactive',
            'is_approved' => true,
            'city_id' => $city->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertDontSee('Inactive Service');
    }
}
