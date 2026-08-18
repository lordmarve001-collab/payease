<?php

namespace App\Livewire\Agent;

use App\Services\FloatSettlementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class RequestTopUp extends Component
{
    public float $amount = 0;
    public string $reason = '';
    public string $successMessage = '';
    public string $errorMessage = '';

    protected function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function submit(FloatSettlementService $service): void
    {
        $this->resetErrorBag();
        $this->successMessage = '';
        $this->errorMessage = '';
        $this->validate();

        $agent = Auth::user()->agent;

        if (!$agent) {
            $this->errorMessage = 'Agent profile not found.';
            return;
        }

        try {
            $service->requestTopUp($agent, $this->amount, $this->reason ?: null);
            $this->successMessage = 'Your top-up request has been submitted for approval.';
            $this->reset('amount', 'reason');
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $agent = Auth::user()->agent;
        $pendingTopUp = $agent?->pendingTopUpRequest()->first();

        return view('livewire.agent.request-topup', [
            'agent' => $agent,
            'pendingTopUp' => $pendingTopUp,
        ])->layout('components.layouts.agent');
    }
}
