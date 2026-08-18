<?php

namespace App\Livewire\Public;

use App\Helpers\PhoneNumberHelper;
use App\Jobs\SendSmsNotification;
use App\Models\AjoOwner;
use App\Models\User;
use App\Services\OtpService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use RuntimeException;

class AjoOwnerSignup extends Component
{
    public int $step = 1;
    public string $phoneInput = '';
    public string $otp1 = '';
    public string $otp2 = '';
    public string $otp3 = '';
    public string $otp4 = '';
    public string $otp5 = '';
    public string $otp6 = '';
    public int $resendAvailableIn = 0;
    public string $pin = '';
    public string $pinConfirmation = '';
    public string $fullName = '';
    public string $businessName = '';
    public string $businessDescription = '';
    public string $businessAddress = '';
    public string $lga = '';
    public string $state = '';
    public bool $hasExperience = false;
    public int $plannedGroups = 1;
    public int $membersPerGroup = 10;
    public string $agentAssignmentPreference = '';
    public string $referenceContactName = '';
    public string $referenceContactPhone = '';
    public bool $agreeTerms = false;

    public string $normalizedPhone = '';
    public ?string $pendingUserId = null;

    protected function rules(): array
    {
        return match ($this->step) {
            1 => ['phoneInput' => ['required', 'string']],
            2 => [
                'otp1' => ['required', 'digits:1'],
                'otp2' => ['required', 'digits:1'],
                'otp3' => ['required', 'digits:1'],
                'otp4' => ['required', 'digits:1'],
                'otp5' => ['required', 'digits:1'],
                'otp6' => ['required', 'digits:1'],
            ],
            3 => [
                'pin' => ['required', 'string', 'digits:6'],
                'pinConfirmation' => ['required', 'string', 'digits:6', 'same:pin'],
                'fullName' => ['required', 'string', 'max:255'],
            ],
            4 => [
                'businessName' => ['required', 'string', 'max:255'],
                'businessDescription' => ['required', 'string', 'max:2000'],
                'businessAddress' => ['required', 'string', 'max:500'],
                'lga' => ['required', 'string', 'max:100'],
                'state' => ['required', 'string', 'max:50'],
            ],
            5 => [
                'plannedGroups' => ['required', 'integer', 'min:1', 'max:100'],
                'membersPerGroup' => ['required', 'integer', 'min:1', 'max:10000'],
                'agentAssignmentPreference' => ['required', 'string', 'in:have_agents,needs_help,not_sure'],
            ],
            default => [],
        };
    }

    public function updated($field): void
    {
        if (in_array($field, ['otp1', 'otp2', 'otp3', 'otp4', 'otp5', 'otp6'])) {
            $this->$field = substr($this->$field, -1);
            if (strlen($this->$field) && $field !== 'otp6') {
                $next = 'otp' . ((int) substr($field, -1) + 1);
                $this->dispatch('focus-next', field: $next);
            }
        }
    }

    public function sendOtp(): void
    {
        $this->validateOnly('phoneInput');
        $this->phoneInput = preg_replace('/\D/', '', $this->phoneInput);

        if (strlen($this->phoneInput) < 10 || strlen($this->phoneInput) > 14) {
            $this->addError('phoneInput', 'Enter a valid Nigerian phone number (10-14 digits).');
            return;
        }

        try {
            $this->normalizedPhone = PhoneNumberHelper::normalize($this->phoneInput);
        } catch (\InvalidArgumentException $e) {
            $this->addError('phoneInput', 'Enter a valid Nigerian phone number.');
            return;
        }

        $existing = User::where('phone_number', $this->normalizedPhone)->first();
        if ($existing) {
            $this->addError('phoneInput', 'This phone number is already registered. Please log in instead.');
            return;
        }

        $user = User::create([
            'phone_number' => $this->normalizedPhone,
            'full_name' => '',
            'status' => 'active',
            'kyc_level' => 0,
        ]);

        $this->pendingUserId = $user->id;
        session()->put('ajo_signup_user_id', $user->id);

        try {
            app(OtpService::class)->sendOtp($user, enforceCooldown: false);
            $this->step = 2;
        } catch (RuntimeException $exception) {
            $user->delete();
            $this->addError('phoneInput', $exception->getMessage());
        }
    }

    public function verifyOtp(): void
    {
        $this->validateOnlyStep();
        $user = User::find($this->pendingUserId);
        if (!$user) {
            $this->addError('otp1', 'Session expired. Please start again.');
            return;
        }
        if (!app(OtpService::class)->verifyOtp($user, $this->getOtpString())) {
            $this->addError('otp1', 'Invalid OTP.');
            return;
        }
        app(OtpService::class)->clearOtp($user);
        $this->step = 3;
    }

    public function resendOtp(): void
    {
        $user = User::find($this->pendingUserId);
        if (!$user) {
            $this->redirect(route('ajo-owner.signup'));
            return;
        }
        try {
            app(OtpService::class)->sendOtp($user);
            $this->resendAvailableIn = app(OtpService::class)->getCooldownSeconds($user);
        } catch (RuntimeException $exception) {
            $this->addError('otp1', $exception->getMessage());
        }
    }

    public function tick(): void
    {
        if ($this->resendAvailableIn > 0) {
            $this->resendAvailableIn--;
        }
    }

    public function nextStep(): void
    {
        $this->validateOnlyStep();
        $this->step++;
    }

    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function submit(): void
    {
        $this->validate([
            'agreeTerms' => ['accepted'],
        ]);

        $user = User::find($this->pendingUserId);
        if (!$user) {
            $this->addError('agreeTerms', 'Session expired. Please start again.');
            return;
        }

        try {
            $user->update([
                'full_name' => $this->fullName,
                'pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
                'login_pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
                'transfer_pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
                'kyc_level' => 0,
            ]);

            $user->assignRole('customer');

            app(WalletService::class)->createTierWallet($user, 1);

            AjoOwner::create([
                'user_id' => $user->id,
                'business_name' => $this->businessName,
                'business_description' => $this->businessDescription,
                'business_address' => $this->businessAddress,
                'lga' => $this->lga,
                'state' => $this->state,
                'has_experience' => $this->hasExperience,
                'planned_groups' => $this->plannedGroups,
                'members_per_group' => $this->membersPerGroup,
                'agent_assignment_preference' => $this->agentAssignmentPreference,
                'reference_contact_name' => $this->referenceContactName ?: null,
                'reference_contact_phone' => $this->referenceContactPhone ?: null,
                'status' => 'pending',
            ]);

            rescue(function () use ($user): void {
                SendSmsNotification::dispatch(
                    $user->phone_number,
                    'Welcome to PayEase! Your Ajo Owner application has been received and is under review. -PayEase'
                );
            }, report: false);

            auth()->login($user);
            session()->forget('ajo_signup_user_id');

            $this->step = 7;
        } catch (\Exception $e) {
            $this->addError('agreeTerms', 'Something went wrong. Please try again.');
        }
    }

    public function render()
    {
        return view('livewire.public.ajo-owner-signup')->layout('components.layouts.app');
    }

    protected function validateOnlyStep(): void
    {
        $this->validate($this->rules());
    }

    protected function getOtpString(): string
    {
        return $this->otp1 . $this->otp2 . $this->otp3 . $this->otp4 . $this->otp5 . $this->otp6;
    }
}
