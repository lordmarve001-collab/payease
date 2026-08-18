<?php

namespace App\Livewire\Customer;

use App\Models\KycDocument;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class KycAddress extends Component
{
    use WithFileUploads;

    public $addressDocument;

    protected function rules(): array
    {
        return [
            'addressDocument' => ['required', 'image', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }

    public function submit(): void
    {
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        $path = $this->addressDocument->store('kyc-documents', 'public');

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'proof_of_address',
            'document_url' => Storage::url($path),
            'verification_status' => 'pending',
        ]);

        $this->dispatch('notify-success', message: 'Address proof submitted for review.');
        $this->redirect(route('customer.dashboard'), navigate: true);
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $walletService = app(WalletService::class);

        return view('livewire.customer.kyc-address', [
            'user' => $user,
            'upgradeMessage' => $walletService->getKycUpgradeMessage((int) $user->kyc_level),
        ])->layout('components.layouts.customer');
    }
}
