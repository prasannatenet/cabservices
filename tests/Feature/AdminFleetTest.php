<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminFleetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_vehicle_with_fleet_details_and_documents()
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $vehicle = Vehicle::factory()->make();

        $response = $this->actingAs($admin)->post(route('admin.vehicles.store'), [
            'name' => $vehicle->name,
            'model' => $vehicle->model,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'registration_number' => $vehicle->registration_number,
            'seating_capacity' => $vehicle->seating_capacity,
            'city_id' => $vehicle->city_id,
            'status' => 'Available',
            'price_per_km' => '15.50',
            'price_per_day' => '2500.00',
            'fixed_km_per_day' => '300',
            'last_service_date' => '2026-09-01',
            'insurance_photos' => [UploadedFile::fake()->create('insurance.jpg', 10, 'image/jpeg')],
            'rc_photos' => [
                UploadedFile::fake()->create('rc.jpg', 10, 'image/jpeg'),
                UploadedFile::fake()->create('rc2.jpg', 10, 'image/jpeg'),
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.vehicles.index'));

        $created = Vehicle::where('registration_number', $vehicle->registration_number)->first();

        $this->assertSame('15.50', $created->price_per_km);
        $this->assertSame('2500.00', $created->price_per_day);
        $this->assertSame(300, $created->fixed_km_per_day);
        $this->assertSame('2026-09-01', $created->last_service_date->format('Y-m-d'));
        $this->assertCount(1, $created->insurance_photo);
        $this->assertCount(2, $created->rc_photo);

        Storage::disk('public')->assertExists($created->insurance_photo[0]);
        Storage::disk('public')->assertExists($created->rc_photo[0]);
    }

    public function test_admin_can_view_fleet_details_page()
    {
        $admin = User::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'price_per_km' => '12.50',
            'price_per_day' => '1800.00',
            'fixed_km_per_day' => 250,
            'insurance_photo' => ['vehicles/documents/insurance.jpg'],
            'rc_photo' => ['vehicles/documents/rc.jpg'],
            'last_service_date' => '2026-08-15',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.vehicles.show', $vehicle));

        $response->assertStatus(200);
        $response->assertSee('Fleet Details');
        $response->assertSee($vehicle->name);
        $response->assertSee($vehicle->registration_number);
        $response->assertSee('12.50');
        $response->assertSee('1800.00');
        $response->assertSee('250');
        $response->assertSee('15 Aug, 2026');
        $response->assertSee(asset('storage/vehicles/documents/insurance.jpg'));
        $response->assertSee(asset('storage/vehicles/documents/rc.jpg'));
    }

    public function test_admin_can_remove_document_photo_on_update()
    {
        Storage::fake('public');

        $admin = User::factory()->create();

        $vehicle = Vehicle::factory()->create([
            'insurance_photo' => ['vehicles/documents/keep.jpg', 'vehicles/documents/remove.jpg'],
        ]);

        Storage::disk('public')->put('vehicles/documents/keep.jpg', 'keep');
        Storage::disk('public')->put('vehicles/documents/remove.jpg', 'remove');

        $response = $this->actingAs($admin)->put(route('admin.vehicles.update', $vehicle), [
            'name' => $vehicle->name,
            'model' => $vehicle->model,
            'vehicle_category_id' => $vehicle->vehicle_category_id,
            'registration_number' => $vehicle->registration_number,
            'seating_capacity' => $vehicle->seating_capacity,
            'city_id' => $vehicle->city_id,
            'status' => $vehicle->status,
            'remove_insurance' => ['vehicles/documents/remove.jpg'],
        ]);

        $response->assertSessionHasNoErrors();

        $vehicle->refresh();

        $this->assertSame(['vehicles/documents/keep.jpg'], $vehicle->insurance_photo);
        Storage::disk('public')->assertExists('vehicles/documents/keep.jpg');
        Storage::disk('public')->assertMissing('vehicles/documents/remove.jpg');
    }

    public function test_admin_can_view_driver_details_page()
    {
        $admin = User::factory()->create();

        $driver = Driver::factory()->create([
            'profile_photo' => 'drivers/photos/driver.jpg',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.drivers.show', $driver));

        $response->assertStatus(200);
        $response->assertSee('Driver Details');
        $response->assertSee($driver->name);
        $response->assertSee($driver->phone);
        $response->assertSee($driver->license_number);
        $response->assertSee(asset('storage/drivers/photos/driver.jpg'));
    }
}
