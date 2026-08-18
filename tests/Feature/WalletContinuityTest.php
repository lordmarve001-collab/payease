<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Services\MonnifyWalletProvisioning;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class WalletContinuityTest extends TestCase
{
    use RefreshDatabase;

    public function test_tier_2_upgrade_updates_existing_wallet_without_creating_duplicate(): void
    {
        $user = User::create([
            'phone_number' => '2348012345001',
            'full_name' => 'Continuity User One',
            'kyc_level' => 1,
            'status' => 'active',
            'nin' => '12345678901',
        ]);

        $wallet = app(WalletService::class)->createTierWallet($user, tier: 1);

        $provisioning = app(MonnifyWalletProvisioning::class);
        $provisioned = $provisioning->provisionReservedAccount($user);

        $this->assertNotNull($provisioned);
        $this->assertSame($wallet->id, $provisioned->id);
        $this->assertSame(1, $user->wallets()->where('wallet_type', 'customer')->count());
        $this->assertTrue($provisioned->status === 'active' || $provisioned->status === 'provisioned');
        $this->assertNotNull($provisioned->account_number);
        $this->assertNotNull($provisioned->wallet_account_number);
    }

    public function test_tier_2_upgrade_preserves_balance_bit_for_bit(): void
    {
        $user = User::create([
            'phone_number' => '2348012345002',
            'full_name' => 'Continuity User Two',
            'kyc_level' => 1,
            'status' => 'active',
            'nin' => '12345678901',
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 12345.67,
            'available_balance' => 12345.67,
            'currency' => 'NGN',
            'status' => 'pending_kyc',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'PENDING-' . Str::upper(Str::random(10)),
        ]);

        $provisioning = app(MonnifyWalletProvisioning::class);
        $provisioned = $provisioning->provisionReservedAccount($user);

        $this->assertNotNull($provisioned);
        $this->assertSame($wallet->id, $provisioned->id);
        $this->assertEqualsWithDelta(12345.67, (float) $provisioned->fresh()->balance, 0.001);
        $this->assertEqualsWithDelta(12345.67, (float) $provisioned->fresh()->available_balance, 0.001);
    }

    public function test_provisioning_without_tier_1_wallet_throws_instead_of_creating_duplicate(): void
    {
        $user = User::create([
            'phone_number' => '2348012345003',
            'full_name' => 'Continuity User Three',
            'kyc_level' => 0,
            'status' => 'active',
            'nin' => '12345678901',
        ]);

        Log::swap(new \Illuminate\Log\LogManager($this->app));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No pending customer wallet found');

        app(MonnifyWalletProvisioning::class)->provisionReservedAccount($user);
    }

    public function test_create_tier_wallet_does_not_duplicate_existing_wallet(): void
    {
        $user = User::create([
            'phone_number' => '2348012345004',
            'full_name' => 'Continuity User Four',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $first = app(WalletService::class)->createTierWallet($user, tier: 1);
        $second = app(WalletService::class)->createTierWallet($user, tier: 2);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, $user->wallets()->where('wallet_type', 'customer')->count());
    }

    public function test_multiple_provisioning_calls_return_same_wallet(): void
    {
        $user = User::create([
            'phone_number' => '2348012345005',
            'full_name' => 'Continuity User Five',
            'kyc_level' => 1,
            'status' => 'active',
            'nin' => '12345678901',
        ]);

        app(WalletService::class)->createTierWallet($user, tier: 1);

        $provisioning = app(MonnifyWalletProvisioning::class);

        $first = $provisioning->provisionReservedAccount($user);
        $second = $provisioning->provisionReservedAccount($user);
        $third = $provisioning->provisionReservedAccount($user);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($second->id, $third->id);
        $this->assertSame(1, $user->wallets()->where('wallet_type', 'customer')->count());
    }
}
