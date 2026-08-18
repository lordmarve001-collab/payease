<?php

namespace App\Livewire\AjoOwner;

use App\Models\AjoGroup;
use App\Models\AjoOwner;
use App\Models\User;
use App\Services\AjoService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Groups extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $frequencyFilter = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFrequencyFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var AjoOwner $ajoOwner */
        $ajoOwner = $user->ajoOwner;
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $query = AjoGroup::query()
            ->where('ajo_owner_id', $ajoOwner->id)
            ->with(['managingAgent.user'])
            ->withCount('members')
            ->withSum('contributions as total_contributed', 'amount');

        if ($this->search !== '') {
            $search = '%' . trim($this->search) . '%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', $search)
                    ->orWhereHas('managingAgent', function ($agentQuery) use ($search): void {
                        $agentQuery->where('business_name', 'like', $search)
                            ->orWhereHas('user', function ($userQuery) use ($search): void {
                                $userQuery->where('full_name', 'like', $search)
                                    ->orWhere('phone_number', 'like', $search);
                            });
                    });
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->frequencyFilter !== 'all') {
            $query->where('frequency', $this->frequencyFilter);
        }

        $groups = $query->latest()->paginate(10);
        $progressByGroup = [];
        foreach ($groups as $group) {
            $progressByGroup[$group->id] = $ajoService->getCycleProgress($group);
        }

        return view('livewire.ajo-owner.groups', [
            'groups' => $groups,
            'progressByGroup' => $progressByGroup,
        ])->layout('components.layouts.ajo-owner');
    }
}
