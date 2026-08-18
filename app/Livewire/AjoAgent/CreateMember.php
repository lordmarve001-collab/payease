<?php

namespace App\Livewire\AjoAgent;

use App\Helpers\PhoneNumberHelper;
use App\Jobs\SendSmsNotification;
use App\Models\Agent;
use App\Models\AjoGroup;
use App\Models\User;
use App\Services\AjoService;
use App\Services\OtpService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class CreateMember extends Component
{
    public int $step = 1;
    public string $phone = '';
    public string $fullName = '';
    public string $pin = '';
    public string $pinConfirmation = '';
    public string $otpCode = '';
    public string $otpResendMessage = '';
    public int $otpCooldown = 0;

    public ?User $foundUser = null;
    public bool $isExistingUser = false;

    public ?string $selectedGroupId = null;

    public string $resultState = '';
    public string $resultMessage = '';

    public function resetForm(): void
    {
        $this->step = 1;
        $this->phone = '';
        $this->fullName = '';
        $this->pin = '';
        $this->pinConfirmation = '';
        $this->otpCode = '';
        $this->otpResendMessage = '';
        $this->otpCooldown = 0;
        $this->foundUser = null;
        $this->isExistingUser = false;
        $this->selectedGroupId = null;
        $this->resultState = '';
        $this->resultMessage = '';
        $this->resetErrorBag();
    }

    public function lookupPhone(): void
    {
        $this->validate([
            'phone' => ['required', 'string'],
        ]);

        try {
            $normalized = PhoneNumberHelper::normalize($this->phone);
        } catch (\Exception $e) {
            $this->addError('phone', 'Enter a valid Nigerian phone number.');
            return;
        }

        $user = User::where('phone_number', $normalized)->first();

        if ($user) {
            $this->foundUser = $user;
            $this->isExistingUser = true;
            $this->sendOtpToUser($user);
            $this->step = 3;
        } else {
            $this->foundUser = null;
            $this->isExistingUser = false;
            $this->phone = $normalized;
            $this->step = 2;
        }
    }

    public function createUser(): void
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'pin' => ['required', 'string', 'size:6', 'digits:6'],
            'pinConfirmation' => ['required', 'string', 'same:pin'],
        ], [
            'fullName.required' => 'Enter the member\'s full name.',
            'pin.required' => 'Set a 6-digit login PIN.',
            'pin.size' => 'PIN must be exactly 6 digits.',
            'pin.digits' => 'PIN must contain only numbers.',
            'pinConfirmation.same' => 'PINs do not match.',
        ]);

        try {
            $agent = Auth::user()->agent;

            $user = User::create([
                'phone_number' => $this->phone,
                'full_name' => $this->fullName,
                'status' => 'active',
                'kyc_level' => 0,
                'pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
                'login_pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
                'transfer_pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
                'registered_by_agent_id' => $agent?->id,
            ]);

            $user->assignRole('customer');

            app(WalletService::class)->createTierWallet($user, 1);

            $this->foundUser = $user;
            $this->isExistingUser = false;

            rescue(fn () => SendSmsNotification::dispatch(
                $user->phone_number,
                "Welcome to PayEase, {$this->fullName}! Your phone number (Login ID) is {$this->phone} and your 6-digit PIN is {$this->pin}. Keep your PIN safe. -PayEase"
            ), report: false);

            $this->sendOtpToUser($user);
            $this->step = 3;
        } catch (\Exception $e) {
            $this->addError('fullName', 'Failed to create account. ' . $e->getMessage());
        }
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otpCode' => ['required', 'string', 'size:6'],
        ], [
            'otpCode.required' => 'Enter the OTP code.',
            'otpCode.size' => 'OTP must be exactly 6 digits.',
        ]);

        if (!$this->foundUser) {
            $this->addError('otpCode', 'Session expired. Please start over.');
            return;
        }

        /** @var OtpService $otpService */
        $otpService = app(OtpService::class);

        if (!$otpService->verifyOtp($this->foundUser, $this->otpCode)) {
            $this->addError('otpCode', 'Invalid OTP. Please check the code and try again.');
            return;
        }

        $otpService->clearOtp($this->foundUser);
        $this->otpCode = '';
        $this->otpResendMessage = '';
        $this->resetErrorBag();
        $this->step = 4;
    }

    public function resendOtp(): void
    {
        if (!$this->foundUser) {
            $this->otpResendMessage = 'Session expired. Please start over.';
            return;
        }

        try {
            $otpService = app(OtpService::class);
            $otpService->sendOtp($this->foundUser, false);
            $this->otpResendMessage = 'A new OTP has been sent to ' . $this->foundUser->phone_number;
            $this->otpCooldown = 60;
        } catch (\Exception $e) {
            $this->otpResendMessage = $e->getMessage();
        }
    }

    public function selectGroup(string $groupId): void
    {
        $this->selectedGroupId = $groupId;
        $this->step = 5;
    }

    public function goBack(): void
    {
        match ($this->step) {
            2 => $this->step = 1,
            3 => $this->step = $this->isExistingUser ? 1 : 2,
            4 => $this->step = 3,
            5 => $this->step = 4,
            default => null,
        };
        $this->resetErrorBag();
    }

    public function confirmAdd(): void
    {
        if (!$this->foundUser || !$this->selectedGroupId) {
            $this->addError('group', 'Missing required information. Please start over.');
            return;
        }

        try {
            $agent = Auth::user()->agent;
            $ajoService = app(AjoService::class);

            $group = AjoGroup::with('agents')->findOrFail($this->selectedGroupId);

            $isAssigned = $group->agents->contains('id', $agent->id)
                || $group->managing_agent_id === $agent->id;

            if (!$isAssigned) {
                $this->addError('group', 'You are not assigned to this group.');
                return;
            }

            $ajoService->addMember($group, $this->foundUser);

            $this->resultState = 'success';
            $this->resultMessage = $this->isExistingUser
                ? "Member has been added to the group."
                : "Account created and member added to the group successfully!";
            $this->step = 6;
        } catch (\Exception $e) {
            $this->resultState = 'error';
            $this->resultMessage = $e->getMessage();
            $this->step = 6;
        }
    }

    protected function sendOtpToUser(User $user): void
    {
        try {
            $otpService = app(OtpService::class);
            $otpService->sendOtp($user);
            $this->otpResendMessage = 'An OTP has been sent to ' . $user->phone_number;
            $this->otpCooldown = 60;
        } catch (\Exception $e) {
            $this->otpResendMessage = 'Failed to send OTP: ' . $e->getMessage();
        }
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var Agent|null $agent */
        $agent = $user->agent;

        $assignedGroups = collect();
        $selectedGroup = null;

        if ($agent) {
            $assignedGroups = AjoGroup::query()
                ->whereHas('agents', fn ($q) => $q->where('agent_id', $agent->id))
                ->orWhere('managing_agent_id', $agent->id)
                ->where('status', 'active')
                ->with('members')
                ->get();

            if ($this->selectedGroupId) {
                $selectedGroup = AjoGroup::with('members')->find($this->selectedGroupId);
            }
        }

        return view('livewire.ajo-agent.create-member', [
            'user' => $user,
            'agent' => $agent,
            'assignedGroups' => $assignedGroups,
            'selectedGroup' => $selectedGroup,
        ])->layout('components.layouts.ajo-agent');
    }
}
