<?php

namespace App\View\Components;

use App\Models\AdminNotification;
use Illuminate\View\Component;

class AdminNotificationBell extends Component
{
    public int $unreadCount;

    public function __construct()
    {
        $this->unreadCount = AdminNotification::unread()->count();
    }

    public function render()
    {
        return view('components.admin-notification-bell');
    }
}
