<?php

namespace App\Livewire\Admin;

use App\Models\AgentSettlement;
use App\Models\FloatTopUpRequest;
use App\Services\FloatSettlementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class FloatManagement extends Component
{
    use WithPagination;

    // Top-Up modal
    public bool $showTopUpModal = false;
    public ?string $selectedTopUpId = null;
    public string $topUpAction = '';
    public string $rejectionReason = '';

    // Settlement modal
    public bool $showSettlementModal = false;
    public ?string $selectedSettlementId = null;
    public string $settlementAction = '';

    protected function rules(): array
    {
        return [
            'rejectionReason' => 'required_if:topUpAction,reject|required_if:settlementAction,reject|string|min:5|max:500',
        ];
    }

    public function confirmTopUp(string $requestId, string $action): void
    {
        $this->selectedTopUpId = $requestId;
        $this->topUpAction = $action;
        $this->rejectionReason = '';
        $this->showTopUpModal = true;
    }

    public function closeTopUpModal(): void
    {
        $this->reset('showTopUpModal', 'selectedTopUpId', 'topUpAction', 'rejectionReason');
    }

    public function confirmSettlement(string $settlementId, string $action): void
    {
        $this->selectedSettlementId = $settlementId;
        $this->settlementAction = $action;
        $this->rejectionReason = '';
        $this->showSettlementModal = true;
    }

    public function closeSettlementModal(): void
    {
        $this->reset('showSettlementModal', 'selectedSettlementId', 'settlementAction', 'rejectionReason');
    }

    public function processTopUp(FloatSettlementService $service): void
    {
        $admin = Auth::user();

        if ($this->topUpAction === 'approve') {
            $request = FloatTopUpRequest::findOrFail($this->selectedTopUpId);
            $service->approveTopUp($request, $admin);
            $this->dispatch('notify-success', 'Top-up approved successfully.');
        } elseif ($this->topUpAction === 'reject') {
            $this->validate();
            $request = FloatTopUpRequest::findOrFail($this->selectedTopUpId);
            $service->rejectTopUp($request, $admin, $this->rejectionReason);
            $this->dispatch('notify-success', 'Top-up rejected.');
        }

        $this->closeTopUpModal();
    }

    public function processSettlement(FloatSettlementService $service): void
    {
        $admin = Auth::user();

        if ($this->settlementAction === 'verify') {
            $settlement = AgentSettlement::findOrFail($this->selectedSettlementId);
            $service->verifySettlement($settlement, $admin);
            $this->dispatch('notify-success', 'Settlement verified successfully.');
        } elseif ($this->settlementAction === 'reject') {
            $this->validate();
            $settlement = AgentSettlement::findOrFail($this->selectedSettlementId);
            $service->rejectSettlement($settlement, $admin, $this->rejectionReason);
            $this->dispatch('notify-success', 'Settlement rejected.');
        }

        $this->closeSettlementModal();
    }

    public function render(FloatSettlementService $service)
    {
        return view('livewire.admin.float-management', [
            'pendingTopUps' => $service->getPendingTopUpRequests(),
            'pendingSettlements' => $service->getPendingSettlements(),
        ])->layout('components.layouts.admin');
    }
}
