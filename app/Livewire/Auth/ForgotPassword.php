<?php

namespace App\Livewire\Auth;

use App\Helpers\PhoneNumberHelper;
use App\Helpers\PinSecurity;
use App\Jobs\SendSmsNotification;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Component;

class ForgotPassword extends Component
{
    public int $step = 1;
    public string $phoneInput = '';
    public string $otp1 = '';
    public string $otp2 = '';
    public string $otp3 = '';
    public string $otp4 = '';
    public string $otp5 = '';
    public string $otp6 = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';
    public int $resendAvailableIn = 0;
    public ?string $verifiedUserId = null;

    public function sendResetOtp()
    {
        $this->validateOnlyStep();

        try {
            $normalized = PhoneNumberHelper::normalize($this->phoneInput);
        } catch (\InvalidArgumentException) {
            $this->addError('phoneInput', 'Invalid phone number.');
            return;
        }

        $user = User::where('phone_number', $normalized)->first();
        if (!$user) {
            $this->addError('phoneInput', 'No account found with this phone number.');
            return;
        }

        $this->verifiedUserId = $user->id;
        session(['pwd_reset_user_id' => $user->id]);

        try {
            $otpService = app(OtpService::class);
            $otpService->sendOtp($user, enforceCooldown: false);
            $this->resendAvailableIn = $otpService->getCooldownSeconds($user);
            $this->step = 2;
        } catch (\RuntimeException $e) {
            $this->addError('phoneInput', $e->getMessage());
        }
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

    public function verifyOtp()
    {
        $this->validateOnlyStep();

        $userId = session('pwd_reset_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $this->addError('otp1', 'Session expired. Please start over.');
            return;
        }

        $otp = $this->otp1 . $this->otp2 . $this->otp3 . $this->otp4 . $this->otp5 . $this->otp6;

        $maxAttempts = 5;
        $lockoutMinutes = 30;
        $attemptKey = 'forgot_otp_attempts_' . $user->id;
        $lockKey = 'forgot_otp_locked_' . $user->id;

        if (Cache::get($lockKey)) {
            $this->addError('otp1', 'Too many attempts. Please try again in ' . $lockoutMinutes . ' minutes.');
            return;
        }

        $otpService = app(OtpService::class);
        if (!$otpService->verifyOtp($user, $otp)) {
            $attempts = (int) Cache::increment($attemptKey, 1);
            Cache::put($attemptKey, $attempts, now()->addMinutes($lockoutMinutes));

            if ($attempts >= $maxAttempts) {
                Cache::put($lockKey, true, now()->addMinutes($lockoutMinutes));
                $this->addError('otp1', 'Too many attempts. Please try again in ' . $lockoutMinutes . ' minutes.');
                return;
            }

            $remaining = $maxAttempts - $attempts;
            $this->addError('otp1', 'Invalid OTP. ' . $remaining . ' attempt(s) remaining.');
            return;
        }

        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        $otpService->clearOtp($user);
        $this->step = 3;
    }

    public function resendOtp(): void
    {
        $userId = session('pwd_reset_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $this->redirect(route('password.forgot'));
            return;
        }

        try {
            $otpService = app(OtpService::class);
            $otpService->sendOtp($user);
            $this->resendAvailableIn = $otpService->getCooldownSeconds($user);
        } catch (\RuntimeException $e) {
            $this->addError('otp1', $e->getMessage());
        }
    }

    public function tick(): void
    {
        if ($this->resendAvailableIn > 0) {
            $this->resendAvailableIn--;
        }
    }

    public function resetPassword()
    {
        $this->validateOnlyStep();

        $userId = session('pwd_reset_user_id');
        $user = $userId ? User::find($userId) : null;

        if (!$user) {
            $this->addError('newPassword', 'Session expired. Please start over.');
            return;
        }

        if (PinSecurity::isWeak($this->newPassword)) {
            $this->addError('newPassword', PinSecurity::weakPinMessage());
            return;
        }

        $user->update([
            'login_password' => Hash::make($this->newPassword, ['rounds' => 12]),
            'must_change_password' => false,
        ]);

        session()->forget('pwd_reset_user_id');

        $this->dispatch('notify-success', message: 'Password reset successful. Please log in with your new password.');

        return redirect()->route('login');
    }

    protected function rules(): array
    {
        return match ($this->step) {
            1 => ['phoneInput' => ['required', 'string', 'regex:/^[0-9]{10,14}$/']],
            2 => [
                'otp1' => ['required', 'digits:1'],
                'otp2' => ['required', 'digits:1'],
                'otp3' => ['required', 'digits:1'],
                'otp4' => ['required', 'digits:1'],
                'otp5' => ['required', 'digits:1'],
                'otp6' => ['required', 'digits:1'],
            ],
            3 => [
                'newPassword' => ['required', 'string', 'size:6'],
                'newPasswordConfirmation' => ['required', 'string', 'same:newPassword'],
            ],
            default => [],
        };
    }

    protected function validateOnlyStep(): void
    {
        $this->validate($this->rules());
    }

    public function render()
    {
        return view('livewire.auth.forgot-password')->layout('components.layouts.app');
    }
}
