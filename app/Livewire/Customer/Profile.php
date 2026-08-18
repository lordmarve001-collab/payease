<?php

namespace App\Livewire\Customer;

use App\Helpers\PinSecurity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Profile extends Component
{
    public bool $showChangePinForm = false;
    public string $currentPin = '';
    public string $newPin = '';
    public string $newPinConfirmation = '';

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function toggleChangePinForm(): void
    {
        $this->showChangePinForm = !$this->showChangePinForm;
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

        if (PinSecurity::isWeak($validated['newPin'])) {
            $this->addError('newPin', PinSecurity::weakPinMessage());
            return;
        }

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
        $this->dispatch('notify-success', message: 'PIN changed successfully.');
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();

        return view('livewire.customer.profile', [
            'user' => $user
        ])->layout('components.layouts.customer');
    }
}
