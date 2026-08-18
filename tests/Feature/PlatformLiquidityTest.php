<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use App\Models\Wallet;
use App\Services\PlatformLiquidityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformLiquidityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'super_admin', 'customer', 'agent'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        config(['services.platform_liquidity.master_balance' => 1000000]);
        config(['services.platform_liquidity.minimum_threshold' => 50000]);
    }

    protected function createAgentForUser(User $user, float $floatBalance = 0): Agent
    {
        return Agent::create([
            'user_id' => $user->id,
            'business_name' => 'Liquidity Agent Shop',
            'business_address' => '123 Test Street',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'float_balance' => $floatBalance,
            'max_float' => 500000,
            'commission_rate' => 2.5,
            'total_earnings' => 0,
            'status' => 'active',
        ]);
    }

    public function test_liquidity_snapshot_calculates_customer_agent_and_available_funds(): void
    {
        $user = User::create([
            'phone_number' => '2348012346001',
            'full_name' => 'Liquidity Customer',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 250000,
            'available_balance' => 250000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $agentUser = User::create([
            'phone_number' => '2348012346002',
            'full_name' => 'Liquidity Agent',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->createAgentForUser($agentUser, 150000);

        $service = app(PlatformLiquidityService::class);
        $snapshot = $service->getLiquiditySnapshot();

        $this->assertEqualsWithDelta(250000, $snapshot['customer_wallet_balances'], 0.001);
        $this->assertEqualsWithDelta(150000, $snapshot['agent_unsettled_obligations'], 0.001);
        $this->assertEqualsWithDelta(1000000, $snapshot['platform_master_balance'], 0.001);
        $this->assertEqualsWithDelta(600000, $snapshot['available_platform_funds'], 0.001);
        $this->assertTrue($snapshot['is_healthy']);
    }

    public function test_liquidity_is_unhealthy_when_below_threshold(): void
    {
        config(['services.platform_liquidity.master_balance' => 300000]);

        $user = User::create([
            'phone_number' => '2348012346003',
            'full_name' => 'Liquidity Customer Two',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 200000,
            'available_balance' => 200000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $agentUser = User::create([
            'phone_number' => '2348012346004',
            'full_name' => 'Liquidity Agent Two',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->createAgentForUser($agentUser, 100000);

        $service = app(PlatformLiquidityService::class);
        $snapshot = $service->getLiquiditySnapshot();

        $this->assertEqualsWithDelta(0, $snapshot['available_platform_funds'], 0.001);
        $this->assertFalse($snapshot['is_healthy']);
    }

    public function test_assert_sufficient_liquidity_throws_when_below_threshold(): void
    {
        config(['services.platform_liquidity.master_balance' => 300000]);

        $user = User::create([
            'phone_number' => '2348012346005',
            'full_name' => 'Liquidity Customer Three',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 200000,
            'available_balance' => 200000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $agentUser = User::create([
            'phone_number' => '2348012346006',
            'full_name' => 'Liquidity Agent Three',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->createAgentForUser($agentUser, 100000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Platform liquidity is below the safety threshold');

        app(PlatformLiquidityService::class)->assertSufficientLiquidity(10000);
    }

    public function test_assert_sufficient_liquidity_throws_when_amount_exceeds_available(): void
    {
        config(['services.platform_liquidity.master_balance' => 500000]);

        $user = User::create([
            'phone_number' => '2348012346007',
            'full_name' => 'Liquidity Customer Four',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 100000,
            'available_balance' => 100000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $agentUser = User::create([
            'phone_number' => '2348012346008',
            'full_name' => 'Liquidity Agent Four',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->createAgentForUser($agentUser, 100000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exceeds available platform funds');

        app(PlatformLiquidityService::class)->assertSufficientLiquidity(300001);
    }

    public function test_bank_transfer_is_blocked_when_liquidity_is_low(): void
    {
        config(['services.platform_liquidity.master_balance' => 300000]);

        $user = User::create([
            'phone_number' => '2348012346009',
            'full_name' => 'Liquidity Customer Five',
            'kyc_level' => 2,
            'status' => 'active',
            'pin_hash' => bcrypt('123456'),
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 500000,
            'available_balance' => 500000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
            'account_number' => '1234567890',
        ]);

        $agentUser = User::create([
            'phone_number' => '2348012346010',
            'full_name' => 'Liquidity Agent Five',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->createAgentForUser($agentUser, 100000);

        $this->actingAs($user);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Platform liquidity is below the safety threshold');

        app(\App\Services\TransactionService::class)->initiateBankTransferDisbursement(
            $user,
            '044',
            '1234567890',
            'Recipient Person',
            50000,
            'Test bank transfer'
        );
    }

    public function test_admin_liquidity_page_renders(): void
    {
        $admin = User::create([
            'phone_number' => '2348012346011',
            'full_name' => 'Liquidity Admin',
            'kyc_level' => 1,
            'status' => 'active',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
        ]);
        $admin->assignRole('super_admin');

        $this->actingAs($admin);

        $response = $this->get(route('admin.liquidity'));
        $response->assertOk();
        $response->assertSee('Platform Liquidity');
        $response->assertSee('Available Platform Funds');
    }

    public function test_settlement_verification_updates_last_settlement_at(): void
    {
        $admin = User::create([
            'phone_number' => '2348012346012',
            'full_name' => 'Settlement Admin',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $agentUser = User::create([
            'phone_number' => '2348012346013',
            'full_name' => 'Settlement Agent',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $agent = $this->createAgentForUser($agentUser, 100000);

        $settlement = \App\Models\AgentSettlement::create([
            'agent_id' => $agent->id,
            'amount_declared' => 50000,
            'bank_reference' => 'REF123',
            'status' => 'pending_verification',
        ]);

        $this->assertNull($agent->fresh()->last_settlement_at);

        app(\App\Services\FloatSettlementService::class)->verifySettlement($settlement, $admin);

        $this->assertNotNull($agent->fresh()->last_settlement_at);
    }
}
