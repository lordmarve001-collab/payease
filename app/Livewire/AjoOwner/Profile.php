<?php

namespace App\Livewire\AjoOwner;

use App\Helpers\PinSecurity;
use App\Models\AjoOwner;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class Profile extends Component
{
    public bool $showChangeLoginPin = false;
    public bool $showChangeTransferPin = false;

    // Login PIN (6-digit)
    public string $currentLoginPin = '';
    public string $newLoginPin = '';
    public string $newLoginPinConfirm = '';

    // Transfer PIN (6-digit)
    public string $currentTransferPin = '';
    public string $newTransferPin = '';
    public string $newTransferPinConfirm = '';

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function toggleChangeLoginPin(): void
    {
        $this->showChangeLoginPin = !$this->showChangeLoginPin;
        $this->showChangeTransferPin = false;
        $this->resetLoginPinFields();
        $this->resetTransferPinFields();
        $this->clearErrorBag();
    }

    public function toggleChangeTransferPin(): void
    {
        $this->showChangeTransferPin = !$this->showChangeTransferPin;
        $this->showChangeLoginPin = false;
        $this->resetLoginPinFields();
        $this->resetTransferPinFields();
        $this->clearErrorBag();
    }

    public function changeLoginPin(): void
    {
        $validated = $this->validate([
            'currentLoginPin' => ['required', 'string', 'digits:6'],
            'newLoginPin' => ['required', 'string', 'digits:6', 'different:currentLoginPin'],
            'newLoginPinConfirm' => ['required', 'string', 'same:newLoginPin'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->verifyLoginPin($validated['currentLoginPin'])) {
            $this->addError('currentLoginPin', 'Current PIN is incorrect.');
            return;
        }

        $user->setLoginPin($validated['newLoginPin']);

        $this->resetLoginPinFields();
        $this->showChangeLoginPin = false;
        $this->dispatch('notify-success', message: 'Login PIN changed successfully.');
    }

    public function changeTransferPin(): void
    {
        $validated = $this->validate([
            'currentTransferPin' => ['required', 'string', 'digits:6'],
            'newTransferPin' => ['required', 'string', 'digits:6', 'different:currentTransferPin'],
            'newTransferPinConfirm' => ['required', 'string', 'same:newTransferPin'],
        ]);

        if (PinSecurity::isWeak($validated['newTransferPin'])) {
            $this->addError('newTransferPin', PinSecurity::weakPinMessage());
            return;
        }

        /** @var User $user */
        $user = Auth::user();

        if (!$user->verifyTransferPin($validated['currentTransferPin'])) {
            $this->addError('currentTransferPin', 'Current PIN is incorrect.');
            return;
        }

        $user->setTransferPin($validated['newTransferPin']);

        $this->resetTransferPinFields();
        $this->showChangeTransferPin = false;
        $this->dispatch('notify-success', message: 'Transfer PIN changed successfully.');
    }

    private function resetLoginPinFields(): void
    {
        $this->currentLoginPin = '';
        $this->newLoginPin = '';
        $this->newLoginPinConfirm = '';
    }

    private function resetTransferPinFields(): void
    {
        $this->currentTransferPin = '';
        $this->newTransferPin = '';
        $this->newTransferPinConfirm = '';
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var AjoOwner $ajoOwner */
        $ajoOwner = $user->ajoOwner;

        return view('livewire.ajo-owner.profile', [
            'user' => $user,
            'ajoOwner' => $ajoOwner
        ])->layout('components.layouts.ajo-owner');
    }
}
