<?php

namespace App\Livewire\AjoAgent;

use App\Models\Agent;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Profile extends Component
{
    public bool $showChangePinForm = false;
    public string $currentPin = '';
    public string $newPin = '';
    public string $newPinConfirmation = '';

    public function logout(): \Illuminate\Http\RedirectResponse
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function toggleChangePinForm(): void
    {
        $this->showChangePinForm = !$this->showChangePinForm;
        $this->resetErrorBag();
    }

    public function changePin(): void
    {
        $validated = $this->validate([
            'currentPin' => ['required', 'digits:6'],
            'newPin' => ['required', 'digits:6', 'different:currentPin'],
            'newPinConfirmation' => ['required', 'same:newPin'],
        ], [
            'currentPin.required' => 'Enter your current PIN.',
            'newPin.digits' => 'Your new PIN must be 6 digits.',
            'newPin.different' => 'Choose a new PIN different from your current one.',
            'newPinConfirmation.same' => 'New PIN confirmation does not match.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($validated['currentPin'], (string) $user->pin_hash)) {
            $this->addError('currentPin', 'Current PIN is incorrect.');
            return;
        }

        $user->update([
            'pin_hash' => Hash::make($validated['newPin'], ['rounds' => 12]),
            'login_pin_hash' => Hash::make($validated['newPin'], ['rounds' => 12]),
            'transfer_pin_hash' => Hash::make($validated['newPin'], ['rounds' => 12]),
        ]);

        $this->reset(['currentPin', 'newPin', 'newPinConfirmation']);
        $this->showChangePinForm = false;
        $this->dispatch('notify-success', message: 'Login PIN changed successfully.');
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $walletService = app(WalletService::class);
        $wallet = $agent ? $walletService->getAgentWallet($user) : null;

        return view('livewire.ajo-agent.profile', [
            'user' => $user,
            'agent' => $agent,
            'wallet' => $wallet,
        ])->layout('components.layouts.ajo-agent');
    }
}
