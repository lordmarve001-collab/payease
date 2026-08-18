<?php

namespace App\Livewire\AjoOwner;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BusinessInfo extends Component
{
    public string $businessName = '';
    public string $businessDescription = '';
    public string $businessAddress = '';
    public string $lga = '';
    public string $state = '';
    public bool $hasExperience = false;
    public string $plannedGroups = '';

    protected function rules(): array
    {
        return [
            'businessName' => ['required', 'string', 'max:255'],
            'businessDescription' => ['nullable', 'string', 'max:1000'],
            'businessAddress' => ['nullable', 'string', 'max:500'],
            'lga' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'hasExperience' => ['boolean'],
            'plannedGroups' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function mount(): void
    {
        $owner = Auth::user()->ajoOwner;
        if ($owner) {
            $this->businessName = $owner->business_name ?? '';
            $this->businessDescription = $owner->business_description ?? '';
            $this->businessAddress = $owner->business_address ?? '';
            $this->lga = $owner->lga ?? '';
            $this->state = $owner->state ?? '';
            $this->hasExperience = (bool) $owner->has_experience;
            $this->plannedGroups = (string) ($owner->planned_groups ?? '');
        }
    }

    public function save(): void
    {
        $this->validate();

        $owner = Auth::user()->ajoOwner;
        if ($owner) {
            $owner->update([
                'business_name' => $this->businessName,
                'business_description' => $this->businessDescription,
                'business_address' => $this->businessAddress,
                'lga' => $this->lga,
                'state' => $this->state,
                'has_experience' => $this->hasExperience,
                'planned_groups' => $this->plannedGroups !== '' ? (int) $this->plannedGroups : null,
            ]);
        }

        $this->dispatch('notify-success', message: 'Business information saved successfully.');
    }

    public function render()
    {
        return view('livewire.ajo-owner.business-info')
            ->layout('components.layouts.ajo-owner');
    }
}
