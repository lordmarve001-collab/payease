<?php

namespace App\Livewire\AjoOwner;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsSettings extends Component
{
    public bool $emailAlerts = true;
    public bool $smsAlerts = true;
    public bool $payoutReminders = true;
    public bool $contributionAlerts = true;
    public bool $agentActivity = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->emailAlerts = (bool) ($user->notify_email ?? true);
        $this->smsAlerts = (bool) ($user->notify_sms ?? true);
        $this->payoutReminders = (bool) ($user->notify_payout ?? true);
        $this->contributionAlerts = (bool) ($user->notify_contribution ?? true);
        $this->agentActivity = (bool) ($user->notify_agent_activity ?? false);
    }

    public function save(): void
    {
        $user = Auth::user();
        $user->update([
            'notify_email' => $this->emailAlerts,
            'notify_sms' => $this->smsAlerts,
            'notify_payout' => $this->payoutReminders,
            'notify_contribution' => $this->contributionAlerts,
            'notify_agent_activity' => $this->agentActivity,
        ]);

        $this->dispatch('notify-success', message: 'Notification preferences saved.');
    }

    public function render()
    {
        return view('livewire.ajo-owner.notifications-settings')
            ->layout('components.layouts.ajo-owner');
    }
}
