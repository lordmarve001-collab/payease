<?php

namespace App\Livewire\Agent;

use App\Contracts\IdentityVerificationClientInterface;
use App\Helpers\PhoneNumberHelper;
use App\Models\User;
use App\Services\KycCompletionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class VerifyNin extends Component
{
    public int $step = 1;
    public string $searchPhone = '';
    public ?User $customer = null;

    public string $nin = '';
    public string $fullName = '';
    public string $verificationStatus = '';
    public string $verificationMessage = '';
    public array $verificationResult = [];

    public string $agentPin = '';
    public bool $pinVerified = false;
    public bool $tier2Completed = false;

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

        if (! $user) {
            $this->addError('searchPhone', 'No customer found with this phone number.');
            return;
        }

        $this->customer = $user;
        $this->fullName = $user->full_name;
        $this->step = 2;
    }

    public function verifyNin(): void
    {
        $this->validate([
            'nin' => ['required', 'string', 'size:11'],
            'fullName' => ['required', 'string', 'max:255'],
        ]);

        if (! $this->customer) {
            $this->dispatch('notify-error', message: 'Customer not found. Please search again.');
            return;
        }

        if (filled($this->customer->nin_verified_at)) {
            $this->verificationStatus = 'already_verified';
            $this->verificationMessage = 'This customer already has a verified NIN.';
            $this->step = 3;
            return;
        }

        $this->verificationStatus = 'verifying';

        $client = app(IdentityVerificationClientInterface::class);
        $result = $client->verifyNin($this->nin, $this->fullName, true);

        if (! empty($result['error']) || ! ($result['verified'] ?? false)) {
            $this->verificationStatus = 'failed';
            $this->verificationMessage = $result['error'] ?? 'NIN verification failed. Please check the NIN and name match.';
            return;
        }

        $this->verificationStatus = 'verified';
        $this->verificationMessage = 'NIN verified successfully.';
        $this->verificationResult = $result;
        $this->step = 3;
    }

    public function verifyAgentPin(): void
    {
        $this->validate([
            'agentPin' => ['required', 'string', 'digits:6'],
        ]);

        $key = 'agent-verify-nin-pin-' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('agentPin', 'Too many attempts. Please start over.');
            return;
        }

        $agent = Auth::user()?->agent;
        if (! $agent) {
            $this->dispatch('notify-error', message: 'Agent profile not found.');
            return;
        }

        if (! Auth::user()?->verifyTransferPin($this->agentPin)) {
            RateLimiter::hit($key, 60);
            $this->addError('agentPin', 'Incorrect PIN.');
            return;
        }

        RateLimiter::clear($key);
        $this->pinVerified = true;

        $this->submitVerification();
    }

    protected function submitVerification(): void
    {
        if (! $this->customer) {
            $this->dispatch('notify-error', message: 'Customer session lost. Please start over.');
            return;
        }

        $kycCompletion = app(KycCompletionService::class);

        if ($this->verificationStatus === 'already_verified') {
            $this->tier2Completed = $kycCompletion->tryCompleteTier2($this->customer);
        } elseif ($this->verificationStatus === 'verified') {
            $kycCompletion->recordNinVerification($this->customer, $this->verificationResult);
            $this->customer->update(['nin' => $this->nin]);
            $this->tier2Completed = $kycCompletion->tryCompleteTier2($this->customer);
        } else {
            $this->dispatch('notify-error', message: 'NIN must be verified before continuing.');
            return;
        }

        if ($this->tier2Completed) {
            $kycCompletion->dispatchTier2SuccessNotification($this->customer);
        }

        $this->step = 4;
    }

    public function resetAndStart(): void
    {
        $this->step = 1;
        $this->searchPhone = '';
        $this->customer = null;
        $this->nin = '';
        $this->fullName = '';
        $this->verificationStatus = '';
        $this->verificationMessage = '';
        $this->agentPin = '';
        $this->pinVerified = false;
        $this->tier2Completed = false;
    }

    public function render()
    {
        return view('livewire.agent.verify-nin')
            ->layout('components.layouts.agent');
    }
}
