<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Livewire\Component;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class VerifyOtp extends Component
{
    public $otp1 = '';
    public $otp2 = '';
    public $otp3 = '';
    public $otp4 = '';
    public $otp5 = '';
    public $otp6 = '';
    public $showOtp = false;
    public int $resendAvailableIn = 0;

    protected function rules(): array
    {
        return [
            'otp1' => ['required', 'digits:1'],
            'otp2' => ['required', 'digits:1'],
            'otp3' => ['required', 'digits:1'],
            'otp4' => ['required', 'digits:1'],
            'otp5' => ['required', 'digits:1'],
            'otp6' => ['required', 'digits:1'],
        ];
    }

    protected function getOtp()
    {
        return $this->otp1 . $this->otp2 . $this->otp3 . $this->otp4 . $this->otp5 . $this->otp6;
    }

    public function updated($field)
    {
        if (in_array($field, ['otp1', 'otp2', 'otp3', 'otp4', 'otp5', 'otp6'])) {
            $this->$field = substr($this->$field, -1);
            if (strlen($this->$field) && $field !== 'otp6') {
                $next = 'otp' . ((int)substr($field, -1) + 1);
                $this->dispatch('focus-next', field: $next);
            }
        }
    }

    public function verify()
    {
        $this->validate();

        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('register');
        }

        $user = User::find($userId);
        if (!$user) {
            session()->forget('otp_user_id');
            return redirect()->route('register');
        }

        $otpService = app(OtpService::class);

        $attemptKey = 'verify_otp_attempts_' . $user->id;
        $lockKey = 'verify_otp_locked_' . $user->id;
        $maxAttempts = 5;
        $lockoutMinutes = 30;

        if (Cache::get($lockKey)) {
            $this->addError('otp', 'Too many attempts. Please try again in ' . $lockoutMinutes . ' minutes.');
            return;
        }

        if (!$otpService->verifyOtp($user, $this->getOtp())) {
            $attempts = (int) Cache::increment($attemptKey, 1);
            Cache::put($attemptKey, $attempts, now()->addMinutes($lockoutMinutes));

            if ($attempts >= $maxAttempts) {
                Cache::put($lockKey, true, now()->addMinutes($lockoutMinutes));
                $this->addError('otp', 'Too many attempts. Please try again in ' . $lockoutMinutes . ' minutes.');
                return;
            }

            $remaining = $maxAttempts - $attempts;
            $this->addError('otp', 'Invalid OTP. ' . $remaining . ' attempt(s) remaining.');
            return;
        }

        Cache::forget($attemptKey);
        Cache::forget($lockKey);

        $otpService->clearOtp($user);
        $user->update(['kyc_level' => 1, 'kyc_verified_at' => now()]);

        session()->forget('otp_user_id');
        session()->put('registration_verified_user_id', $user->id);

        return redirect()->route('register');
    }

    public function mount()
    {
        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('register');
        }
        $this->showOtp = app()->environment('local');
        $user = User::find($userId);
        if ($user) {
            $this->resendAvailableIn = app(OtpService::class)->getCooldownSeconds($user);
        }
    }

    public function tick(): void
    {
        if ($this->resendAvailableIn > 0) {
            $this->resendAvailableIn--;
        }
    }

    public function resendOtp()
    {
        $userId = session('otp_user_id');
        if (!$userId) {
            return redirect()->route('register');
        }

        $user = User::find($userId);
        if (!$user) {
            return redirect()->route('register');
        }

        try {
            app(OtpService::class)->sendOtp($user);
            $this->resendAvailableIn = app(OtpService::class)->getCooldownSeconds($user);
            $this->dispatch('notify-success', message: 'A new OTP has been sent to your phone.');
        } catch (RuntimeException $exception) {
            $this->addError('otp', $exception->getMessage());
            $this->dispatch('notify-error', message: $exception->getMessage());
        }
    }

    public function render()
    {
        $userId = session('otp_user_id');
        $storedOtp = null;
        if ($this->showOtp && $userId) {
            $user = User::find($userId);
            $storedOtp = $user ? cache()->get('otp_' . $user->id) : null;
        }
        return view('livewire.auth.verify-otp', [
            'storedOtp' => $storedOtp,
        ])->layout('components.layouts.app');
    }
}
