<?php

namespace App\Livewire\Agent;

use App\Helpers\PinSecurity;
use App\Models\Agent;
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

    public bool $showChangePasswordForm = false;
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function toggleChangePinForm(): void
    {
        $this->showChangePasswordForm = false;
        $this->showChangePinForm = !$this->showChangePinForm;
    }

    public function toggleChangePasswordForm(): void
    {
        $this->showChangePinForm = false;
        $this->showChangePasswordForm = !$this->showChangePasswordForm;
    }

    public function changePassword(): void
    {
        $validated = $this->validate([
            'currentPassword' => ['required', 'string', 'min:6'],
            'newPassword' => ['required', 'string', 'min:6', 'different:currentPassword'],
            'newPasswordConfirmation' => ['required', 'same:newPassword'],
        ], [
            'currentPassword.required' => 'Enter your current password.',
            'newPassword.min' => 'Your new password must be at least 6 characters.',
            'newPassword.different' => 'Choose a new password different from your current one.',
            'newPasswordConfirmation.same' => 'New password confirmation does not match.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($validated['currentPassword'], (string) $user->login_password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user->update([
            'login_password' => Hash::make($validated['newPassword'], ['rounds' => 12]),
            'must_change_password' => false,
        ]);

        $this->reset(['currentPassword', 'newPassword', 'newPasswordConfirmation']);
        $this->showChangePasswordForm = false;
        $this->dispatch('notify-success', message: 'Password changed successfully.');
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
        /** @var Agent $agent */
        $agent = $user->agent;

        return view('livewire.agent.profile', [
            'user' => $user,
            'agent' => $agent
        ])->layout('components.layouts.agent');
    }
}
