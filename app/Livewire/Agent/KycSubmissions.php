<?php

namespace App\Livewire\Agent;

use App\Models\Agent;
use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class KycSubmissions extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent $agent */
        $agent = $user->agent;

        $documents = KycDocument::query()
            ->with('user')
            ->where('submitted_by_agent_id', (string) $agent->id)
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('verification_status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        return view('livewire.agent.kyc-submissions', [
            'documents' => $documents,
        ])->layout('components.layouts.agent');
    }
}
