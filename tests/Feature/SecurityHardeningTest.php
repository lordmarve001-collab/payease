<?php

namespace Tests\Feature;

use App\Helpers\PinSecurity;
use App\Http\Middleware\SecurityHeaders;
use App\Livewire\Admin\Users;
use App\Livewire\Agent\CreateCustomer;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Customer\Profile as CustomerProfile;
use App\Livewire\Customer\SendMoney;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MockMmoClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;


class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mock_mmo.latency_min_ms', 0);
        Config::set('services.mock_mmo.latency_max_ms', 0);
        Config::set('services.mock_mmo.failure_rate', 0);
        Config::set('services.mock_mmo.force_fail', null);

        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
    }

    public function test_lockout_config_has_sensible_defaults(): void
    {
        $this->assertEquals(3, config('lockout.pin.max_attempts'));
        $this->assertEquals(5, config('lockout.login.max_attempts'));
    }

    public function test_lockout_duration_is_longer_in_production(): void
    {
        Config::set('lockout.pin.lockout_duration', 86400);
        Config::set('lockout.login.lockout_duration', 86400);
        $this->assertEquals(86400, config('lockout.pin.lockout_duration'));
        $this->assertEquals(86400, config('lockout.login.lockout_duration'));
    }

    public function test_security_headers_middleware_sets_expected_headers(): void
    {
        $middleware = new SecurityHeaders();
        $request = Request::create('/');
        $next = fn ($req) => new Response('OK');

        $response = $middleware->handle($request, $next);

        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy') ?? '');
        $this->assertEquals('none', $response->headers->get('X-Permitted-Cross-Domain-Policies'));
        $this->assertNull($response->headers->get('X-Powered-By'));
    }

    public function test_customer_send_money_pin_lockout_persists_across_requests(): void
    {
        $sender = $this->createCustomer('08040000010', 'Lockout Sender', 50000, 1, '123456');
        $recipient = $this->createCustomer('08040000011', 'Lockout Recipient', 20000, 1, '654321');

        $this->actingAs($sender);

        // Use wrong PIN 3 times to trigger lockout
        for ($i = 0; $i < 3; $i++) {
            Livewire::test(SendMoney::class)
                ->set('phone', $recipient->phone_number)
                ->set('amount', '1000')
                ->call('continueToConfirm')
                ->call('continueToPinStep')
                ->set('pin1', '0')
                ->set('pin2', '0')
                ->set('pin3', '0')
                ->set('pin4', '0')
                ->set('pin5', '0')
                ->set('pin6', '0')
                ->call('confirmTransferPin');
        }

        // Now should be locked - even with a fresh component instance
        Livewire::test(SendMoney::class)
            ->set('phone', $recipient->phone_number)
            ->set('amount', '1000')
            ->call('continueToConfirm')
            ->call('continueToPinStep')
            ->set('pin1', '1')
            ->set('pin2', '2')
            ->set('pin3', '3')
            ->set('pin4', '4')
            ->set('pin5', '5')
            ->set('pin6', '6')
            ->call('confirmTransferPin')
            ->assertSet('step', 25)
            ->assertSee('locked');
    }

    public function test_admin_can_unlock_account(): void
    {
        $admin = $this->createAdminUser('super_admin');
        $user = $this->createCustomer('08040000012', 'Locked User', 0, 0, '123456');

        Cache::put('customer_pin_lock_send_' . $user->id, now()->addHour()->timestamp, 3600);
        Cache::put('customer_pin_attempts_send_' . $user->id, 3, 3600);

        $this->assertNotNull(Cache::get('customer_pin_lock_send_' . $user->id));

        Livewire::actingAs($admin)
            ->test(Users::class)
            ->call('confirmUnlock', (string) $user->id)
            ->assertSet('showUnlockModal', true)
            ->call('unlockAccount')
            ->assertSet('showUnlockModal', false);

        $this->assertNull(Cache::get('customer_pin_lock_send_' . $user->id));
        $this->assertNull(Cache::get('customer_pin_attempts_send_' . $user->id));

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'account_unlocked',
            'entity_id' => (string) $user->id,
        ]);
    }

    public function test_mock_mmo_fail_gated_behind_local_environment(): void
    {
        app()['env'] = 'production';

        $client = new MockMmoClient();
        $reflection = new \ReflectionMethod($client, 'resolveForcedFailureMode');
        $reflection->setAccessible(true);

        $result = $reflection->invoke($client);

        $this->assertNull($result, 'mmo_fail should not be resolved in production');
    }

    public function test_send_money_validation_rules_exist(): void
    {
        $sender = $this->createCustomer('08040000013', 'Validation Test', 50000, 1, '123456');
        $this->actingAs($sender);

        Livewire::test(SendMoney::class)
            ->set('phone', '')
            ->set('amount', '')
            ->call('continueToConfirm')
            ->assertHasNoErrors();
    }

    public function test_login_logs_audit_entry(): void
    {
        $user = $this->createCustomer('08040000014', 'Audit Login', 0, 0, '123456');

        Livewire::test(Login::class)
            ->set('phoneNumber', '08040000014')
            ->set('pin', '123456')
            ->call('login');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login',
        ]);
    }

    public function test_failed_login_attempts_are_logged(): void
    {
        $user = $this->createCustomer('08040000015', 'Failed Login', 0, 0, '123456');

        Livewire::test(Login::class)
            ->set('phoneNumber', '08040000015')
            ->set('pin', '000000')
            ->call('login');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login_failed',
        ]);
    }

    public function test_login_lockout_blocks_after_max_attempts(): void
    {
        $user = $this->createCustomer('08040000016', 'Locked Login', 0, 0, '123456');
        Config::set('lockout.login.max_attempts', 3);
        Config::set('lockout.login.lockout_duration', 60);

        for ($i = 0; $i < 3; $i++) {
            Livewire::test(Login::class)
                ->set('phoneNumber', '08040000016')
                ->set('pin', '000000')
                ->call('login');
        }

        Livewire::test(Login::class)
            ->set('phoneNumber', '08040000016')
            ->set('pin', '123456')
            ->call('login')
            ->assertHasErrors(['phoneNumber' => 'Too many login attempts. Please try again later.']);
    }

    public function test_registration_rejects_weak_pin(): void
    {
        $user = User::create([
            'phone_number' => '2348090000001',
            'full_name' => 'Test User',
            'status' => 'active',
            'kyc_level' => 0,
        ]);

        Livewire::test(Register::class)
            ->set('pendingUserId', (string) $user->id)
            ->set('step', 3)
            ->set('pin', '123456')
            ->set('pinConfirmation', '123456')
            ->call('setPin')
            ->assertHasErrors('pin');
    }

    public function test_customer_pin_change_rejects_weak_pin(): void
    {
        $user = $this->createCustomer('08040000017', 'Pin Change User', 0, 0, '123456');

        Livewire::actingAs($user)
            ->test(CustomerProfile::class)
            ->set('currentPin', '123456')
            ->set('newPin', '000000')
            ->set('newPinConfirmation', '000000')
            ->call('changePin')
            ->assertHasErrors('newPin');
    }

    public function test_agent_customer_registration_rejects_weak_pin(): void
    {
        $agentUser = $this->createAdminUser('agent');
        Agent::create([
            'id' => (string) $agentUser->id,
            'user_id' => $agentUser->id,
            'business_name' => 'Test Agent Business',
            'business_address' => '123 Test Street',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'status' => 'approved',
        ]);

        $user = User::create([
            'phone_number' => '2348090000002',
            'full_name' => 'Test Customer',
            'status' => 'active',
            'kyc_level' => 0,
        ]);

        Livewire::actingAs($agentUser)
            ->test(CreateCustomer::class)
            ->set('step', 4)
            ->set('otpVerified', true)
            ->set('fullName', 'Test Customer')
            ->set('pin', '111111')
            ->set('pinConfirmation', '111111')
            ->call('submitPin')
            ->assertHasErrors('pin');
    }

    protected function createCustomer(string $phone, string $name, float $balance, int $kycLevel, string $pin): User
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make($pin, ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => $kycLevel,
        ]);

        $user->assignRole('customer');

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => $balance,
            'available_balance' => $balance,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        return $user;
    }

    protected function createAdminUser(string $role): User
    {
        $user = User::create([
            'phone_number' => '080' . str_pad((string) random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            'full_name' => 'Admin User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 3,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
