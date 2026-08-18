<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;

class Users extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?string $selectedUserId = null;
    public bool $showStatusModal = false;
    public bool $showUnlockModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmToggleStatus(string $userId): void
    {
        $this->selectedUserId = $userId;
        $this->showStatusModal = true;
    }

    public function closeStatusModal(): void
    {
        $this->reset('showStatusModal', 'selectedUserId');
    }

    public function confirmUnlock(string $userId): void
    {
        $this->selectedUserId = $userId;
        $this->showUnlockModal = true;
    }

    public function closeUnlockModal(): void
    {
        $this->reset('showUnlockModal', 'selectedUserId');
    }

    public function toggleStatus(): void
    {
        $user = User::findOrFail((string) $this->selectedUserId);
        $oldStatus = (string) $user->status;
        $newStatus = $oldStatus === 'suspended' ? 'active' : 'suspended';

        $user->update(['status' => $newStatus]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $newStatus === 'suspended' ? 'user_suspended' : 'user_activated',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => $newStatus],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: $newStatus === 'suspended' ? 'User suspended successfully.' : 'User activated successfully.');
        $this->closeStatusModal();
    }

    public function unlockAccount(): void
    {
        $user = User::findOrFail((string) $this->selectedUserId);

        $loginLockKey = 'login_lock_' . $user->phone_number;
        $loginAttemptKey = 'login_attempts_' . $user->phone_number;
        Cache::forget($loginLockKey);
        Cache::forget($loginAttemptKey);

        $customerPinLockKey = 'customer_pin_lock_send_' . $user->id;
        $customerPinAttemptKey = 'customer_pin_attempts_send_' . $user->id;
        Cache::forget($customerPinLockKey);
        Cache::forget($customerPinAttemptKey);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'account_unlocked',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'old_values' => null,
            'new_values' => [
                'reason' => 'Admin manual unlock',
            ],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'Account unlocked successfully.');
        $this->closeUnlockModal();
    }

    public function render()
    {
        $users = User::with('registeredByAgent')
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($query): void {
                    $query->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone_number', 'like', "%{$this->search}%");
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.users', [
            'users' => $users,
            'selectedUser' => $this->selectedUserId ? User::find($this->selectedUserId) : null,
        ])->layout('components.layouts.admin');
    }
}
