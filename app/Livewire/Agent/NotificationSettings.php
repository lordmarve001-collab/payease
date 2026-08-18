<?php

namespace App\Livewire\Agent;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationSettings extends Component
{
    public bool $notifySms = false;
    public bool $notifyEmail = false;
    public bool $notifyAgentActivity = false;

    public function mount()
    {
        $user = Auth::user();
        $this->notifySms = (bool) $user->notify_sms;
        $this->notifyEmail = (bool) $user->notify_email;
        $this->notifyAgentActivity = (bool) $user->notify_agent_activity;
    }

    public function updated($property)
    {
        $field = match ($property) {
            'notifySms' => 'notify_sms',
            'notifyEmail' => 'notify_email',
            'notifyAgentActivity' => 'notify_agent_activity',
            default => null,
        };

        if ($field) {
            Auth::user()->update([$field => $this->$property]);
            $label = match ($property) {
                'notifySms' => 'SMS',
                'notifyEmail' => 'Email',
                'notifyAgentActivity' => 'Agent activity alerts',
                default => 'Notification',
            };
            $state = $this->$property ? 'enabled' : 'disabled';
            $this->dispatch('notify-success', message: "{$label} {$state}!");
        }
    }

    public function render()
    {
        return view('livewire.agent.notification-settings')
            ->layout('components.layouts.agent');
    }
}
