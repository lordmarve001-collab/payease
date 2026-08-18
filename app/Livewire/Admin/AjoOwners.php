<?php

namespace App\Livewire\Admin;

use App\Models\AjoOwner;
use App\Models\AuditLog;
use App\Services\AjoOwnerApplicationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class AjoOwners extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?string $selectedOwnerId = null;
    public string $pendingAction = '';
    public bool $showActionModal = false;
    public bool $showDetailModal = false;
    public ?AjoOwner $detailOwner = null;
    public string $rejectionReason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmOwnerAction(string $ownerId, string $action): void
    {
        $this->selectedOwnerId = $ownerId;
        $this->pendingAction = $action;
        $this->showActionModal = true;
        $this->rejectionReason = '';
    }

    public function closeActionModal(): void
    {
        $this->showActionModal = false;
        $this->selectedOwnerId = null;
        $this->pendingAction = '';
        $this->rejectionReason = '';
    }

    public function viewDetail(string $ownerId): void
    {
        $this->detailOwner = AjoOwner::with('user')->find($ownerId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailOwner = null;
    }

    public function runOwnerAction(): void
    {
        /** @var AjoOwner $owner */
        $owner = AjoOwner::with('user')->findOrFail((string) $this->selectedOwnerId);
        $oldStatus = (string) $owner->status;

        if ($this->pendingAction === 'approve') {
            app(AjoOwnerApplicationService::class)->approve($owner, Auth::user());

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'ajo_owner_approved',
                'entity_type' => 'ajo_owner',
                'entity_id' => $owner->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'active', 'approved_at' => now()->toIso8601String()],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Ajo Owner approved successfully.');
        } elseif ($this->pendingAction === 'reject') {
            $this->validate([
                'rejectionReason' => ['required', 'string', 'min:3'],
            ]);

            app(AjoOwnerApplicationService::class)->reject($owner, Auth::user(), $this->rejectionReason);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'ajo_owner_rejected',
                'entity_type' => 'ajo_owner',
                'entity_id' => $owner->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'rejected', 'reason' => $this->rejectionReason],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Ajo Owner application rejected.');
        } elseif ($this->pendingAction === 'suspend') {
            $owner->update(['status' => 'suspended']);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'ajo_owner_suspended',
                'entity_type' => 'ajo_owner',
                'entity_id' => $owner->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'suspended'],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Ajo Owner suspended.');
        }

        $this->closeActionModal();
    }

    public function render()
    {
        $owners = AjoOwner::query()
            ->with('user')
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($query): void {
                    $query->where('business_name', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($q2) {
                            $q2->where('full_name', 'like', "%{$this->search}%")
                                ->orWhere('phone_number', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.ajo-owners', [
            'owners' => $owners,
            'selectedOwner' => $this->selectedOwnerId ? AjoOwner::with('user')->find($this->selectedOwnerId) : null,
        ])->layout('components.layouts.admin');
    }
}
