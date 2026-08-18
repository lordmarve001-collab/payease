<?php

namespace App\Livewire\Auth;

use App\Helpers\PhoneNumberHelper;
use App\Helpers\PinSecurity;
use App\Jobs\SendSmsNotification;
use App\Mail\WelcomeMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\OtpService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use RuntimeException;

class Register extends Component
{
    public int $step = 1;
    public string $phoneInput = '';
    public string $emailInput = '';
    public string $otp1 = '';
    public string $otp2 = '';
    public string $otp3 = '';
    public string $otp4 = '';
    public string $otp5 = '';
    public string $otp6 = '';
    public int $resendAvailableIn = 0;
    public string $pin = '';
    public string $pinConfirmation = '';
    public string $loginPassword = '';
    public string $fullName = '';
    public bool $terms = false;
    public string $normalizedPhone = '';
    public ?string $pendingUserId = null;

    public function boot(): void
    {
        $verifiedUserId = session()->pull('registration_verified_user_id');
        if ($verifiedUserId) {
            $user = User::find($verifiedUserId);
            if ($user) {
                $this->pendingUserId = $user->id;
                $this->normalizedPhone = $user->phone_number;
                $this->phoneInput = ltrim($user->phone_number, '+234');
                $this->step = 3;
            }
        }
    }

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
            ],
            4 => [
                'emailInput' => ['nullable', 'email', 'max:255'],
                'fullName' => ['required', 'string', 'max:255'],
                'terms' => ['accepted'],
            ],
            default => [],
        };
    }

    protected $messages = [
        'phoneInput.required' => 'Please enter your phone number.',
        'pin.digits' => 'Your transaction PIN must be exactly 6 digits.',
        'pinConfirmation.same' => 'PIN confirmation does not match.',
    ];

    public function sendOtp(): void
    {
        $this->validateOnly('phoneInput');

        // Strip non-digits from phone input
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
            $this->addError('phoneInput', 'This phone number is already registered. Please log in.');
            return;
        }

        $user = User::create([
            'phone_number' => $this->normalizedPhone,
            'status' => 'active',
            'kyc_level' => 0,
        ]);

        $this->pendingUserId = $user->id;
        session()->put('otp_user_id', $user->id);

        try {
            app(OtpService::class)->sendOtp($user, enforceCooldown: false);
            $this->step = 2;
        } catch (RuntimeException $exception) {
            $user->delete();
            $this->addError('phoneInput', $exception->getMessage());
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

    public function verifyOtp(): void
    {
        $this->validateOnlyStep();

        $user = User::find($this->pendingUserId);
        if (!$user) {
            $this->addError('otp1', 'Session expired. Please start again.');
            return;
        }

        $attemptKey = 'register_otp_attempts_' . $user->id;
        $lockKey = 'register_otp_locked_' . $user->id;
        $maxAttempts = 5;
        $lockoutMinutes = 30;

        if (Cache::get($lockKey)) {
            $this->addError('otp1', 'Too many attempts. Please try again in ' . $lockoutMinutes . ' minutes.');
            return;
        }

        if (!app(OtpService::class)->verifyOtp($user, $this->getOtpString())) {
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

        app(OtpService::class)->clearOtp($user);
        $this->step = 3;
    }

    public function resendOtp(): void
    {
        $user = User::find($this->pendingUserId);
        if (!$user) {
            $this->redirect(route('register'));
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

    public function setPin(): void
    {
        $this->validateOnlyStep();

        if (PinSecurity::isWeak($this->pin)) {
            $this->addError('pin', PinSecurity::weakPinMessage());
            return;
        }

        $this->step = 4;
    }

    public function goBackToPin(): void
    {
        $this->step = 3;
    }

    public function goBackToOtp(): void
    {
        $this->step = 2;
    }

    public function completeRegistration(): void
    {
        $this->validateOnlyStep();

        $user = User::find($this->pendingUserId);
        if (!$user) {
            $this->addError('fullName', 'Session expired. Please start again.');
            return;
        }

        $plainPassword = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->update([
            'full_name' => $this->fullName,
            'email' => $this->emailInput ?: null,
            'pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
            'login_pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
            'transfer_pin_hash' => Hash::make($this->pin, ['rounds' => 12]),
            'login_password' => Hash::make($plainPassword),
            'must_change_password' => true,
            'kyc_level' => 1,
            'kyc_verified_at' => now(),
        ]);

        $user->assignRole('customer');

        app(WalletService::class)->createTierWallet($user, 1);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'registration',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'old_values' => null,
            'new_values' => [
                'full_name' => $this->fullName,
                'phone_number' => $user->phone_number,
                'email' => $this->emailInput ?: null,
                'kyc_level' => 1,
            ],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        if ($this->emailInput) {
            rescue(function () use ($user, $plainPassword): void {
                Mail::to($user->email)->send(new WelcomeMail($user, $plainPassword));
            }, report: false);
        }

        rescue(function () use ($user, $plainPassword): void {
            SendSmsNotification::dispatch(
                $user->phone_number,
                "Welcome to PayEase, {$this->fullName}! Default password: {$plainPassword}. Please change it after login. -PayEase"
            );
        }, report: false);

        auth()->login($user);

        session()->forget('otp_user_id');

        $this->dispatch('notify-success', message: 'Welcome to PayEase!');

        $this->redirect(route('customer.dashboard'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.register')->layout('components.layouts.app');
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
