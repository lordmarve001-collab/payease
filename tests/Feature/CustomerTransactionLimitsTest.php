<?php

namespace Tests\Feature;

use App\Livewire\Customer\TransactionLimits;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerTransactionLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 10000,
            'available_balance' => 10000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($user);
    }

    public function test_transaction_limits_renders_with_wallet_limits(): void
    {
        Livewire::test(TransactionLimits::class)
            ->assertSet('walletLimits', function ($limits) {
                return $limits !== null
                    && $limits['daily_limit'] === 500000.0
                    && $limits['single_txn_limit'] === 200000.0;
            });
    }

    public function test_transaction_limits_has_tiers_config(): void
    {
        Livewire::test(TransactionLimits::class)
            ->assertSet('limits', function ($limits) {
                return is_array($limits) && count($limits) > 0;
            });
    }

    public function test_transaction_limits_sets_current_level(): void
    {
        Livewire::test(TransactionLimits::class)
            ->assertSee('Tier 1');
    }
}
