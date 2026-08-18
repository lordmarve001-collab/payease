<?php

namespace App\Livewire\Agent;

use App\Helpers\PhoneNumberHelper;
use App\Helpers\PinSecurity;
use App\Jobs\SendSmsNotification;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\OtpService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use RuntimeException;

class CreateCustomer extends Component
{
    public int $step = 1;
    public string $phone = '';
    public string $email = '';
    public string $fullName = '';
    public string $otp = '';
    public string $pin = '';
    public string $pinConfirmation = '';
    public bool $otpSent = false;
    public bool $otpVerified = false;
    public int $resendAvailableIn = 0;
    public string $tempPin = '';
    public string $tempPassword = '';

    public function mount(): void
    {
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->step = 1;
        $this->phone = '';
        $this->email = '';
        $this->fullName = '';
        $this->otp = '';
        $this->pin = '';
        $this->pinConfirmation = '';
        $this->otpSent = false;
        $this->otpVerified = false;
        $this->resendAvailableIn = 0;
        $this->tempPin = '';
        $this->tempPassword = '';
    }

    public function updatedPhone(): void
    {
        $this->validateOnly('phone', [
            'phone' => ['required', 'string'],
        ]);
    }

    public function submitPhone(): void
    {
        $this->validate([
            'phone' => ['required', 'string'],
        ]);

        try {
            $normalized = PhoneNumberHelper::normalize($this->phone);
        } catch (\Exception $e) {
            $this->addError('phone', 'Invalid phone number.');
            return;
        }

        $existing = User::where('phone_number', $normalized)->first();
        if ($existing) {
            $this->addError('phone', 'A user with this phone number already exists.');
            return;
        }

        $user = User::create([
            'phone_number' => $normalized,
            'full_name' => $normalized,
            'status' => 'active',
            'kyc_level' => 0,
            'registered_by_agent_id' => Auth::user()->agent?->id ?? Auth::id(),
        ]);

        session(['agent_reg_user_id' => $user->id, 'agent_reg_phone' => $normalized]);

        try {
            app(OtpService::class)->sendOtp($user, enforceCooldown: false);
            $this->resendAvailableIn = app(OtpService::class)->getCooldownSeconds($user);
            $this->dispatch('notify-success', message: "OTP sent to customer's phone via SMS");
            $this->step = 2;
        } catch (RuntimeException $e) {
            $user->delete();
            session()->forget(['agent_reg_user_id', 'agent_reg_phone']);
            $this->addError('phone', $e->getMessage());
        }
    }

    public function submitName(): void
    {
        $this->validate([
            'fullName' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $this->step = 3;
    }

    public function tick(): void
    {
        if ($this->resendAvailableIn > 0) {
            $this->resendAvailableIn--;
        }
    }

    public function resendOtp(): void
    {
        $userId = session('agent_reg_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $this->addError('otp', 'Session expired. Please start over.');
            return;
        }

        try {
            app(OtpService::class)->sendOtp($user);
            $this->resendAvailableIn = app(OtpService::class)->getCooldownSeconds($user);
            $this->dispatch('notify-success', message: 'OTP resent successfully.');
        } catch (RuntimeException $e) {
            $this->addError('otp', $e->getMessage());
        }
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $key = 'agent-reg-otp-' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('otp', 'Too many attempts. Please start over.');
            return;
        }

        $userId = session('agent_reg_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $this->addError('otp', 'Session expired. Please start over.');
            return;
        }

        $otpService = app(OtpService::class);

        if ($otpService->verifyOtp($user, $this->otp)) {
            $otpService->clearOtp($user);
            $this->otpVerified = true;
            $this->dispatch('notify-success', message: 'OTP verified successfully.');
            $this->step = 4;
        } else {
            RateLimiter::hit($key, 60);
            $this->addError('otp', 'Invalid OTP. Please try again.');
        }
    }

    public function submitPin(): void
    {
        $this->validate([
            'pin' => ['required', 'string', 'digits:6'],
            'pinConfirmation' => ['required', 'string', 'same:pin'],
        ]);

        if (PinSecurity::isWeak($this->pin)) {
            $this->addError('pin', PinSecurity::weakPinMessage());
            return;
        }

        $this->tempPin = $this->pin;
        $this->tempPassword = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->proceedWithRegistration();
    }

    protected function proceedWithRegistration(): void
    {
        $userId = session('agent_reg_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user || !$this->tempPin) {
            $this->addError('pin', 'Session expired. Please start over.');
            return;
        }

        try {
            $updateData = [
                'full_name' => $this->fullName,
                'email' => $this->email ?: null,
                'pin_hash' => Hash::make($this->tempPin),
                'login_pin_hash' => Hash::make($this->tempPin),
                'transfer_pin_hash' => Hash::make($this->tempPin),
                'login_password' => Hash::make($this->tempPassword),
                'must_change_password' => true,
                'kyc_level' => 1,
                'kyc_verified_at' => now(),
            ];

            $user->update($updateData);
            $user->assignRole('customer');

            app(WalletService::class)->createTierWallet($user, 1);

            session()->forget(['agent_reg_user_id', 'agent_reg_phone', 'agent_reg_otp']);

            if ($this->email) {
                rescue(function () use ($user): void {
                    Mail::to($user->email)->send(new WelcomeMail($user, $this->tempPassword));
                }, report: false);
            }

            rescue(function () use ($user): void {
                SendSmsNotification::dispatch(
                    $user->phone_number,
                    "Welcome to PayEase, {$this->fullName}! Default password: {$this->tempPassword}. Please change it after login. -PayEase"
                );
            }, report: false);

            $this->dispatch('notify-success', message: "Customer {$this->fullName} registered successfully.");
        } catch (\Exception $e) {
            $this->addError('pin', 'Registration failed. Please try again.');
            return;
        }

        $this->step = 5;
    }

    public function resetAndStart(): void
    {
        $this->resetForm();
    }

    public function render()
    {
        return view('livewire.agent.create-customer')
            ->layout('components.layouts.agent');
    }
}
