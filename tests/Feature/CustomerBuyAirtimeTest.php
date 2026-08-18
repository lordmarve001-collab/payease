<?php

namespace Tests\Feature;

use App\Contracts\BillPaymentClientInterface;
use App\Livewire\Customer\BuyAirtime;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MockBillPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerBuyAirtimeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(BillPaymentClientInterface::class, new MockBillPaymentClient());

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $this->user->id,
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

        $this->actingAs($this->user);
    }

    public function test_select_network_proceeds_to_amount_step(): void
    {
        Livewire::test(BuyAirtime::class)
            ->assertSet('step', 'select')
            ->call('selectNetwork', 'MTN')
            ->assertSet('step', 'amount')
            ->assertSet('selectedNetwork', 'MTN');
    }

    public function test_amount_validation_rejects_below_minimum(): void
    {
        Livewire::test(BuyAirtime::class)
            ->call('selectNetwork', 'MTN')
            ->set('amount', '10')
            ->assertSee('Minimum airtime is');
    }

    public function test_go_to_confirm_requires_phone_number(): void
    {
        Livewire::test(BuyAirtime::class)
            ->call('selectNetwork', 'MTN')
            ->set('amount', '500')
            ->call('goToConfirm')
            ->assertSee('Enter your phone number');
    }

    public function test_purchase_airtime_success(): void
    {
        Livewire::test(BuyAirtime::class)
            ->call('selectNetwork', 'MTN')
            ->set('amount', '500')
            ->set('phoneNumber', '08012345678')
            ->call('goToConfirm')
            ->assertSet('step', 'confirm')
            ->call('purchase')
            ->assertSet('step', 'result')
            ->assertSet('resultState', 'success');
    }

    public function test_purchase_airtime_insufficient_balance(): void
    {
        $wallet = $this->user->wallets()->where('wallet_type', 'customer')->first();
        $wallet->update(['balance' => 50, 'available_balance' => 50]);

        Livewire::test(BuyAirtime::class)
            ->call('selectNetwork', 'MTN')
            ->set('amount', '500')
            ->set('phoneNumber', '08012345678')
            ->call('goToConfirm')
            ->call('purchase')
            ->assertSet('step', 'result')
            ->assertSet('resultState', 'failed');
    }

    public function test_go_back_from_amount_returns_to_select(): void
    {
        Livewire::test(BuyAirtime::class)
            ->call('selectNetwork', 'MTN')
            ->call('goBack')
            ->assertSet('step', 'select');
    }

    public function test_go_back_from_confirm_returns_to_amount(): void
    {
        Livewire::test(BuyAirtime::class)
            ->call('selectNetwork', 'MTN')
            ->set('amount', '500')
            ->set('phoneNumber', '08012345678')
            ->call('goToConfirm')
            ->call('goBack')
            ->assertSet('step', 'amount');
    }
}
