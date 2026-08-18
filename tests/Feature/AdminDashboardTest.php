<?php

namespace Tests\Feature;

use App\Livewire\Admin\Agents as AdminAgents;
use App\Livewire\Admin\KycQueue as AdminKycQueue;
use App\Livewire\Agent\CashIn as AgentCashIn;
use App\Models\Agent;
use App\Models\KycDocument;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AdminService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

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
    }

    public function test_admin_service_returns_real_overview_metrics_charts_alerts_and_kyc_counts(): void
    {
        $olderCustomer = $this->createCustomer('08050000020', 'Older Customer', 12000, 1);
        $recentCustomer = $this->createCustomer('08050000021', 'Recent Customer', 15000, 2);
        $recipient = $this->createCustomer('08050000022', 'Recipient User', 8000, 2);
        $activeOlderAgent = $this->createAgent('08050000023', 'Older Active Agent', 40000, 'active');
        $activeRecentAgent = $this->createAgent('08050000024', 'Recent Active Agent', 5000, 'active');
        $this->createAgent('08050000025', 'Pending Agent', 3000, 'pending');

        $this->setCreatedAt($olderCustomer, now()->subDays(8));
        $this->setCreatedAt($activeOlderAgent, now()->subDays(8));
        $this->setCreatedAt($activeOlderAgent->user, now()->subDays(8));
        $this->setCreatedAt($recentCustomer, now()->subDay());
        $this->setCreatedAt($activeRecentAgent, now()->subDay());
        $this->setCreatedAt($activeRecentAgent->user, now()->subDay());

        KycDocument::create([
            'user_id' => $olderCustomer->id,
            'document_type' => 'National ID',
            'document_url' => '/storage/kyc/older-id.jpg',
            'verification_status' => 'pending',
        ]);
        KycDocument::create([
            'user_id' => $recentCustomer->id,
            'document_type' => 'Passport',
            'document_url' => '/storage/kyc/recent-passport.jpg',
            'verification_status' => 'verified',
        ]);
        KycDocument::create([
            'user_id' => $recipient->id,
            'document_type' => 'Voters Card',
            'document_url' => '/storage/kyc/voter-card.jpg',
            'verification_status' => 'rejected',
        ]);

        $olderWallet = $olderCustomer->wallets()->where('wallet_type', 'customer')->firstOrFail();
        $recentWallet = $recentCustomer->wallets()->where('wallet_type', 'customer')->firstOrFail();
        $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail();

        $todayTransfer = Transaction::create([
            'reference' => 'ADM-SVC-001',
            'transaction_type' => 'transfer',
            'amount' => 5000,
            'fee' => 25,
            'status' => 'completed',
            'from_wallet_id' => $olderWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipient->phone_number,
            'agent_id' => $activeOlderAgent->user_id,
            'description' => 'Today transfer',
            'mmo_partner' => 'mock',
            'completed_at' => now(),
        ]);
        $todayDeposit = Transaction::create([
            'reference' => 'ADM-SVC-002',
            'transaction_type' => 'deposit',
            'amount' => 3000,
            'commission' => 45,
            'status' => 'completed',
            'to_wallet_id' => $recentWallet->id,
            'agent_id' => $activeOlderAgent->user_id,
            'description' => 'Today deposit',
            'mmo_partner' => 'mock',
            'completed_at' => now(),
        ]);
        $yesterdayTransfer = Transaction::create([
            'reference' => 'ADM-SVC-003',
            'transaction_type' => 'transfer',
            'amount' => 2000,
            'fee' => 10,
            'status' => 'completed',
            'from_wallet_id' => $recentWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipient->phone_number,
            'agent_id' => $activeRecentAgent->user_id,
            'description' => 'Yesterday transfer',
            'mmo_partner' => 'mock',
            'completed_at' => now()->subDay(),
        ]);
        $recentFailed = Transaction::create([
            'reference' => 'ADM-SVC-004',
            'transaction_type' => 'transfer',
            'amount' => 1500,
            'fee' => 10,
            'status' => 'failed',
            'from_wallet_id' => $olderWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipient->phone_number,
            'description' => 'Recent failed transfer',
            'mmo_partner' => 'mock',
        ]);

        $this->setCreatedAt($todayTransfer, now());
        $this->setCreatedAt($todayDeposit, now());
        $this->setCreatedAt($yesterdayTransfer, now()->subDay());
        $this->setCreatedAt($recentFailed, now()->subHours(2));

        $service = app(AdminService::class);

        $kpis = $service->getOverviewKpis();
        $volumeChart = $service->getTransactionVolumeChart();
        $agentChart = $service->getAgentPerformanceChart();
        $alerts = $service->getRecentAlerts();
        $kycCounts = $service->getPendingKycCount();

        $this->assertSame(6, $kpis['total_users']['raw']);
        $this->assertSame('200%', $kpis['total_users']['trend']);
        $this->assertSame(3, $kpis['daily_transactions']['raw']);
        $this->assertSame('200%', $kpis['daily_transactions']['trend']);
        $this->assertSame('₦25.00', $kpis['revenue']['value']);
        $this->assertSame('150%', $kpis['revenue']['trend']);
        $this->assertSame(2, $kpis['active_agents']['raw']);
        $this->assertSame('100%', $kpis['active_agents']['trend']);

        $this->assertCount(7, $volumeChart['labels']);
        $this->assertCount(7, $volumeChart['data']);
        $this->assertSame(4, array_sum($volumeChart['data']));
        $this->assertSame(['Older Active Agent', 'Recent Active Agent'], $agentChart['labels']);
        $this->assertSame([2, 1], $agentChart['data']);

        $this->assertCount(3, $alerts);
        $this->assertSame('1 agent approval pending', $alerts[0]['title']);
        $this->assertSame('2 agents below float threshold', $alerts[1]['title']);
        $this->assertSame('1 failed transaction in the last 24 hours', $alerts[2]['title']);

        $this->assertSame([
            'pending' => 1,
            'verified' => 1,
            'rejected' => 1,
        ], $kycCounts);
    }

    public function test_pending_agent_can_be_approved_and_then_perform_cash_in(): void
    {
        $admin = $this->createUserWithRole('08050000001', 'Admin User', 'admin');
        $customer = $this->createCustomer('08050000002', 'Customer User', 10000, 2);
        $agent = $this->createAgent('08050000003', 'Pending Agent', 20000, 'pending');

        Livewire::actingAs($admin)
            ->test(AdminAgents::class)
            ->call('confirmAgentAction', $agent->id, 'approve')
            ->call('runAgentAction');

        $agent->refresh();
        $this->assertSame('active', $agent->status);
        $this->assertNotNull($agent->approved_at);

        Livewire::actingAs($agent->user)
            ->test(AgentCashIn::class)
            ->set('phone', $customer->phone_number)
            ->call('lookupCustomer')
            ->set('amount', '1000')
            ->call('continueToPin')
            ->set('agentPin', '123456')
            ->call('confirmDeposit')
            ->assertSet('resultState', 'success');

        $customerWallet = $customer->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $agent->refresh();

        $this->assertEquals(11000.0, (float) $customerWallet->available_balance);
        $this->assertEquals(21000.0, (float) $agent->float_balance);
    }

    public function test_kyc_approval_increments_level_and_caps_at_three(): void
    {
        $admin = $this->createUserWithRole('08050000004', 'Admin User', 'admin');
        $user = $this->createCustomer('08050000005', 'Kyc User', 10000, 2);
        $document = KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'National ID',
            'document_url' => '/storage/kyc/id.jpg',
            'verification_status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminKycQueue::class)
            ->call('confirmDocumentAction', $document->id, 'approve')
            ->call('runDocumentAction');

        $user->refresh();
        $document->refresh();
        $this->assertSame(3, (int) $user->kyc_level);
        $this->assertSame('verified', $document->verification_status);

        $secondDocument = KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'Drivers License',
            'document_url' => '/storage/kyc/license.jpg',
            'verification_status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(AdminKycQueue::class)
            ->call('confirmDocumentAction', $secondDocument->id, 'approve')
            ->call('runDocumentAction');

        $user->refresh();
        $this->assertSame(3, (int) $user->kyc_level);
    }

    public function test_transaction_reversal_restores_wallet_balances_and_creates_reversal_record(): void
    {
        $admin = $this->createUserWithRole('08050000006', 'Admin User', 'admin');
        $sender = $this->createCustomer('08050000007', 'Sender User', 39950, 2);
        $recipient = $this->createCustomer('08050000008', 'Recipient User', 60000, 2);
        $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail();
        $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail();

        $transaction = Transaction::create([
            'reference' => 'TXREV001',
            'transaction_type' => 'transfer',
            'amount' => 10000,
            'fee' => 50,
            'status' => 'completed',
            'from_wallet_id' => $senderWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipient->phone_number,
            'description' => 'Reversible transfer',
            'mmo_partner' => 'mock',
            'completed_at' => now(),
        ]);

        $reversal = app(TransactionService::class)->reverseTransaction($transaction, $admin, 'Customer reported mistaken transfer');

        $transaction->refresh();
        $senderWallet->refresh();
        $recipientWallet->refresh();

        $this->assertSame('reversed', $transaction->status);
        $this->assertSame('reversal', $reversal->transaction_type);
        $this->assertSame('completed', $reversal->status);
        $this->assertEquals(50000.0, (float) $senderWallet->available_balance);
        $this->assertEquals(50000.0, (float) $recipientWallet->available_balance);
        $this->assertSame($transaction->id, $reversal->metadata['reversed_transaction_id']);
    }

    public function test_transaction_reversal_is_blocked_for_reversed_or_failed_transactions(): void
    {
        $admin = $this->createUserWithRole('08050000009', 'Admin User', 'admin');
        $sender = $this->createCustomer('08050000010', 'Sender User', 39950, 2);
        $recipient = $this->createCustomer('08050000011', 'Recipient User', 60000, 2);
        $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail();
        $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail();

        $reversed = Transaction::create([
            'reference' => 'TXREV002',
            'transaction_type' => 'transfer',
            'amount' => 10000,
            'fee' => 50,
            'status' => 'reversed',
            'from_wallet_id' => $senderWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipient->phone_number,
            'description' => 'Already reversed',
            'mmo_partner' => 'mock',
            'completed_at' => now(),
        ]);

        $failed = Transaction::create([
            'reference' => 'TXREV003',
            'transaction_type' => 'transfer',
            'amount' => 10000,
            'fee' => 50,
            'status' => 'failed',
            'from_wallet_id' => $senderWallet->id,
            'to_wallet_id' => $recipientWallet->id,
            'recipient_phone' => $recipient->phone_number,
            'description' => 'Failed transfer',
            'mmo_partner' => 'mock',
            'completed_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This transaction has already been reversed.');

        try {
            app(TransactionService::class)->reverseTransaction($reversed, $admin, 'Duplicate attempt');
        } finally {
            $this->assertSame(2, Transaction::count());
        }

        try {
            app(TransactionService::class)->reverseTransaction($failed, $admin, 'Duplicate attempt');
            $this->fail('Failed transaction reversal should have been blocked.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Failed transactions cannot be reversed.', $exception->getMessage());
        }
    }

    public function test_non_admin_accounts_are_blocked_from_admin_routes(): void
    {
        $customer = $this->createUserWithRole('08050000012', 'Customer User', 'customer');
        $agentUser = $this->createUserWithRole('08050000013', 'Agent User', 'agent');
        $ajoOwner = $this->createUserWithRole('08050000014', 'Ajo Owner User', 'ajo_owner');

        foreach ([$customer, $agentUser, $ajoOwner] as $user) {
            $this->actingAs($user);

            foreach (['/admin/overview', '/admin/users', '/admin/agents', '/admin/transactions', '/admin/ajo-groups', '/admin/kyc-queue'] as $route) {
                $this->get($route)->assertForbidden();
            }
        }
    }

    protected function createCustomer(string $phone, string $name, float $balance, int $kycLevel): User
    {
        $user = $this->createUserWithRole($phone, $name, 'customer', $kycLevel);

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

    protected function createAgent(string $phone, string $name, float $floatBalance, string $status): Agent
    {
        $user = $this->createUserWithRole($phone, $name, 'agent', 2);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'agent',
            'balance' => $floatBalance,
            'available_balance' => $floatBalance,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'AGENT-' . Str::upper(Str::random(10)),
        ]);

        return Agent::create([
            'user_id' => $user->id,
            'business_name' => $name . ' Business',
            'business_address' => '12 Market Road',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'float_balance' => $floatBalance,
            'max_float' => 50000,
            'commission_rate' => 1.50,
            'total_earnings' => 0,
            'status' => $status,
            'approved_at' => $status === 'active' ? now() : null,
        ]);
    }

    protected function createUserWithRole(string $phone, string $name, string $role, int $kycLevel = 3): User
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => $kycLevel,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function setCreatedAt(object $model, $timestamp): void
    {
        $model->timestamps = false;
        $model->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->save();
        $model->timestamps = true;
    }
}
