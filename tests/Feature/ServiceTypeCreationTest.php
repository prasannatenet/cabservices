<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTypeCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_service_type(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $city = City::factory()->create();

        $serviceData = [
            'name' => 'Premium Sedan',
            'description' => 'Luxury sedan service',
            'display_order' => 1,
            'status' => 'Active',
            'city_id' => $city->id,
        ];

        $this->actingAs($admin)
            ->post(route('admin.service-types.store'), $serviceData)
            ->assertRedirect(route('admin.service-types.index'))
            ->assertSessionHas('success', 'Service created successfully.');

        $service = ServiceType::with('creator')->first();

        $this->assertNotNull($service, 'Service should be created');
        $this->assertEquals('Premium Sedan', $service->name);
        $this->assertEquals($admin->id, $service->created_by);
        $this->assertEquals(true, $service->is_approved, "Service should be approved by default when created by admin. Actual value: {$service->is_approved}");
        $this->assertEquals($admin->id, $service->created_by);
        $this->assertEquals($admin->role, $service->creator->role);
        $this->assertTrue($service->creator->isAdmin());
    }

    public function test_associate_cannot_access_admin_service_types(): void
    {
        $associate = User::factory()->associate()->create();

        $city = City::factory()->create();
        $associate->assignedCities()->attach($city->id);

        $serviceData = [
            'name' => 'Economy Car',
            'description' => 'Budget friendly option',
            'display_order' => 2,
            'status' => 'Active',
            'city_id' => $city->id,
        ];

        $this->actingAs($associate)
            ->post(route('admin.service-types.store'), $serviceData)
            ->assertRedirect(route('associate.dashboard'));

        $this->assertDatabaseMissing('service_types', [
            'name' => 'Economy Car',
        ]);
    }

    public function test_admin_can_create_global_service_without_city(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $serviceData = [
            'name' => 'Global Premium Service',
            'description' => 'Available in all cities',
            'display_order' => 3,
            'status' => 'Active',
            'city_id' => '', // No city = global service
        ];

        $this->actingAs($admin)
            ->post(route('admin.service-types.store'), $serviceData)
            ->assertRedirect(route('admin.service-types.index'))
            ->assertSessionHas('success', 'Service created successfully.');

        $service = ServiceType::with('creator')->first();

        $this->assertNotNull($service);
        $this->assertEquals('Global Premium Service', $service->name);
        $this->assertNull($service->city_id);
        $this->assertEquals($admin->id, $service->created_by);
        $this->assertEquals(true, $service->is_approved, "Service should be approved by default when created by admin. Actual value: {$service->is_approved}");
        $this->assertEquals($admin->id, $service->created_by);
        $this->assertEquals($admin->role, $service->creator->role);
        $this->assertTrue($service->creator->isAdmin());
    }

    public function test_service_type_shows_creator_role_correctly(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $associate = User::factory()->create([
            'role' => User::ROLE_ASSOCIATE,
        ]);

        $city = City::factory()->create();
        $associate->assignedCities()->attach($city->id);

        $adminService = ServiceType::factory()->createdByAdmin($admin)->create();

        $associateService = ServiceType::factory()->createdByAssociate($associate, $city)->create();

        // Verify admin service
        $this->assertTrue($adminService->creator->isAdmin());
        $this->assertNull($adminService->city_id);

        // Verify associate service
        $this->assertFalse($associateService->creator->isAdmin());
        $this->assertTrue($associateService->creator->isAssociate());
        $this->assertEquals($city->id, $associateService->city_id);
    }
}
