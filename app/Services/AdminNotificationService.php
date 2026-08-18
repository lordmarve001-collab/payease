<?php

namespace App\Services;

use App\Models\AdminNotification;

class AdminNotificationService
{
    public function create(array $data): AdminNotification
    {
        return AdminNotification::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'message' => $data['message'],
            'action_url' => $data['action_url'] ?? null,
            'action_label' => $data['action_label'] ?? null,
            'severity' => $data['severity'] ?? 'info',
            'is_read' => false,
            'related_id' ?? null => $data['related_id'] ?? null,
            'related_type' => $data['related_type'] ?? null,
        ]);
    }

    public function getUnreadCount(): int
    {
        return AdminNotification::unread()->count();
    }

    public function getRecent(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return AdminNotification::latest()->take($limit)->get();
    }

    public function markAllRead(): void
    {
        AdminNotification::unread()->update(['is_read' => true]);
    }

    public function markRead(string $id): void
    {
        AdminNotification::where('id', $id)->update(['is_read' => true]);
    }

    public function clearOld(int $days = 90): int
    {
        return AdminNotification::where('created_at', '<', now()->subDays($days))->delete();
    }
}
