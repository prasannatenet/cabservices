<?php

namespace Tests\Feature;

use App\Mail\SettingsTestMail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_settings_page(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.index');
        $response->assertSee('Email notifications');
    }

    public function test_admin_can_save_the_mail_settings(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'mail_enabled' => '1',
            'mail_from_address' => 'bookings@example.com',
            'mail_from_name' => 'Cab Services',
            'mail_notify_on_booking_request' => '1',
            'mail_notify_customer' => '1',
            'mail_notify_customer_on_driver_assigned' => '1',
            'mail_booking_notification_recipients' => "ops@example.com\ndispatch@example.com",
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.settings.index'));

        $this->assertTrue(Setting::boolean('mail.enabled'));
        $this->assertSame('bookings@example.com', Setting::string('mail.from_address'));
        $this->assertSame('Cab Services', Setting::string('mail.from_name'));
        $this->assertSame(
            ['ops@example.com', 'dispatch@example.com'],
            Setting::list('mail.booking_notification_recipients')
        );
    }

    public function test_unchecked_toggles_are_stored_as_disabled(): void
    {
        $admin = User::factory()->create();
        Setting::put('mail.enabled', true);
        Setting::put('mail.notify_customer', true);

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'mail_from_address' => 'bookings@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertFalse(Setting::boolean('mail.enabled'));
        $this->assertFalse(Setting::boolean('mail.notify_customer'));
    }

    public function test_saved_settings_are_shown_back_on_the_page(): void
    {
        $admin = User::factory()->create();
        Setting::put('mail.from_address', 'saved@example.com');
        Setting::put('mail.booking_notification_recipients', 'ops@example.com');

        $response = $this->actingAs($admin)->get(route('admin.settings.index'));

        $response->assertSee('saved@example.com');
        $response->assertSee('ops@example.com');
    }

    public function test_recipients_must_be_valid_email_addresses(): void
    {
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->put(route('admin.settings.update'), [
            'mail_booking_notification_recipients' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('mail_booking_notification_recipients.0');
    }

    public function test_admin_can_send_a_test_mail(): void
    {
        Mail::fake();
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.settings.test-mail'), [
            'email' => 'admin@example.com',
        ]);

        $response->assertSessionHasNoErrors();
        Mail::assertSent(SettingsTestMail::class, fn (SettingsTestMail $mail): bool => $mail->hasTo('admin@example.com'));
    }

    public function test_test_mail_requires_a_valid_address(): void
    {
        Mail::fake();
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.settings.test-mail'), [
            'email' => 'nope',
        ]);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    public function test_associate_cannot_open_the_settings_page(): void
    {
        $associate = User::factory()->associate()->create();

        $this->actingAs($associate)->get(route('admin.settings.index'))->assertRedirect(route('associate.dashboard'));
    }
}
