<?php

namespace App\Livewire\Admin;

use App\Models\AjoPayoutQueue as AjoPayoutQueueModel;
use App\Models\User;
use App\Services\AjoPayoutQueueService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class AjoPayoutQueue extends Component
{
    use WithPagination;

    public string $filter = 'pending';
    public ?string $confirmingItemId = null;
    public ?string $rejectingItemId = null;
    public string $rejectNote = '';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function confirmProcess(string $itemId): void
    {
        $this->confirmingItemId = $itemId;
    }

    public function cancelProcess(): void
    {
        $this->confirmingItemId = null;
    }

    public function processItem(): void
    {
        if (!$this->confirmingItemId) {
            return;
        }

        try {
            /** @var User $admin */
            $admin = Auth::user();
            $service = app(AjoPayoutQueueService::class);
            $service->processQueueItem($this->confirmingItemId, $admin);
            $this->cancelProcess();
            $this->dispatch('notify-success', message: 'Ajo payout credited to member wallet.');
        } catch (RuntimeException $e) {
            $this->dispatch('notify-error', message: $e->getMessage());
            $this->cancelProcess();
        }
    }

    public function confirmReject(string $itemId): void
    {
        $this->rejectingItemId = $itemId;
        $this->rejectNote = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingItemId = null;
        $this->rejectNote = '';
    }

    public function rejectItem(): void
    {
        if (!$this->rejectingItemId) {
            return;
        }

        try {
            /** @var User $admin */
            $admin = Auth::user();
            $service = app(AjoPayoutQueueService::class);
            $service->rejectQueueItem($this->rejectingItemId, $admin, $this->rejectNote);
            $this->cancelReject();
            $this->dispatch('notify-success', message: 'Ajo payout queue item rejected.');
        } catch (RuntimeException $e) {
            $this->dispatch('notify-error', message: $e->getMessage());
            $this->cancelReject();
        }
    }

    public function render()
    {
        $query = AjoPayoutQueueModel::query()
            ->with(['group', 'memberUser', 'agent.user', 'ajoPayout.transaction']);

        if ($this->filter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($this->filter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($this->filter === 'failed') {
            $query->where('status', 'failed');
        }

        $items = $query->latest()->paginate(15);

        $selectedItem = $this->confirmingItemId
            ? AjoPayoutQueueModel::with(['group', 'memberUser', 'agent.user'])->find($this->confirmingItemId)
            : null;

        return view('livewire.admin.ajo-payout-queue', [
            'items' => $items,
            'selectedItem' => $selectedItem,
            'pendingCount' => AjoPayoutQueueModel::where('status', 'pending')->count(),
        ])->layout('components.layouts.admin');
    }
}
