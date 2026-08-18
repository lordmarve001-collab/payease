<?php

namespace App\Livewire\Auth;

use App\Helpers\PhoneNumberHelper;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Login extends Component
{
    public $phoneNumber;
    public $pin;

    protected $rules = [
        'phoneNumber' => 'required|string',
        'pin' => 'required|string|digits:6',
    ];

    public function login()
    {
        $this->validate();

        // Strip non-digits from phone input
        $this->phoneNumber = preg_replace('/\D/', '', $this->phoneNumber);

        try {
            $normalizedPhone = PhoneNumberHelper::normalize($this->phoneNumber);
        } catch (\InvalidArgumentException) {
            $this->addError('phoneNumber', 'Enter a valid Nigerian phone number (10-14 digits).');
            return;
        }

        $maxAttempts = (int) config('lockout.login.max_attempts', 5);
        $lockoutDuration = (int) config('lockout.login.lockout_duration', 86400);

        $lockKey = 'login_lock_' . $normalizedPhone;
        if (Cache::has($lockKey)) {
            $this->addError('phoneNumber', 'Too many login attempts. Please try again later.');
            return;
        }

        $user = User::where('phone_number', $normalizedPhone)->first()
              ?? User::where('phone_number', trim($this->phoneNumber))->first();

        if (!$user || !$user->verifyLoginPin($this->pin)) {
            $attemptKey = 'login_attempts_' . $normalizedPhone;
            $attempts = Cache::increment($attemptKey, 1);
            Cache::put($attemptKey, $attempts, $lockoutDuration);

            AuditLog::create([
                'user_id' => $user?->id,
                'action' => 'login_failed',
                'entity_type' => 'user',
                'entity_id' => $user?->id,
                'old_values' => null,
                'new_values' => [
                    'phone_number' => $normalizedPhone,
                    'attempt' => $attempts,
                ],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            if ($attempts >= $maxAttempts) {
                Cache::put($lockKey, true, $lockoutDuration);
                $this->addError('phoneNumber', 'Too many login attempts. Account locked.');
            } else {
                $this->addError('phoneNumber', 'Invalid credentials.');
            }
            return;
        }

        if (strtolower((string) $user->status) === 'suspended') {
            $this->addError('phoneNumber', 'This account has been suspended. Please contact support.');
            return;
        }

        Cache::forget($lockKey);
        Cache::forget('login_attempts_' . $normalizedPhone);

        \Log::info('LoginFlow: successful for user ' . $user->id);

        Auth::loginUsingId($user->id);

        $user->update(['last_login_at' => now()]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'old_values' => null,
            'new_values' => ['phone_number' => $normalizedPhone],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        if ($user->must_change_password) {
            $this->redirect(route('password.change'), navigate: false);
            return;
        }

        $this->redirect($this->getDashboardUrl($user), navigate: false);
    }

    protected function getDashboardUrl(User $user): string
    {
        if ($user->hasRole('super_admin') || $user->hasRole('admin')) {
            return route('admin.overview');
        } elseif ($user->hasRole('agent')) {
            if ($user->agent()->exists()) {
                return route('ajo-agent.dashboard');
            }
            return route('agent.dashboard');
        } elseif ($user->hasRole('ajo_owner')) {
            return route('ajo-owner.dashboard');
        } else {
            return route('customer.dashboard');
        }
    }

    public function render()
    {
        return view('livewire.auth.login')->layout('components.layouts.app');
    }
}
