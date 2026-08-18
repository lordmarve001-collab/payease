<?php

namespace Tests\Feature;

use App\Livewire\Agent\AjoCollection;
use App\Models\Agent;
use App\Models\AjoOwner;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AjoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AjoLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['customer', 'agent', 'ajo_owner', 'admin', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_full_ajo_lifecycle_from_group_creation_to_payout_completion(): void
    {
        $owner = $this->createAjoOwner('08060000001', 'Ajo Owner');
        $agent = $this->createAgent('08060000002', 'Field Agent', 10000);
        $memberOne = $this->createCustomer('08060000003', 'Member One', 5000);
        $memberTwo = $this->createCustomer('08060000004', 'Member Two', 5000);
        $memberThree = $this->createCustomer('08060000005', 'Member Three', 5000);
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $group = $ajoService->createGroup($owner, [
            'name' => 'Market Women Daily',
            'contribution_amount' => 1000,
            'frequency' => 'daily',
            'members_count' => 3,
            'payout_order' => 'fixed',
        ], $agent);

        $memberRecordOne = $ajoService->addMember($group, $memberOne);
        $ajoService->addMember($group, $memberTwo);
        $ajoService->addMember($group, $memberThree);

        $group->refresh();
        $this->assertSame('active', $group->status);
        $this->assertNotNull($group->start_date);

        Livewire::actingAs($agent->user)
            ->test(AjoCollection::class)
            ->call('selectGroup', $group->id)
            ->call('selectMember', $memberRecordOne->id)
            ->set('agentPin', '123456')
            ->call('confirmContribution')
            ->assertSet('resultState', 'success');

        $progressAfterOne = $ajoService->getCycleProgress($group->fresh());
        $this->assertSame(1, $progressAfterOne['paid_members']);

        $ajoService->logContribution($agent, $group->fresh(), $memberTwo, 1000);
        $ajoService->logContribution($agent, $group->fresh(), $memberThree, 1000);

        $progress = $ajoService->getCycleProgress($group->fresh());
        $this->assertSame(100, $progress['percentage']);
        $this->assertTrue($progress['is_complete']);

        $recipientWalletBefore = $memberOne->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $recipientBalanceBefore = (float) $recipientWalletBefore->available_balance;

        $payoutTransaction = $ajoService->processPayout($group->fresh(), $memberOne, $agent->fresh(), $progress['cycle_number']);

        $this->assertSame('ajo_payout', $payoutTransaction->transaction_type);
        $this->assertTrue((bool) ($payoutTransaction->metadata['cash_handover'] ?? false));

        $agent->refresh();
        $recipientWalletAfter = $memberOne->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $this->assertEquals(10000.0, (float) $agent->float_balance);
        $this->assertEquals(30.0, (float) $agent->total_earnings);
        $this->assertEquals($recipientBalanceBefore, (float) $recipientWalletAfter->available_balance);
        $this->assertSame(2, $ajoService->getCurrentCycleNumber($group->fresh()));
    }

    public function test_log_contribution_rejects_duplicate_payment_attempt_for_same_cycle(): void
    {
        [$group, $agent, $member] = $this->createReadyGroup();
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $ajoService->logContribution($agent, $group, $member, 1000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This member has already paid for the current cycle.');

        $ajoService->logContribution($agent, $group->fresh(), $member, 1000);
    }

    public function test_process_payout_rejects_if_cycle_is_not_fully_collected(): void
    {
        [$group, $agent, $member, $otherMembers] = $this->createReadyGroup(3, 10000);
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $ajoService->logContribution($agent, $group, $member, 1000);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('This cycle is not fully collected yet.');

        $ajoService->processPayout($group->fresh(), $member, $agent->fresh(), 1);
    }

    public function test_process_payout_rejects_if_agent_float_is_insufficient(): void
    {
        [$group, $agent, $member, $otherMembers] = $this->createReadyGroup(3, 0);
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $ajoService->logContribution($agent, $group, $member, 1000);
        foreach ($otherMembers as $otherMember) {
            $ajoService->logContribution($agent, $group->fresh(), $otherMember, 1000);
        }

        $agent->update(['float_balance' => 2000]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The managing agent does not have enough float for this payout.');

        $ajoService->processPayout($group->fresh(), $member, $agent->fresh(), 1);
    }

    protected function createReadyGroup(int $memberCount = 3, float $startingFloat = 10000): array
    {
        $owner = $this->createAjoOwner('08061000001', 'Ajo Owner');
        $agent = $this->createAgent('08061000002', 'Field Agent', $startingFloat);
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);
        $group = $ajoService->createGroup($owner, [
            'name' => 'Ready Group',
            'contribution_amount' => 1000,
            'frequency' => 'weekly',
            'members_count' => $memberCount,
            'payout_order' => 'fixed',
        ], $agent);

        $members = [];
        for ($i = 0; $i < $memberCount; $i++) {
            $member = $this->createCustomer('0806100001' . $i, 'Member ' . ($i + 1), 5000);
            $members[] = $member;
            $ajoService->addMember($group, $member);
        }

        return [$group->fresh(), $agent->fresh(), $members[0], array_slice($members, 1)];
    }

    protected function createAjoOwner(string $phone, string $name): AjoOwner
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 3,
        ]);
        $user->assignRole('ajo_owner');

        return AjoOwner::create([
            'user_id' => $user->id,
            'business_name' => $name . ' Business',
            'status' => 'active',
        ])->fresh('user');
    }

    protected function createAgent(string $phone, string $name, float $floatBalance): Agent
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 2,
        ]);
        $user->assignRole('agent');

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
            'max_float' => 100000,
            'commission_rate' => 1.5,
            'total_earnings' => 0,
            'status' => 'active',
            'approved_at' => now(),
        ])->fresh('user');
    }

    protected function createCustomer(string $phone, string $name, float $balance): User
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 2,
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
}
