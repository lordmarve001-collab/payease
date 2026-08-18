<?php

namespace App\Livewire\Auth;

use App\Helpers\PinSecurity;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class ChangePassword extends Component
{
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    protected $rules = [
        'currentPassword' => ['required', 'string', 'size:6'],
        'newPassword' => ['required', 'string', 'size:6', 'different:currentPassword'],
        'newPasswordConfirmation' => ['required', 'string', 'same:newPassword'],
    ];

    protected $messages = [
        'currentPassword.size' => 'Current password must be 6 digits.',
        'newPassword.size' => 'New password must be 6 digits.',
        'newPassword.different' => 'Choose a password different from your current one.',
        'newPasswordConfirmation.same' => 'Password confirmation does not match.',
    ];

    public function changePassword()
    {
        $this->validate();

        if (PinSecurity::isWeak($this->newPassword)) {
            $this->addError('newPassword', PinSecurity::weakPinMessage());
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if (!Hash::check($this->currentPassword, (string) $user->login_password)) {
            $this->addError('currentPassword', 'Current password is incorrect.');
            return;
        }

        $user->update([
            'login_password' => Hash::make($this->newPassword, ['rounds' => 12]),
            'must_change_password' => false,
        ]);

        $this->dispatch('notify-success', message: 'Password changed successfully.');

        return $this->redirectToDashboard($user);
    }

    protected function redirectToDashboard(User $user)
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return redirect()->route('admin.overview');
        } elseif ($user->hasRole('agent')) {
            return redirect()->route('agent.dashboard');
        } elseif ($user->hasRole('ajo_owner')) {
            return redirect()->route('ajo-owner.dashboard');
        } else {
            return redirect()->route('customer.dashboard');
        }
    }

    public function render()
    {
        return view('livewire.auth.change-password')->layout('components.layouts.app');
    }
}
