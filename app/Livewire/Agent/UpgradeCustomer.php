<?php

namespace App\Livewire\Agent;

use App\Helpers\PhoneNumberHelper;
use App\Models\KycDocument;
use App\Models\User;
use App\Services\AgentCustomerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithFileUploads;

class UpgradeCustomer extends Component
{
    use WithFileUploads;

    public int $step = 1;
    public string $searchPhone = '';
    public ?User $customer = null;

    public int $targetTier = 2;
    public string $nin = '';
    public string $bvn = '';
    public string $nextOfKinName = '';
    public string $nextOfKinRelationship = '';
    public string $nextOfKinPhone = '';

    public $ninSlip = null;
    public $bvnSlip = null;
    public $livenessCapture = null;
    public $proofOfAddress = null;
    public $addressIndemnityForm = null;

    public string $customerPin = '';
    public bool $pinVerified = false;

    protected function getService(): AgentCustomerService
    {
        return app(AgentCustomerService::class);
    }

    public function updatedSearchPhone(): void
    {
        $this->validateOnly('searchPhone', [
            'searchPhone' => ['required', 'string'],
        ]);
    }

    public function searchCustomer(): void
    {
        $this->validate([
            'searchPhone' => ['required', 'string'],
        ]);

        try {
            $normalized = PhoneNumberHelper::normalize($this->searchPhone);
        } catch (\Exception $e) {
            $this->addError('searchPhone', 'Invalid phone number.');
            return;
        }

        $user = User::where('phone_number', $normalized)->whereDoesntHave('roles', function ($q): void {
            $q->whereIn('name', ['admin', 'super_admin', 'agent', 'ajo_owner']);
        })->first();

        if (!$user) {
            $this->addError('searchPhone', 'No customer found with this phone number.');
            return;
        }

        $this->customer = $user;
        $this->step = 2;
    }

    public function selectTier(int $tier): void
    {
        if ($this->customer === null) {
            return;
        }

        $currentLevel = (int) $this->customer->kyc_level;

        if ($tier <= $currentLevel) {
            $this->dispatch('notify-error', message: "Customer is already at Tier {$currentLevel}.");
            return;
        }

        if ($tier === 3 && $currentLevel < 2) {
            $this->dispatch('notify-error', message: 'Customer must complete Tier 2 first.');
            return;
        }

        $this->targetTier = $tier;
        $this->prefillExistingData();
        $this->step = 3;
    }

    protected function prefillExistingData(): void
    {
        if (! $this->customer) {
            return;
        }

        if (blank($this->customer->nin_verified_at) && filled($this->customer->nin)) {
            $this->nin = $this->customer->nin;
        }

        if (blank($this->customer->bvn_verified_at) && filled($this->customer->bvn)) {
            $this->bvn = $this->customer->bvn;
        }

        if (blank($this->customer->next_of_kin_submitted_at)) {
            $this->nextOfKinName = $this->customer->next_of_kin_name ?? '';
            $this->nextOfKinRelationship = $this->customer->next_of_kin_relationship ?? '';
            $this->nextOfKinPhone = $this->customer->next_of_kin_phone ?? '';
        }
    }

    public function ninAlreadyVerified(): bool
    {
        return filled($this->customer?->nin_verified_at);
    }

    public function bvnAlreadyVerified(): bool
    {
        return filled($this->customer?->bvn_verified_at);
    }

    public function nextOfKinAlreadySubmitted(): bool
    {
        return filled($this->customer?->next_of_kin_submitted_at);
    }

    public function goBackToCustomer(): void
    {
        $this->step = 2;
    }

    public function goBackToTierSelection(): void
    {
        $this->resetFormData();
        $this->step = 2;
    }

    protected function resetFormData(): void
    {
        $this->nin = '';
        $this->bvn = '';
        $this->nextOfKinName = '';
        $this->nextOfKinRelationship = '';
        $this->nextOfKinPhone = '';
        $this->ninSlip = null;
        $this->bvnSlip = null;
        $this->livenessCapture = null;
        $this->proofOfAddress = null;
        $this->addressIndemnityForm = null;
    }

    public function submitTierDetails(): void
    {
        if ($this->targetTier === 2) {
            $rules = [
                'nin' => $this->ninAlreadyVerified() ? ['nullable', 'string', 'size:11'] : ['required', 'string', 'size:11'],
                'bvn' => $this->bvnAlreadyVerified() ? ['nullable', 'string', 'size:11'] : ['required', 'string', 'size:11'],
                'nextOfKinName' => $this->nextOfKinAlreadySubmitted() ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
                'nextOfKinRelationship' => $this->nextOfKinAlreadySubmitted() ? ['nullable', 'string', 'max:255'] : ['required', 'string', 'max:255'],
                'nextOfKinPhone' => $this->nextOfKinAlreadySubmitted() ? ['nullable', 'string'] : ['required', 'string'],
                'ninSlip' => ['nullable', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
                'bvnSlip' => ['nullable', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
                'livenessCapture' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            ];

            $this->validate($rules);
        } else {
            $this->validate([
                'proofOfAddress' => ['nullable', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
                'addressIndemnityForm' => ['nullable', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
            ], [
                'proofOfAddress.required' => 'A proof of address or indemnity form is required.',
            ]);
        }

        $this->step = 4;
    }

    public function verifyCustomerPin(): void
    {
        $this->validate([
            'customerPin' => ['required', 'string', 'digits:6'],
        ]);

        $key = 'agent-upgrade-pin-' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('customerPin', 'Too many attempts. Please start over.');
            return;
        }

        $customer = User::find($this->customer?->id);
        if (!$customer) {
            $this->dispatch('notify-error', message: 'Customer not found. Please search again.');
            return;
        }

        if ($customer->verifyTransferPin($this->customerPin)) {
            $this->pinVerified = true;
            $this->customer = $customer;
            RateLimiter::clear($key);
            $this->submitUpgrade();
        } else {
            RateLimiter::hit($key, 60);
            $this->addError('customerPin', 'Incorrect PIN.');
        }
    }

    protected function submitUpgrade(): void
    {
        $agent = Auth::user()->agent;
        if (!$agent || !$this->customer) {
            $this->dispatch('notify-error', message: 'Session error. Please start over.');
            return;
        }

        try {
            $documents = [];
            if ($this->targetTier === 2) {
                $documents['nin_slip'] = $this->ninSlip;
                $documents['bvn_slip'] = $this->bvnSlip;
                $documents['liveness_capture'] = $this->livenessCapture;
            } else {
                $documents['proof_of_address'] = $this->proofOfAddress;
                $documents['address_indemnity_form'] = $this->addressIndemnityForm;
            }

            $data = [
                'nin' => $this->nin,
                'bvn' => $this->bvn,
                'next_of_kin_name' => $this->nextOfKinName,
                'next_of_kin_relationship' => $this->nextOfKinRelationship,
                'next_of_kin_phone' => $this->nextOfKinPhone,
            ];

            $this->getService()->submitKycViaAgent($agent, $this->customer, $this->targetTier, $data, $documents);

            $this->dispatch('notify-success', message: "Tier {$this->targetTier} upgrade submitted for {$this->customer->full_name}.");
        } catch (\Exception $e) {
            $this->addError('customerPin', $e->getMessage());
            return;
        }

        $this->step = 5;
    }

    public function resetAndStart(): void
    {
        $this->step = 1;
        $this->searchPhone = '';
        $this->customer = null;
        $this->resetFormData();
        $this->pinVerified = false;
        $this->customerPin = '';
        $this->targetTier = 2;
    }

    public function render()
    {
        return view('livewire.agent.upgrade-customer')
            ->layout('components.layouts.agent');
    }
}
