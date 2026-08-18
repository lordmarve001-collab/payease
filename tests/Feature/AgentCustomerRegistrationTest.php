<?php

namespace Tests\Feature;

use App\Helpers\PhoneNumberHelper;
use App\Jobs\SendSmsNotification;
use App\Livewire\Agent\CreateCustomer;
use App\Livewire\Agent\Customers;
use App\Livewire\Agent\UpgradeCustomer;
use App\Models\Agent;
use App\Models\KycDocument;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AgentCustomerService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgentCustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected Agent $agent;
    protected User $agentUser;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['customer', 'agent', 'ajo_owner', 'admin', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        Config::set('services.mock_mmo.latency_min_ms', 0);
        Config::set('services.mock_mmo.latency_max_ms', 0);
        Config::set('services.mock_mmo.failure_rate', 0);
        Config::set('services.mock_mmo.force_fail', null);

        $this->agentUser = $this->createUserWithRole('08030000001', 'Test Agent', 'agent', 2);
        $this->agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'business_name' => 'Test Agent Business',
            'business_address' => '12 Market Road',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'float_balance' => 50000,
            'max_float' => 100000,
            'commission_rate' => 1.50,
            'total_earnings' => 0,
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }

    #[Test]
    public function agent_can_register_customer_via_service(): void
    {
        $service = app(AgentCustomerService::class);
        $phone = '08040000001';
        $name = 'New Customer';

        $user = $service->registerCustomerViaAgent($this->agent, $phone, $name);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'phone_number' => '8040000001',
            'full_name' => $name,
            'kyc_level' => 1,
            'registered_by_agent_id' => $this->agent->id,
        ]);

        $this->assertTrue($user->hasRole('customer'));

        $this->assertDatabaseHas('wallets', [
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'daily_limit' => 5000,
            'single_txn_limit' => 2000,
        ]);
    }

    #[Test]
    public function agent_cannot_register_duplicate_phone(): void
    {
        $service = app(AgentCustomerService::class);
        $service->registerCustomerViaAgent($this->agent, '08040000002', 'First Customer');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('A user with this phone number already exists.');

        $service->registerCustomerViaAgent($this->agent, '08040000002', 'Duplicate Customer');
    }

    #[Test]
    public function registered_customer_has_registered_by_agent_id_set(): void
    {
        $service = app(AgentCustomerService::class);
        $user = $service->registerCustomerViaAgent($this->agent, '08040000003', 'Agent Registered');

        $this->assertEquals($this->agent->id, $user->registered_by_agent_id);
        $this->assertNotNull($user->registeredByAgent);
        $this->assertEquals($this->agent->id, $user->registeredByAgent->id);
    }

    #[Test]
    public function create_customer_component_has_step_one_loaded(): void
    {
        $this->actingAs($this->agentUser);

        Livewire::test(CreateCustomer::class)
            ->assertSet('step', 1)
            ->assertSee('Enter Customer Phone Number');
    }

    #[Test]
    public function create_customer_submit_phone_with_invalid_number_shows_error(): void
    {
        $this->actingAs($this->agentUser);

        Livewire::test(CreateCustomer::class)
            ->set('phone', '123')
            ->call('submitPhone')
            ->assertHasErrors('phone');
    }

    #[Test]
    public function create_customer_submit_phone_with_existing_number_shows_error(): void
    {
        $this->actingAs($this->agentUser);

        $this->createUserWithRole('08050000001', 'Existing User', 'customer', 1);

        Livewire::test(CreateCustomer::class)
            ->set('phone', '08050000001')
            ->call('submitPhone')
            ->assertHasErrors('phone');
    }

    #[Test]
    public function create_customer_submit_valid_phone_proceeds_to_name_step(): void
    {
        $this->actingAs($this->agentUser);

        Livewire::test(CreateCustomer::class)
            ->set('phone', '08060000001')
            ->call('submitPhone')
            ->assertSet('step', 2);
    }

    #[Test]
    public function create_customer_submit_name_proceeds_to_otp_step(): void
    {
        $this->actingAs($this->agentUser);

        Livewire::test(CreateCustomer::class)
            ->set('phone', '08060000002')
            ->call('submitPhone')
            ->set('fullName', 'John Doe')
            ->call('submitName')
            ->assertSet('step', 3);
    }

    #[Test]
    public function create_customer_verify_otp_proceeds_to_pin_step(): void
    {
        $this->actingAs($this->agentUser);

        $component = Livewire::test(CreateCustomer::class)
            ->set('phone', '08060000003')
            ->call('submitPhone')
            ->set('fullName', 'Jane Doe')
            ->call('submitName');

        $userId = session('agent_reg_user_id');
        $otp = \Illuminate\Support\Facades\Cache::get('otp_' . $userId);

        $component
            ->set('otp', $otp)
            ->call('verifyOtp')
            ->assertSet('step', 4)
            ->assertSet('otpVerified', true);
    }

    #[Test]
    public function create_customer_verify_invalid_otp_shows_error(): void
    {
        $this->actingAs($this->agentUser);

        $component = Livewire::test(CreateCustomer::class)
            ->set('phone', '08060000004')
            ->call('submitPhone')
            ->set('fullName', 'Bad OTP User')
            ->call('submitName');

        session(['agent_reg_otp' => '999999']);

        $component
            ->set('otp', '000000')
            ->call('verifyOtp')
            ->assertHasErrors('otp');
    }

    #[Test]
    public function create_customer_submit_pin_creates_user(): void
    {
        $this->actingAs($this->agentUser);

        $component = Livewire::test(CreateCustomer::class)
            ->set('phone', '08060000005')
            ->call('submitPhone')
            ->set('fullName', 'PIN Test User')
            ->call('submitName');

        $component
            ->set('pin', '584927')
            ->set('pinConfirmation', '584927')
            ->call('submitPin')
            ->assertSet('step', 5);

        $this->assertDatabaseHas('users', [
            'phone_number' => '8060000005',
            'full_name' => 'PIN Test User',
            'kyc_level' => 1,
            'registered_by_agent_id' => $this->agent->id,
        ]);
    }

    #[Test]
    public function create_customer_pin_mismatch_shows_error(): void
    {
        $this->actingAs($this->agentUser);

        $component = Livewire::test(CreateCustomer::class)
            ->set('phone', '08060000006')
            ->call('submitPhone')
            ->set('fullName', 'PIN Mismatch User')
            ->call('submitName');

        $component
            ->set('pin', '584927')
            ->set('pinConfirmation', '567890')
            ->call('submitPin')
            ->assertHasErrors('pinConfirmation');
    }

    #[Test]
    public function upgrade_customer_search_by_phone_finds_customer(): void
    {
        $this->actingAs($this->agentUser);

        $customer = $this->createUserWithRole('08070000001', 'Upgrade Customer', 'customer', 1);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000001')
            ->call('searchCustomer')
            ->assertSet('step', 2)
            ->assertSet('customer.id', $customer->id);
    }

    #[Test]
    public function upgrade_customer_search_nonexistent_phone_shows_error(): void
    {
        $this->actingAs($this->agentUser);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08099999999')
            ->call('searchCustomer')
            ->assertHasErrors('searchPhone');
    }

    #[Test]
    public function upgrade_customer_select_tier_proceeds_to_details(): void
    {
        $this->actingAs($this->agentUser);

        $customer = $this->createUserWithRole('08070000002', 'Tier Selector', 'customer', 1);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000002')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->assertSet('step', 3)
            ->assertSet('targetTier', 2);
    }

    #[Test]
    public function upgrade_customer_cannot_select_same_or_lower_tier(): void
    {
        $this->actingAs($this->agentUser);

        $customer = $this->createUserWithRole('08070000003', 'Already Tier 2', 'customer', 2);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000003')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->assertSet('step', 2);
    }

    #[Test]
    public function upgrade_customer_cannot_select_tier3_without_tier2(): void
    {
        $this->actingAs($this->agentUser);

        $customer = $this->createUserWithRole('08070000004', 'Tier 1 User', 'customer', 1);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000004')
            ->call('searchCustomer')
            ->call('selectTier', 3)
            ->assertSet('step', 2);
    }

    #[Test]
    public function upgrade_customer_tier2_pin_verification_works(): void
    {
        $this->actingAs($this->agentUser);

        $customer = $this->createUserWithRole('08070000050', 'PIN Verify', 'customer', 1);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000050')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->set('nin', '12345678901')
            ->set('bvn', '98765432101')
            ->set('nextOfKinName', 'Kin')
            ->set('nextOfKinRelationship', 'Spouse')
            ->set('nextOfKinPhone', '08080000050')
            ->call('submitTierDetails')
            ->assertSet('step', 4)
            ->set('customerPin', '123456')
            ->call('verifyCustomerPin')
            ->assertSet('step', 5);
    }

    #[Test]
    public function upgrade_customer_tier2_submits_kyc_documents(): void
    {
        $this->actingAs($this->agentUser);

        Storage::fake('public');

        $customer = $this->createUserWithRole('08070000005', 'Tier 2 Submit', 'customer', 1);

        Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000005')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->set('nin', '12345678901')
            ->set('bvn', '98765432101')
            ->set('nextOfKinName', 'Next Kin')
            ->set('nextOfKinRelationship', 'Spouse')
            ->set('nextOfKinPhone', '08080000001')
            ->set('ninSlip', UploadedFile::fake()->image('nin.jpg'))
            ->set('bvnSlip', UploadedFile::fake()->image('bvn.jpg'))
            ->set('livenessCapture', UploadedFile::fake()->image('liveness.jpg'))
            ->call('submitTierDetails')
            ->assertSet('step', 4)
            ->set('customerPin', '123456')
            ->call('verifyCustomerPin')
            ->assertSet('step', 5)
            ->assertDispatched('notify-success');

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $customer->id,
            'verification_status' => 'verified',
            'submitted_by_agent_id' => $this->agent->id,
        ]);

        $customer->refresh();
        $this->assertSame(2, (int) $customer->kyc_level);
        $this->assertNotNull($customer->nin_verified_at);
        $this->assertNotNull($customer->bvn_verified_at);
        $this->assertNotNull($customer->next_of_kin_submitted_at);
    }

    #[Test]
    public function upgrade_customer_wrong_pin_does_not_submit(): void
    {
        $this->actingAs($this->agentUser);

        $customer = $this->createUserWithRole('08070000006', 'Wrong PIN', 'customer', 1);

        // Set a known PIN hash
        $customer->update(['pin_hash' => Hash::make('123456', ['rounds' => 4])]);
        $customer->refresh();

        $component = Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000006')
            ->call('searchCustomer')
            ->call('selectTier', 2)
            ->set('nin', '12345678901')
            ->set('bvn', '98765432101')
            ->set('nextOfKinName', 'Next')
            ->set('nextOfKinRelationship', 'Spouse')
            ->set('nextOfKinPhone', '08080000002')
            ->call('submitTierDetails');

        $component
            ->set('customerPin', '9999')
            ->call('verifyCustomerPin')
            ->assertHasErrors('customerPin');
    }

    #[Test]
    public function agent_customer_service_submits_tier2_kyc(): void
    {
        $service = app(AgentCustomerService::class);
        Storage::fake('public');

        $customer = $this->createUserWithRole('08090000001', 'Svc Tier 2', 'customer', 1);

        $documents = [
            'nin_slip' => UploadedFile::fake()->image('nin.jpg'),
            'bvn_slip' => UploadedFile::fake()->image('bvn.jpg'),
            'liveness_capture' => UploadedFile::fake()->image('liveness.jpg'),
        ];

        $service->submitKycViaAgent($this->agent, $customer, 2, [
            'nin' => '12345678901',
            'bvn' => '98765432101',
            'next_of_kin_name' => 'Kin Name',
            'next_of_kin_relationship' => 'Sibling',
            'next_of_kin_phone' => '8080000003',
        ], $documents);

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $customer->id,
            'document_type' => 'nin_slip',
            'submitted_by_agent_id' => $this->agent->id,
        ]);

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $customer->id,
            'document_type' => 'bvn_slip',
            'submitted_by_agent_id' => $this->agent->id,
        ]);

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $customer->id,
            'document_type' => 'liveness_capture',
            'submitted_by_agent_id' => $this->agent->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'nin' => '12345678901',
            'bvn' => '98765432101',
            'next_of_kin_name' => 'Kin Name',
            'next_of_kin_relationship' => 'Sibling',
            'next_of_kin_phone' => '8080000003',
        ]);
    }

    #[Test]
    public function agent_customer_service_submits_tier3_kyc(): void
    {
        $service = app(AgentCustomerService::class);
        Storage::fake('public');

        $customer = $this->createUserWithRole('08090000002', 'Svc Tier 3', 'customer', 2);

        $documents = [
            'proof_of_address' => UploadedFile::fake()->image('address.jpg'),
        ];

        $service->submitKycViaAgent($this->agent, $customer, 3, [], $documents);

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $customer->id,
            'document_type' => 'proof_of_address',
            'submitted_by_agent_id' => $this->agent->id,
        ]);
    }

    #[Test]
    public function agent_customer_service_rejects_tier3_without_tier2(): void
    {
        $service = app(AgentCustomerService::class);

        $customer = $this->createUserWithRole('08090000003', 'No Tier 2', 'customer', 1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Customer must complete Tier 2 before upgrading to Tier 3.');

        $service->submitKycViaAgent($this->agent, $customer, 3, [], []);
    }

    #[Test]
    public function agent_customer_service_rejects_downgrade(): void
    {
        $service = app(AgentCustomerService::class);

        $customer = $this->createUserWithRole('08090000004', 'Already T3', 'customer', 3);

        $this->expectException(\RuntimeException::class);

        $service->submitKycViaAgent($this->agent, $customer, 2, [], []);
    }

    #[Test]
    public function agent_dashboard_shows_new_quick_action_tiles(): void
    {
        $this->actingAs($this->agentUser);

        $this->get('/agent/dashboard')
            ->assertSee('Create Customer')
            ->assertSee('Upgrade KYC')
            ->assertStatus(200);
    }

    #[Test]
    public function admin_users_shows_agent_attribution(): void
    {
        $adminUser = $this->createUserWithRole('08100000001', 'Admin User', 'super_admin', 3);

        // Register a customer via agent
        $service = app(AgentCustomerService::class);
        $customer = $service->registerCustomerViaAgent($this->agent, '08040000007', 'Agent Attr User');

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'registered_by_agent_id' => $this->agent->id,
        ]);
    }

    #[Test]
    public function created_customer_has_tier1_wallet_with_correct_limits(): void
    {
        $service = app(AgentCustomerService::class);
        $user = $service->registerCustomerViaAgent($this->agent, '08040000008', 'Limit Check');

        $wallet = $user->wallets()->where('wallet_type', 'customer')->first();

        $this->assertNotNull($wallet);
        $this->assertEquals(5000, (float) $wallet->daily_limit);
        $this->assertEquals(2000, (float) $wallet->single_txn_limit);
    }

    #[Test]
    public function kyc_document_model_has_submitted_by_agent_relation(): void
    {
        Storage::fake('public');
        $customer = $this->createUserWithRole('08090000005', 'Kyc Rel Test', 'customer', 1);

        $doc = KycDocument::create([
            'user_id' => $customer->id,
            'document_type' => 'nin_slip',
            'document_url' => '/storage/kyc-documents/test.jpg',
            'verification_status' => 'pending',
            'submitted_by_agent_id' => $this->agent->id,
        ]);

        $this->assertNotNull($doc->submittedByAgent);
        $this->assertEquals($this->agent->id, $doc->submittedByAgent->id);
    }

    #[Test]
    public function upgrade_customer_component_resets_properly(): void
    {
        $this->actingAs($this->agentUser);

        $customer = $this->createUserWithRole('08070000007', 'Reset Test', 'customer', 1);

        $component = Livewire::test(UpgradeCustomer::class)
            ->set('searchPhone', '08070000007')
            ->call('searchCustomer')
            ->assertSet('step', 2);

        $component
            ->call('resetAndStart')
            ->assertSet('step', 1)
            ->assertSet('searchPhone', '')
            ->assertSet('customer', null);
    }

    #[Test]
    public function create_customer_component_resets_properly(): void
    {
        $this->actingAs($this->agentUser);

        $component = Livewire::test(CreateCustomer::class)
            ->set('phone', '08060000007')
            ->call('submitPhone')
            ->set('fullName', 'Reset User')
            ->call('submitName')
            ->call('verifyOtp')
            ->set('pin', '584927')
            ->set('pinConfirmation', '584927')
            ->call('submitPin')
            ->assertSet('step', 5);

        $component
            ->call('resetAndStart')
            ->assertSet('step', 1)
            ->assertSet('phone', '')
            ->assertSet('fullName', '');
    }

    #[Test]
    public function agent_customers_page_shows_registered_customers(): void
    {
        $this->actingAs($this->agentUser);

        app(AgentCustomerService::class)->registerCustomerViaAgent(
            $this->agent, '08040000010', 'Customer One', 'one@example.com'
        );
        app(AgentCustomerService::class)->registerCustomerViaAgent(
            $this->agent, '08040000011', 'Customer Two'
        );

        $this->get('/agent/customers')
            ->assertStatus(200)
            ->assertSee('Customer One')
            ->assertSee('Customer Two')
            ->assertSee('one@example.com');
    }

    #[Test]
    public function agent_customers_page_shows_empty_state_when_no_customers(): void
    {
        $this->actingAs($this->agentUser);

        $this->get('/agent/customers')
            ->assertStatus(200)
            ->assertSee('No customers registered yet')
            ->assertSee('Register your first customer');
    }

    #[Test]
    public function agent_customers_page_search_filters_results(): void
    {
        $this->actingAs($this->agentUser);

        app(AgentCustomerService::class)->registerCustomerViaAgent(
            $this->agent, '08040000012', 'Alice Wonder'
        );
        app(AgentCustomerService::class)->registerCustomerViaAgent(
            $this->agent, '08040000013', 'Bob Builder'
        );

        Livewire::test(Customers::class)
            ->set('search', 'Alice')
            ->assertSee('Alice Wonder')
            ->assertDontSee('Bob Builder');
    }

    #[Test]
    public function agent_sidebar_includes_customers_link(): void
    {
        $this->actingAs($this->agentUser);

        $this->get('/agent/dashboard')
            ->assertSee('Customers')
            ->assertSee('/agent/customers');
    }

    #[Test]
    public function agent_customer_service_dispatches_welcome_sms(): void
    {
        Queue::fake();

        $service = app(AgentCustomerService::class);
        $service->registerCustomerViaAgent($this->agent, '08040000014', 'SMS Check');

        Queue::assertPushed(SendSmsNotification::class, function ($job) {
            return str_contains($job->message, 'Welcome to PayEase')
                && str_contains($job->message, 'Default password:')
                && str_contains($job->message, 'Please change it after login')
                && $job->phoneNumber === '8040000014';
        });
    }

    #[Test]
    public function registered_customer_has_must_change_password_flag(): void
    {
        $service = app(AgentCustomerService::class);
        $user = $service->registerCustomerViaAgent($this->agent, '08040000015', 'Pwd Flag Test');

        $this->assertTrue($user->must_change_password);
    }

    #[Test]
    public function registered_customer_gets_six_digit_default_password(): void
    {
        $service = app(AgentCustomerService::class);
        $user = $service->registerCustomerViaAgent($this->agent, '08040000016', 'Pwd Length');

        $this->assertNotNull($user->login_password);
    }

    protected function createUserWithRole(string $phone, string $name, string $role, int $kycLevel = 3): User
    {
        $normalizedPhone = PhoneNumberHelper::normalize($phone);

        $user = User::create([
            'phone_number' => $normalizedPhone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => $kycLevel,
        ]);

        $user->assignRole($role);

        if ($kycLevel >= 1) {
            $user->update(['kyc_verified_at' => now()]);
        }

        if ($role === 'customer') {
            app(WalletService::class)->createTierWallet($user, $kycLevel);
        }

        return $user;
    }
}
