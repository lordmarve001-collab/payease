<?php

namespace App\Livewire\Admin;

use App\Models\AjoGroup;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class AjoGroups extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';

    public bool $showModal = false;
    public string $selectedGroupId = '';

    public bool $showConfirm = false;
    public string $confirmType = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function viewGroup(string $id): void
    {
        $this->selectedGroupId = $id;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedGroupId = '';
        $this->showConfirm = false;
        $this->confirmType = '';
    }

    public function promptConfirm(string $type): void
    {
        $this->confirmType = $type;
        $this->showConfirm = true;
    }

    public function cancelConfirm(): void
    {
        $this->showConfirm = false;
        $this->confirmType = '';
    }

    public function executeConfirm(): void
    {
        $newStatus = match ($this->confirmType) {
            'approve', 'activate' => 'active',
            'suspend' => 'suspended',
            default => null,
        };

        if ($newStatus === null || $this->selectedGroupId === '') {
            $this->cancelConfirm();
            return;
        }

        $group = AjoGroup::findOrFail($this->selectedGroupId);
        $oldStatus = (string) $group->status;

        $group->update([
            'status' => $newStatus,
            'start_date' => $group->start_date ?? ($newStatus === 'active' ? now() : $group->start_date),
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => match ($this->confirmType) {
                'approve' => 'ajo_group_approved',
                default => 'ajo_group_' . $this->confirmType . 'd',
            },
            'entity_type' => 'ajo_group',
            'entity_id' => $group->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $label = $this->confirmType;
        $this->cancelConfirm();
        $this->closeModal();
        $this->dispatch('notify-success', message: "Group {$label}d successfully.");
    }

    public function render()
    {
        $groups = AjoGroup::query()
            ->with(['managingAgent', 'managingAgent.user'])
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($query): void {
                    $query->where('name', 'like', "%{$this->search}%")
                        ->orWhereHas('managingAgent.user', function ($q2) {
                            $q2->where('full_name', 'like', "%{$this->search}%");
                        })
                        ->orWhereHas('managingAgent', function ($q3) {
                            $q3->where('business_name', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);

        $selectedGroup = null;
        if ($this->showModal && $this->selectedGroupId !== '') {
            $selectedGroup = AjoGroup::with([
                'managingAgent.user',
                'members.user',
                'agents.user',
                'contributions.user',
            ])->find($this->selectedGroupId);
        }

        return view('livewire.admin.ajo-groups', [
            'groups' => $groups,
            'selectedGroup' => $selectedGroup,
        ])->layout('components.layouts.admin');
    }
}
