<?php

namespace App\Livewire\Agent;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Customers extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        $agent = Auth::user()->agent;

        $customers = User::query()
            ->where('registered_by_agent_id', $agent?->id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('full_name', 'like', "%{$this->search}%")
                      ->orWhere('phone_number', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.agent.customers', ['customers' => $customers])
            ->layout('components.layouts.agent');
    }
}
