<?php

namespace App\Livewire\Customer;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PersonalInfo extends Component
{
    public string $fullName = '';
    public string $email = '';
    public string $dateOfBirth = '';
    public string $gender = '';
    public string $lga = '';
    public string $state = '';
    public string $nextOfKinName = '';
    public string $nextOfKinRelationship = '';
    public string $nextOfKinPhone = '';

    public bool $isEditing = false;
    public bool $saved = false;

    public function mount()
    {
        $user = Auth::user();
        $this->fullName = $user->full_name ?? '';
        $this->email = $user->email ?? '';
        $this->dateOfBirth = $user->date_of_birth?->format('Y-m-d') ?? '';
        $this->gender = $user->gender ?? '';
        $this->lga = $user->lga ?? '';
        $this->state = $user->state ?? '';
        $this->nextOfKinName = $user->next_of_kin_name ?? '';
        $this->nextOfKinRelationship = $user->next_of_kin_relationship ?? '';
        $this->nextOfKinPhone = $user->next_of_kin_phone ?? '';
    }

    public function toggleEdit()
    {
        $this->isEditing = !$this->isEditing;
        $this->saved = false;
    }

    public function save()
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->ignore(Auth::id())],
            'dateOfBirth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'lga' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:50'],
            'nextOfKinName' => ['nullable', 'string', 'max:255'],
            'nextOfKinRelationship' => ['nullable', 'string', 'max:100'],
            'nextOfKinPhone' => ['nullable', 'string', 'max:20'],
        ]);

        Auth::user()->update([
            'full_name' => $this->fullName,
            'email' => $this->email ?: null,
            'date_of_birth' => $this->dateOfBirth ?: null,
            'gender' => $this->gender ?: null,
            'lga' => $this->lga ?: null,
            'state' => $this->state ?: null,
            'next_of_kin_name' => $this->nextOfKinName ?: null,
            'next_of_kin_relationship' => $this->nextOfKinRelationship ?: null,
            'next_of_kin_phone' => $this->nextOfKinPhone ?: null,
        ]);

        $this->isEditing = false;
        $this->saved = true;
        $this->dispatch('notify-success', message: 'Personal info updated successfully!');
    }

    public function render()
    {
        return view('livewire.customer.personal-info', [
            'user' => Auth::user(),
        ])->layout('components.layouts.customer');
    }
}
