<?php

namespace App\Livewire\Agent;

use App\Models\AgentSettlement;
use App\Services\FloatSettlementService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettleFloat extends Component
{
    use WithFileUploads;

    public float $amount = 0;
    public string $bankReference = '';
    public $proofOfDeposit = null;
    public string $successMessage = '';
    public string $errorMessage = '';

    protected function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'bankReference' => 'required|string|max:255',
            'proofOfDeposit' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }

    public function declare(FloatSettlementService $service): void
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

        $proofUrl = null;

        if ($this->proofOfDeposit) {
            $proofUrl = $this->proofOfDeposit->store('settlement-proofs', 'public');
        }

        try {
            $service->declareSettlement($agent, $this->amount, $this->bankReference, $proofUrl);
            $this->successMessage = 'Your settlement declaration has been submitted for verification.';
            $this->reset('amount', 'bankReference', 'proofOfDeposit');
        } catch (\RuntimeException $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render()
    {
        $agent = Auth::user()->agent;

        $settlements = $agent
            ? AgentSettlement::where('agent_id', $agent->id)->latest()->paginate(10)
            : collect();

        return view('livewire.agent.settle-float', [
            'agent' => $agent,
            'settlements' => $settlements,
        ])->layout('components.layouts.agent');
    }
}
