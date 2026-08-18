<?php

namespace App\Livewire\AjoOwner;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PayoutSettings extends Component
{
    public string $bankName = '';
    public string $accountName = '';
    public string $accountNumber = '';

    protected function rules(): array
    {
        return [
            'bankName' => ['required', 'string', 'max:255'],
            'accountName' => ['required', 'string', 'max:255'],
            'accountNumber' => ['required', 'string', 'regex:/^\d{10}$/'],
        ];
    }

    public function mount(): void
    {
        $owner = Auth::user()->ajoOwner;
        if ($owner) {
            $this->bankName = $owner->bank_name ?? '';
            $this->accountName = $owner->account_name ?? '';
            $this->accountNumber = $owner->account_number ?? '';
        }
    }

    public function save(): void
    {
        $this->validate();

        $owner = Auth::user()->ajoOwner;
        if ($owner) {
            $owner->update([
                'bank_name' => $this->bankName,
                'account_name' => $this->accountName,
                'account_number' => $this->accountNumber,
            ]);
        }

        $this->dispatch('notify-success', message: 'Payout settings saved successfully.');
    }

    public function render()
    {
        return view('livewire.ajo-owner.payout-settings')
            ->layout('components.layouts.ajo-owner');
    }
}
