<?php

namespace App\Livewire\Customer;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationsSettings extends Component
{
    public bool $notifySms = false;
    public bool $notifyEmail = false;
    public bool $notifyPayout = false;
    public bool $notifyContribution = false;
    public bool $notifyAgentActivity = false;

    public function mount()
    {
        $user = Auth::user();
        $this->notifySms = (bool) $user->notify_sms;
        $this->notifyEmail = (bool) $user->notify_email;
        $this->notifyPayout = (bool) $user->notify_payout;
        $this->notifyContribution = (bool) $user->notify_contribution;
        $this->notifyAgentActivity = (bool) $user->notify_agent_activity;
    }

    public function updated($property)
    {
        $field = match ($property) {
            'notifySms' => 'notify_sms',
            'notifyEmail' => 'notify_email',
            'notifyPayout' => 'notify_payout',
            'notifyContribution' => 'notify_contribution',
            'notifyAgentActivity' => 'notify_agent_activity',
            default => null,
        };

        if ($field) {
            Auth::user()->update([$field => $this->$property]);
            $label = match ($property) {
                'notifySms' => 'SMS',
                'notifyEmail' => 'Email',
                'notifyPayout' => 'Payout alerts',
                'notifyContribution' => 'Contribution alerts',
                'notifyAgentActivity' => 'Agent activity alerts',
                default => 'Notification',
            };
            $state = $this->$property ? 'enabled' : 'disabled';
            $this->dispatch('notify-success', message: "{$label} {$state}!");
        }
    }

    public function render()
    {
        return view('livewire.customer.notifications-settings')
            ->layout('components.layouts.customer');
    }
}
