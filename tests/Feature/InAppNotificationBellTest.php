<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Tests\TestCase;

/**
 * The in-app notification bell in the navigation bar of both dispatcher panels.
 */
class InAppNotificationBellTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $associate;

    private User $driver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['username' => 'admin001']);
        $this->associate = User::factory()->associate()->create();
        $this->driver = User::factory()->create([
            'username' => 'driver001',
            'role' => User::ROLE_DRIVER,
        ]);
    }

    public function test_the_bell_starts_empty_for_a_fresh_account(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertExactJson(['unread_count' => 0, 'notifications' => []]);
    }

    public function test_an_unread_notification_is_listed_with_its_details(): void
    {
        $id = $this->notify($this->admin, 'Ride confirmed', 'Ramesh accepted booking CAB-1.');

        $this->actingAs($this->admin)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('notifications.0.id', $id)
            ->assertJsonPath('notifications.0.title', 'Ride confirmed')
            ->assertJsonPath('notifications.0.body', 'Ramesh accepted booking CAB-1.')
            ->assertJsonPath('notifications.0.url', '/admin/bookings/1');
    }

    public function test_a_notification_can_be_marked_read(): void
    {
        $id = $this->notify($this->admin, 'Ride confirmed', 'Ramesh accepted booking CAB-1.');

        $this->actingAs($this->admin)
            ->postJson(route('notifications.read', ['notification' => $id]))
            ->assertOk();

        $this->assertNotNull(
            $this->admin->notifications()->findOrFail($id)->read_at,
            'The notification should have been marked as read.',
        );

        $this->actingAs($this->admin)
            ->getJson(route('notifications.index'))
            ->assertJsonPath('unread_count', 0);
    }

    public function test_marking_everything_read_clears_the_bell(): void
    {
        $this->notify($this->admin, 'Ride confirmed', 'First.');
        $this->notify($this->admin, 'Ride rejected', 'Second.');

        $this->actingAs($this->admin)
            ->postJson(route('notifications.read-all'))
            ->assertOk();

        $this->actingAs($this->admin)
            ->getJson(route('notifications.index'))
            ->assertExactJson(['unread_count' => 0, 'notifications' => []]);
    }

    public function test_a_dispatcher_never_sees_another_dispatchers_notifications(): void
    {
        $this->notify($this->associate, 'Ride confirmed', 'Only for the associate.');

        $this->actingAs($this->admin)
            ->getJson(route('notifications.index'))
            ->assertExactJson(['unread_count' => 0, 'notifications' => []]);
    }

    public function test_a_notification_of_somebody_else_cannot_be_marked_read(): void
    {
        $id = $this->notify($this->associate, 'Ride confirmed', 'Only for the associate.');

        $this->actingAs($this->admin)
            ->postJson(route('notifications.read', ['notification' => $id]))
            ->assertNotFound();

        $this->assertNull(
            $this->associate->notifications()->findOrFail($id)->read_at,
            'The notification must stay unread for its real owner.',
        );
    }

    public function test_the_associate_panel_can_reach_the_bell(): void
    {
        $id = $this->notify($this->associate, 'Ride rejected', 'The driver refused.');

        $this->actingAs($this->associate)
            ->getJson(route('notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1);

        $this->actingAs($this->associate)
            ->postJson(route('notifications.read', ['notification' => $id]))
            ->assertOk();
    }

    public function test_a_driver_cannot_reach_the_bell(): void
    {
        $this->actingAs($this->driver)
            ->getJson(route('notifications.index'))
            ->assertRedirect(route('driver.dashboard'));
    }

    public function test_a_guest_cannot_read_the_bell(): void
    {
        // A JSON caller gets a 401 rather than the login redirect a browser tab
        // would be sent to.
        $this->getJson(route('notifications.index'))
            ->assertUnauthorized();

        $this->get(route('notifications.index'))
            ->assertRedirect(route('login'));
    }

    public function test_the_bell_is_rendered_in_both_navigation_bars(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertSee('notificationBell', false);

        $this->actingAs($this->associate)
            ->get(route('associate.dashboard'))
            ->assertSee('notificationBell', false);
    }

    /**
     * Write a database channel notification for a user, exactly as the database
     * channel of a real notification would.
     */
    private function notify(User $user, string $title, string $body): string
    {
        NotificationFacade::sendNow($user, new BellTestNotification($title, $body));

        return $user->notifications()->latest()->firstOrFail()->id;
    }
}

/**
 * A minimal database-only notification, so the bell is exercised without the web
 * push channel that the real notification also uses.
 */
class BellTestNotification extends Notification
{
    /**
     * @param  array<int, string>  $channels
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'url' => '/admin/bookings/1',
        ];
    }

    public function __construct(private string $title, private string $body) {}
}
