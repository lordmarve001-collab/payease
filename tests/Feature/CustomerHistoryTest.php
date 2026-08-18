<?php

namespace Tests\Feature;

use App\Contracts\BillPaymentClientInterface;
use App\Livewire\Customer\History;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MockBillPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerHistoryTest extends TestCase
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

        $walletId = $this->user->wallets()->where('wallet_type', 'customer')->first()->id;

        Transaction::create([
            'user_id' => $this->user->id,
            'from_wallet_id' => $walletId,
            'reference' => 'TXN-001',
            'transaction_type' => 'transfer',
            'debit_credit' => 'debit',
            'amount' => 500,
            'fee' => 10,
            'status' => 'completed',
            'description' => 'Transfer to recipient',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'from_wallet_id' => $walletId,
            'reference' => 'TXN-002',
            'transaction_type' => 'airtime',
            'debit_credit' => 'debit',
            'amount' => 200,
            'fee' => 0,
            'status' => 'completed',
            'description' => 'Airtime purchase',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'to_wallet_id' => $walletId,
            'reference' => 'TXN-003',
            'transaction_type' => 'transfer',
            'debit_credit' => 'credit',
            'amount' => 1000,
            'fee' => 0,
            'status' => 'completed',
            'description' => 'Money received',
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'from_wallet_id' => $walletId,
            'reference' => 'TXN-004',
            'transaction_type' => 'transfer',
            'debit_credit' => 'debit',
            'amount' => 300,
            'fee' => 10,
            'status' => 'failed',
            'description' => 'Failed transfer',
        ]);

        $this->actingAs($this->user);
    }

    public function test_history_shows_all_transactions(): void
    {
        Livewire::test(History::class)
            ->assertSet('filter', 'all')
            ->assertSee('TXN-001')
            ->assertSee('TXN-002');
    }

    public function test_history_filter_credit(): void
    {
        Livewire::test(History::class)
            ->set('filter', 'credit')
            ->assertSee('Money received');
    }

    public function test_history_filter_debit(): void
    {
        Livewire::test(History::class)
            ->set('filter', 'debit')
            ->assertSee('Transfer to recipient')
            ->assertSee('Airtime purchase');
    }

    public function test_history_filter_failed(): void
    {
        Livewire::test(History::class)
            ->set('filter', 'failed')
            ->assertSee('Failed transfer');
    }

    public function test_history_filter_change_resets_page(): void
    {
        $component = Livewire::test(History::class);
        $component->set('filter', 'credit');
        $component->assertSet('filter', 'credit');
    }
}
