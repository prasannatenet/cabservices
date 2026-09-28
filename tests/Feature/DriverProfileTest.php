<?php

namespace Tests\Feature;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DriverProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $driverAttributes
     * @return array{0: User, 1: Driver}
     */
    private function createDriverAccount(array $driverAttributes = []): array
    {
        $user = User::factory()->create([
            'name' => 'Test Driver',
            'email' => null,
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);

        $driver = Driver::factory()->create($driverAttributes + ['user_id' => $user->id]);

        return [$user, $driver];
    }

    public function test_driver_can_view_his_profile_page(): void
    {
        [$user, $driver] = $this->createDriverAccount([
            'aadhaar_number' => '123456789012',
            'aadhaar_photo' => 'drivers/aadhaar/card.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('driver.profile.edit'));

        $response->assertStatus(200);
        $response->assertViewIs('driver.profile');
        $response->assertSee('My Profile');
        $response->assertSee($driver->name);
        $response->assertSee('Aadhaar &amp; Licence', false);
    }

    public function test_profile_page_shows_aadhaar_as_read_only(): void
    {
        [$user] = $this->createDriverAccount([
            'aadhaar_number' => '123456789012',
            'aadhaar_photo' => 'drivers/aadhaar/card.jpg',
        ]);

        $response = $this->actingAs($user)->get(route('driver.profile.edit'));

        $response->assertSee('1234 5678 9012');
        $response->assertSee(asset('storage/drivers/aadhaar/card.jpg'));
        $response->assertSee('read-only', false);
        $response->assertSee('Locked');

        $response->assertDontSee('name="aadhaar_number"', false);
        $response->assertDontSee('name="aadhaar_photo"', false);
    }

    public function test_driver_can_update_his_own_contact_details(): void
    {
        [$user, $driver] = $this->createDriverAccount();

        $response = $this->actingAs($user)->put(route('driver.profile.update'), [
            'name' => 'Ramesh Kumar',
            'phone' => '+919876543210',
            'whatsapp' => '+919876543211',
            'alternate_phone' => '+919811122233',
            'email' => 'ramesh@example.com',
            'permanent_address' => '12 MG Road, Jaipur',
            'current_address' => '45 Station Road, Jaipur',
            'experience_years' => 8,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('driver.profile.edit'));

        $driver->refresh();

        $this->assertSame('Ramesh Kumar', $driver->name);
        $this->assertSame('+919876543210', $driver->phone);
        $this->assertSame('+919811122233', $driver->alternate_phone);
        $this->assertSame('ramesh@example.com', $driver->email);
        $this->assertSame('12 MG Road, Jaipur', $driver->permanent_address);
        $this->assertSame(8, $driver->experience_years);
    }

    public function test_driver_can_replace_his_profile_photo(): void
    {
        Storage::fake('public');

        [$user, $driver] = $this->createDriverAccount(['profile_photo' => 'drivers/photos/old.jpg']);
        Storage::disk('public')->put('drivers/photos/old.jpg', 'old');

        $this->actingAs($user)->put(route('driver.profile.update'), [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'profile_photo' => UploadedFile::fake()->create('me.jpg', 20, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $driver->refresh();

        $this->assertNotSame('drivers/photos/old.jpg', $driver->profile_photo);
        Storage::disk('public')->assertMissing('drivers/photos/old.jpg');
        Storage::disk('public')->assertExists($driver->profile_photo);
    }

    public function test_driver_cannot_change_his_aadhaar_number(): void
    {
        [$user, $driver] = $this->createDriverAccount(['aadhaar_number' => '123456789012']);

        $this->actingAs($user)->put(route('driver.profile.update'), [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'aadhaar_number' => '999988887777',
        ])->assertSessionHasNoErrors();

        $this->assertSame('123456789012', $driver->fresh()->aadhaar_number);
    }

    public function test_driver_cannot_upload_an_aadhaar_photo(): void
    {
        Storage::fake('public');

        [$user, $driver] = $this->createDriverAccount(['aadhaar_photo' => 'drivers/aadhaar/old.jpg']);

        $this->actingAs($user)->put(route('driver.profile.update'), [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'aadhaar_photo' => UploadedFile::fake()->create('fake-aadhaar.jpg', 20, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        $this->assertSame('drivers/aadhaar/old.jpg', $driver->fresh()->aadhaar_photo);
    }

    public function test_driver_cannot_change_licence_or_admin_controlled_fields(): void
    {
        [$user, $driver] = $this->createDriverAccount([
            'license_number' => 'DL-1234',
            'status' => 'Available',
        ]);

        $originalCity = $driver->current_city_id;

        $this->actingAs($user)->put(route('driver.profile.update'), [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'license_number' => 'DL-HACKED',
            'license_expiry' => now()->addYears(10)->format('Y-m-d'),
            'status' => 'Inactive',
            'current_city_id' => $originalCity + 999,
        ])->assertSessionHasNoErrors();

        $driver->refresh();

        $this->assertSame('DL-1234', $driver->license_number);
        $this->assertSame('Available', $driver->status);
        $this->assertSame($originalCity, $driver->current_city_id);
    }

    public function test_driver_cannot_use_another_drivers_email(): void
    {
        [$user, $driver] = $this->createDriverAccount();
        Driver::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($user)->put(route('driver.profile.update'), [
            'name' => $driver->name,
            'phone' => $driver->phone,
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');
    }

    public function test_updating_profile_keeps_the_login_account_name_in_sync(): void
    {
        [$user, $driver] = $this->createDriverAccount();

        $this->actingAs($user)->put(route('driver.profile.update'), [
            'name' => 'Updated Name',
            'phone' => $driver->phone,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_admin_cannot_open_the_driver_profile_page(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('driver.profile.edit'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('driver.profile.edit'))->assertRedirect(route('login'));
    }
}
