<?php

namespace App\Livewire\Admin;

use App\Models\AdminNotification;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $open = false;
    public int $unreadCount = 0;
    public $notifications;

    protected $listeners = ['notificationCreated' => '$refresh'];

    public function mount(): void
    {
        $this->unreadCount = AdminNotification::unread()->count();
        $this->notifications = AdminNotification::latest()->take(15)->get();
    }

    public function markAllRead(): void
    {
        AdminNotification::unread()->update(['is_read' => true]);
        $this->unreadCount = 0;
        $this->notifications = AdminNotification::latest()->take(15)->get();
    }

    public function render()
    {
        return view('livewire.admin.notification-bell');
    }
}
