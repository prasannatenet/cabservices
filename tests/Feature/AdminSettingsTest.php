<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The admin settings screen: the company name that brands the whole app, and
 * the admin's own email and password.
 */
class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'username' => 'admin001',
        ]);
    }

    public function test_the_admin_can_change_the_company_name(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.company.update'), ['company_name' => 'Skyline Cabs'])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame('Skyline Cabs', Setting::string('company.name'));
    }

    public function test_the_company_name_is_applied_to_the_app_name(): void
    {
        Setting::put('company.name', 'Skyline Cabs');

        // The provider applies the brand at boot; run a fresh instance the way a
        // new request would, since the setting changed after this test booted.
        $this->app->resolveProvider(AppServiceProvider::class)->boot();

        $this->assertSame('Skyline Cabs', config('app.name'));
    }

    public function test_the_settings_page_shows_the_current_company_name(): void
    {
        Setting::put('company.name', 'Skyline Cabs');

        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Skyline Cabs');
    }

    public function test_the_panel_brands_itself_with_the_app_name(): void
    {
        config(['app.name' => 'Skyline Cabs']);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Skyline Cabs');
    }

    public function test_the_admin_can_change_his_email(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.account.update'), ['admin_email' => 'owner@example.com'])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame('owner@example.com', $this->admin->fresh()->email);
    }

    public function test_the_admin_may_keep_his_own_email(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.account.update'), ['admin_email' => 'admin@example.com'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('admin.settings.index'));
    }

    public function test_the_admin_can_change_his_password(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.account.update'), [
                'admin_email' => 'admin@example.com',
                'admin_password' => 'new-secret-password',
                'admin_password_confirmation' => 'new-secret-password',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertTrue(Hash::check('new-secret-password', $this->admin->fresh()->password));
    }

    public function test_the_password_is_kept_when_the_fields_are_left_blank(): void
    {
        $original = $this->admin->password;

        $this->actingAs($this->admin)
            ->put(route('admin.settings.account.update'), [
                'admin_email' => 'admin@example.com',
                'admin_password' => '',
            ])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertSame($original, $this->admin->fresh()->password);
    }

    public function test_the_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin)
            ->put(route('admin.settings.account.update'), ['admin_email' => 'taken@example.com'])
            ->assertSessionHasErrors('admin_email');

        $this->assertSame('admin@example.com', $this->admin->fresh()->email);
    }

    public function test_a_short_password_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.account.update'), [
                'admin_email' => 'admin@example.com',
                'admin_password' => 'short',
                'admin_password_confirmation' => 'short',
            ])
            ->assertSessionHasErrors('admin_password');
    }

    public function test_the_password_must_be_confirmed(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.account.update'), [
                'admin_email' => 'admin@example.com',
                'admin_password' => 'new-secret-password',
                'admin_password_confirmation' => 'different-password',
            ])
            ->assertSessionHasErrors('admin_password');
    }

    public function test_a_non_admin_is_sent_back_to_his_own_panel(): void
    {
        $associate = User::factory()->associate()->create();

        $this->actingAs($associate)
            ->get(route('admin.settings.index'))
            ->assertRedirect(route('associate.dashboard'));
    }
}
