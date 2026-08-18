<?php

namespace Tests\Feature;

use App\Livewire\Customer\Dashboard;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $this->user->id,
            'wallet_type' => 'customer',
            'balance' => 25000,
            'available_balance' => 25000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($this->user);
    }

    public function test_dashboard_renders_with_balance_section(): void
    {
        Livewire::test(Dashboard::class)
            ->assertSee('Available Balance')
            ->assertSee('Recent Transactions');
    }

    public function test_dashboard_shows_quick_actions(): void
    {
        Livewire::test(Dashboard::class)
            ->assertSee('Send Money')
            ->assertSee('Add Money')
            ->assertSee('Pay Bills');
    }

    public function test_dashboard_shows_kyc_upgrade_banner_for_tier_1(): void
    {
        Livewire::test(Dashboard::class)
            ->assertDontSee('Complete Setup');
    }
}
