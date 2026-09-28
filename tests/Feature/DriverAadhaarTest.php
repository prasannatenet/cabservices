<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverAadhaarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function driverPayload(Driver $driver, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Driver',
            'phone' => '+919876543210',
            'alternate_phone' => '+919811122233',
            'license_number' => 'DL-1234',
            'license_expiry' => now()->addYear()->format('Y-m-d'),
            'current_city_id' => $driver->current_city_id,
            'status' => 'Available',
        ], $overrides);
    }

    public function test_admin_can_store_aadhaar_details_and_photo(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $driver = Driver::factory()->make();

        $response = $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload($driver, [
            'aadhaar_number' => '123456789012',
            'permanent_address' => '12 MG Road, Jaipur, Rajasthan 302001',
            'current_address' => '45 Station Road, Jaipur, Rajasthan 302002',
            'aadhaar_photo' => UploadedFile::fake()->create('aadhaar.jpg', 20, 'image/jpeg'),
        ]));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.drivers.index'));

        $created = Driver::where('license_number', 'DL-1234')->first();

        $this->assertSame('123456789012', $created->aadhaar_number);
        $this->assertSame('12 MG Road, Jaipur, Rajasthan 302001', $created->permanent_address);
        $this->assertSame('45 Station Road, Jaipur, Rajasthan 302002', $created->current_address);
        $this->assertSame('+919811122233', $created->alternate_phone);
        $this->assertNotNull($created->aadhaar_photo);

        Storage::disk('public')->assertExists($created->aadhaar_photo);
    }

    public function test_aadhaar_number_is_stored_without_spaces(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $driver = Driver::factory()->make();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload($driver, [
            'aadhaar_number' => '1234 5678 9012',
        ]))->assertSessionHasNoErrors();

        $this->assertSame(
            '123456789012',
            Driver::where('license_number', 'DL-1234')->first()->aadhaar_number
        );
    }

    public function test_aadhaar_number_is_formatted_for_display(): void
    {
        $driver = Driver::factory()->create(['aadhaar_number' => '123456789012']);

        $this->assertSame('1234 5678 9012', $driver->formatted_aadhaar_number);
    }

    public function test_aadhaar_number_must_be_twelve_digits(): void
    {
        $admin = User::factory()->create();
        $driver = Driver::factory()->make();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload($driver, [
            'aadhaar_number' => '12345',
        ]))->assertSessionHasErrors('aadhaar_number');
    }

    public function test_two_drivers_cannot_share_the_same_aadhaar_number(): void
    {
        $admin = User::factory()->create();
        Driver::factory()->create(['aadhaar_number' => '123456789012']);
        $driver = Driver::factory()->make();

        $this->actingAs($admin)->post(route('admin.drivers.store'), $this->driverPayload($driver, [
            'aadhaar_number' => '123456789012',
        ]))->assertSessionHasErrors('aadhaar_number');
    }

    public function test_a_driver_can_keep_their_own_aadhaar_number_on_update(): void
    {
        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['aadhaar_number' => '123456789012']);

        $this->actingAs($admin)->put(route('admin.drivers.update', $driver), $this->driverPayload($driver, [
            'license_number' => $driver->license_number,
            'aadhaar_number' => '123456789012',
        ]))->assertSessionHasNoErrors();

        $this->assertSame('123456789012', $driver->fresh()->aadhaar_number);
    }

    public function test_aadhaar_photo_is_replaced_on_update(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['aadhaar_photo' => 'drivers/aadhaar/old.jpg']);
        Storage::disk('public')->put('drivers/aadhaar/old.jpg', 'old');

        $this->actingAs($admin)->put(route('admin.drivers.update', $driver), $this->driverPayload($driver, [
            'license_number' => $driver->license_number,
            'aadhaar_photo' => UploadedFile::fake()->create('new.jpg', 20, 'image/jpeg'),
        ]))->assertSessionHasNoErrors();

        $driver->refresh();

        $this->assertNotSame('drivers/aadhaar/old.jpg', $driver->aadhaar_photo);
        Storage::disk('public')->assertMissing('drivers/aadhaar/old.jpg');
        Storage::disk('public')->assertExists($driver->aadhaar_photo);
    }

    public function test_aadhaar_photo_is_removed_when_the_driver_is_deleted(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $driver = Driver::factory()->create(['aadhaar_photo' => 'drivers/aadhaar/gone.jpg']);
        Storage::disk('public')->put('drivers/aadhaar/gone.jpg', 'gone');

        $this->actingAs($admin)->delete(route('admin.drivers.destroy', $driver))
            ->assertRedirect(route('admin.drivers.index'));

        Storage::disk('public')->assertMissing('drivers/aadhaar/gone.jpg');
    }

    public function test_aadhaar_details_are_visible_on_the_driver_page(): void
    {
        $admin = User::factory()->create();

        $driver = Driver::factory()->create([
            'aadhaar_number' => '123456789012',
            'aadhaar_photo' => 'drivers/aadhaar/card.jpg',
            'permanent_address' => '12 MG Road, Jaipur',
            'current_address' => '45 Station Road, Jaipur',
            'alternate_phone' => '+919811122233',
        ]);

        $this->actingAs($admin)->get(route('admin.drivers.show', $driver))
            ->assertStatus(200)
            ->assertSee('Aadhaar Details')
            ->assertSee('1234 5678 9012')
            ->assertSee('12 MG Road, Jaipur')
            ->assertSee('45 Station Road, Jaipur')
            ->assertSee('+919811122233')
            ->assertSee(asset('storage/drivers/aadhaar/card.jpg'));
    }

    public function test_the_driver_form_exposes_the_new_fields(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('admin.drivers.create'))
            ->assertStatus(200)
            ->assertSee('name="aadhaar_number"', false)
            ->assertSee('name="aadhaar_photo"', false)
            ->assertSee('name="permanent_address"', false)
            ->assertSee('name="current_address"', false)
            ->assertSee('name="alternate_phone"', false);
    }
}
