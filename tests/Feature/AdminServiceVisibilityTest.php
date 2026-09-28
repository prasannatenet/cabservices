<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_created_global_service_shows_on_homepage(): void
    {
        // Create an admin
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Create a global service (no city) created by admin
        $globalService = ServiceType::factory()->create([
            'name' => 'Global Airport Transfer',
            'status' => 'Active',
            'is_approved' => true,
            'city_id' => null, // Global service
            'created_by' => $admin->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('Global Airport Transfer');
    }

    public function test_admin_created_city_specific_service_shows_on_homepage(): void
    {
        // Create an admin
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Create a city
        $city = City::factory()->create();

        // Create a city-specific service created by admin
        $cityService = ServiceType::factory()->create([
            'name' => 'City Sedan Service',
            'status' => 'Active',
            'is_approved' => true,
            'city_id' => $city->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertSee('City Sedan Service');
    }

    public function test_service_type_dropdown_contains_admin_services(): void
    {
        // Create an admin
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        // Create cities
        $city1 = City::factory()->create(['name' => 'New York']);
        $city2 = City::factory()->create(['name' => 'Los Angeles']);

        // Create global admin service
        $globalService = ServiceType::factory()->create([
            'name' => 'Global Premium',
            'status' => 'Active',
            'is_approved' => true,
            'city_id' => null,
            'created_by' => $admin->id,
        ]);

        // Create city-specific admin service
        $cityService = ServiceType::factory()->create([
            'name' => 'New York Express',
            'status' => 'Active',
            'is_approved' => true,
            'city_id' => $city1->id,
            'created_by' => $admin->id,
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);

        $this->assertDatabaseHas('service_types', [
            'name' => 'Global Premium',
            'is_approved' => true,
        ]);

        $this->assertDatabaseHas('service_types', [
            'name' => 'New York Express',
            'is_approved' => true,
        ]);
    }
}
