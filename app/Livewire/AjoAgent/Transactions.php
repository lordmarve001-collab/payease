<?php

namespace App\Livewire\AjoAgent;

use App\Models\Agent;
use App\Models\AjoContribution;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;

class Transactions extends Component
{
    use WithPagination;

    public string $filter = 'all';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $contributions = collect();
        $paginator = null;

        if ($agent) {
            $query = AjoContribution::query()
                ->where('logged_by_agent_id', $agent->id)
                ->with(['user', 'group', 'transaction'])
                ->latest();

            if ($this->filter !== 'all') {
                $query->where('status', $this->filter);
            }

            $paginator = $query->paginate(15);
            $contributions = $paginator->getCollection()->groupBy(fn ($c) => $c->created_at->toDateString());
        }

        return view('livewire.ajo-agent.transactions', [
            'agent' => $agent,
            'contributions' => $contributions,
            'paginator' => $paginator,
        ])->layout('components.layouts.ajo-agent');
    }
}
