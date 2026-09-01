<?php

namespace Tests\Feature;

use App\Events\NotificationPushed;
use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Menu;
use App\Models\User;
use App\Notifications\CustomNotification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPushEnterpriseTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_notification_triggers_queued_notification_pushed_event_with_normalized_payload(): void
    {
        Event::fake([NotificationPushed::class]);

        $user = User::factory()->create();

        $notification = new CustomNotification(
            subject: 'System Maintenance',
            message: 'Server will restart in 10 minutes.',
            actionUrl: 'https://example.com/maintenance'
        );

        $user->notify($notification);

        Event::assertDispatched(NotificationPushed::class, function (NotificationPushed $event) use ($user) {
            $this->assertInstanceOf(ShouldBroadcast::class, $event);
            $this->assertSame('default', $event->broadcastQueue);
            $this->assertSame($user->id, $event->userId);
            $this->assertSame(['private-users.' . $user->id], array_map(fn ($ch) => (string) $ch->name, $event->broadcastOn()));
            $this->assertSame('NotificationPushed', $event->broadcastAs());

            $payload = $event->broadcastWith();
            $this->assertSame('System Maintenance', $payload['title']);
            $this->assertSame('Server will restart in 10 minutes.', $payload['message']);
            $this->assertSame('https://example.com/maintenance', $payload['url']);
            $this->assertSame('info', $payload['category']);
            $this->assertTrue($payload['is_unread']);

            return true;
        });
    }

    public function test_notification_feed_controller_endpoints(): void
    {
        $user = User::factory()->create();

        $user->notify(new CustomNotification('Notice 1', 'Message 1'));
        $user->notify(new CustomNotification('Notice 2', 'Message 2'));

        $this->actingAs($user);

        // 1. Unread count endpoint
        $countResponse = $this->getJson('/notifications/unread-count');
        $countResponse->assertStatus(200)
            ->assertJson(['unread' => 2]);

        // 2. Feed endpoint
        $feedResponse = $this->getJson('/notifications/feed');
        $feedResponse->assertStatus(200)
            ->assertJsonCount(2, 'items');

        // 3. Mark all as read endpoint
        $markResponse = $this->postJson('/notifications/mark-all-as-read');
        $markResponse->assertStatus(200)
            ->assertJson(['ok' => true]);

        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_bell_livewire_component_lifecycle_and_in_memory_events(): void
    {
        $user = User::factory()->create();
        $user->notify(new CustomNotification('Hello', 'Welcome to the system'));

        Livewire::actingAs($user)
            ->test(Bell::class)
            ->assertSet('unreadCount', 1)
            ->dispatch('increment-unread-count')
            ->assertSet('unreadCount', 2)
            ->dispatch('notification-received', ['notification' => ['title' => 'New Event']])
            ->assertSet('unreadCount', 3)
            ->dispatch('reset-unread-count')
            ->assertSet('unreadCount', 0)
            ->call('markAllAsRead')
            ->assertSet('unreadCount', 0)
            ->assertDispatched('notifs-marked-all-read');
    }

    public function test_menu_livewire_component_lifecycle_and_in_memory_events(): void
    {
        $user = User::factory()->create();
        $user->notify(new CustomNotification('Hello 1', 'Message 1'));

        Livewire::actingAs($user)
            ->test(Menu::class)
            ->assertSet('unreadCount', 1)
            ->dispatch('notification-received')
            ->assertSet('unreadCount', 1)
            ->dispatch('notifs-marked-all-read')
            ->assertSet('unreadCount', 1);

        $user->notify(new CustomNotification('Hello 2', 'Message 2'));

        Livewire::actingAs($user)
            ->test(Menu::class)
            ->assertSet('unreadCount', 2)
            ->call('markAllRead')
            ->assertSet('unreadCount', 0);
    }
}
