<?php

namespace App\Listeners;

use App\Events\NotificationPushed;
use App\Infrastructure\Persistence\Eloquent\Models\User as InfrastructureUser;
use App\Models\User;
use Illuminate\Support\Str;

class BroadcastNotificationPushed
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        // Only when the 'database' channel is used
        if ($event->channel !== 'database') {
            return;
        }

        if (! ($event->notifiable instanceof User || $event->notifiable instanceof InfrastructureUser)) {
            return;
        }

        $raw = [];
        if (method_exists($event->notification, 'toDatabase')) {
            $raw = $event->notification->toDatabase($event->notifiable);
        } elseif (method_exists($event->notification, 'toArray')) {
            $raw = $event->notification->toArray($event->notifiable);
        }

        if (! is_array($raw)) {
            $raw = (array) $raw;
        }

        $category = data_get($raw, 'category', 'info');
        $validCategories = ['success', 'danger', 'warning', 'info'];
        $category = in_array($category, $validCategories, strict: true) ? $category : 'info';

        $icon = data_get($raw, 'icon') ?? match ($category) {
            'success' => 'bx bx-check-circle',
            'danger' => 'bx bx-x-circle',
            'warning' => 'bx bx-error',
            default => 'bx bx-bell',
        };

        $title = $this->firstFilled($raw, ['title', 'subject', 'name'])
            ?? Str::headline(class_basename($event->notification));

        $message = $this->firstFilled($raw, ['message', 'body', 'content', 'description', 'detail', 'text'])
            ?? '';

        $url = $this->firstFilled($raw, ['action_url', 'url', 'link']);
        if (! $url && is_array($route = data_get($raw, 'route'))) {
            try {
                $url = route($route['name'] ?? '', $route['params'] ?? []);
            } catch (\Throwable) {
                // ignore bad route payloads
            }
        }

        $payload = [
            'id' => (string) ($event->notification->id ?? Str::uuid()),
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'category' => $category,
            'icon' => $icon,
            'is_unread' => true,
            'created_at' => now()->toIso8601String(),
        ];

        event(new NotificationPushed($event->notifiable->id, $payload));
    }

    protected function firstFilled(array $data, array $keys): ?string
    {
        foreach ($keys as $k) {
            $v = data_get($data, $k);
            if (is_string($v) && trim($v) !== '') {
                return $v;
            }
            if (is_numeric($v)) {
                return (string) $v;
            }
        }

        return null;
    }
}
