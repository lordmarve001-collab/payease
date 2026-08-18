<?php

namespace App\Livewire\Public;

use App\Models\AjoOwner;
use App\Services\AjoOwnerApplicationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BecomeAjoOwner extends Component
{
    public bool $showMarketing = true;
    public AjoOwner $existingApplication;

    // Form fields
    public string $businessName = '';
    public string $businessDescription = '';
    public string $businessAddress = '';
    public string $lga = '';
    public string $state = '';
    public bool $hasExperience = false;
    public int $plannedGroups = 1;
    public int $membersPerGroup = 0;
    public string $agentAssignmentPreference = '';
    public string $referenceContactName = '';
    public string $referenceContactPhone = '';
    public bool $agreeTerms = false;

    public int $step = 1;

    protected $listeners = ['refreshStep' => '$refresh'];

    public function mount(): void
    {
        if (!Auth::check()) {
            $this->redirect(route('login'));
            return;
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if already an Ajo Owner
        $existing = AjoOwner::where('user_id', $user->id)->latest()->first();
        if ($existing) {
            $this->existingApplication = $existing;
            if ($existing->status === 'active') {
                $this->redirect(route('ajo-owner.dashboard'));
                return;
            }
            $this->showMarketing = false;
            return;
        }

        // Check tier gate
        if ((int) $user->kyc_level < 2) {
            $this->showMarketing = false;
            return;
        }

        $this->showMarketing = true;
    }

    public function startApplication(): void
    {
        $this->showMarketing = false;
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validateStep1();
        } elseif ($this->step === 2) {
            $this->validateStep2();
        } elseif ($this->step === 3) {
            $this->validateStep3();
        }
        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    protected function validateStep1(): void
    {
        $this->validate([
            'businessName' => ['required', 'string', 'max:255'],
            'businessDescription' => ['required', 'string', 'max:2000'],
            'businessAddress' => ['required', 'string', 'max:500'],
            'lga' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:50'],
        ]);
    }

    protected function validateStep2(): void
    {
        $this->validate([
            'plannedGroups' => ['required', 'integer', 'min:1', 'max:100'],
            'membersPerGroup' => ['required', 'integer', 'min:1', 'max:10000'],
            'agentAssignmentPreference' => ['required', 'string', 'in:have_agents,needs_help,not_sure'],
        ]);
    }

    protected function validateStep3(): void
    {
        $this->validate([
            'referenceContactPhone' => ['nullable', 'string', 'max:20'],
        ]);
    }

    public function submit(): void
    {
        $this->validate([
            'agreeTerms' => ['accepted'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $service = app(AjoOwnerApplicationService::class);
            $service->submitApplication($user, [
                'business_name' => $this->businessName,
                'business_description' => $this->businessDescription,
                'business_address' => $this->businessAddress,
                'lga' => $this->lga,
                'state' => $this->state,
                'has_experience' => $this->hasExperience,
                'planned_groups' => $this->plannedGroups,
                'members_per_group' => $this->membersPerGroup,
                'agent_assignment_preference' => $this->agentAssignmentPreference,
                'reference_contact_name' => $this->referenceContactName,
                'reference_contact_phone' => $this->referenceContactPhone,
            ]);

            $this->dispatch('notify-success', message: 'Application submitted successfully!');
            $this->step = 5; // success step
        } catch (\RuntimeException $e) {
            $this->addError('submit', $e->getMessage());
        }
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return view('livewire.public.become-ajo-owner', [
            'user' => $user,
            'existing' => $this->existingApplication ?? null,
        ])->layout('components.layouts.app');
    }
}
