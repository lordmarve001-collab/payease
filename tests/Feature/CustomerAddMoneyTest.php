<?php

namespace Tests\Feature;

use App\Livewire\Customer\AddMoney;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerAddMoneyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $this->user->id,
            'wallet_type' => 'customer',
            'balance' => 5000,
            'available_balance' => 5000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($this->user);
    }

    public function test_add_money_renders(): void
    {
        Livewire::test(AddMoney::class)
            ->assertSee('Add Money');
    }

    public function test_add_money_shows_agent_and_card_sections(): void
    {
        Livewire::test(AddMoney::class)
            ->assertSee('Visit an Agent')
            ->assertSee('Pay with Card')
            ->assertSee('Fund with Card');
    }
}
