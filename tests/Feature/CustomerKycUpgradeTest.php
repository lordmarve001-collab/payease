<?php

namespace Tests\Feature;

use App\Contracts\BillPaymentClientInterface;
use App\Livewire\Customer\KycUpgrade;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MockBillPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerKycUpgradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->app->instance(BillPaymentClientInterface::class, new MockBillPaymentClient());
    }

    public function test_kyc_upgrade_blocked_for_tier_0(): void
    {
        $user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Tier Zero User',
            'kyc_level' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(KycUpgrade::class)
            ->assertRedirect(route('customer.profile'));
    }

    public function test_kyc_upgrade_blocked_for_tier_2(): void
    {
        $user = User::create([
            'phone_number' => '2348012345679',
            'full_name' => 'Tier Two User',
            'kyc_level' => 2,
            'status' => 'active',
        ]);

        $this->actingAs($user);

        Livewire::test(KycUpgrade::class)
            ->assertSet('blockedReason', function ($reason) {
                return $reason !== null && str_contains($reason, 'already completed');
            });
    }

    public function test_kyc_upgrade_shows_form_for_tier_1(): void
    {
        $user = User::create([
            'phone_number' => '2348012345680',
            'full_name' => 'Tier One User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 1000,
            'available_balance' => 1000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($user);

        Livewire::test(KycUpgrade::class)
            ->assertSet('blockedReason', null)
            ->assertSet('flowStep', 'form');
    }

    public function test_kyc_upgrade_submit_rejects_invalid_nin(): void
    {
        $user = User::create([
            'phone_number' => '2348012345681',
            'full_name' => 'Submit User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 1000,
            'available_balance' => 1000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($user);

        Livewire::test(KycUpgrade::class)
            ->set('nin', '12345')
            ->set('bvn', '12345678901')
            ->set('nextOfKinName', 'Next of Kin')
            ->set('nextOfKinRelationship', 'Brother')
            ->set('nextOfKinPhone', '08012345678')
            ->set('ninDocument', UploadedFile::fake()->image('nin.jpg'))
            ->set('bvnDocument', UploadedFile::fake()->image('bvn.jpg'))
            ->set('consent', true)
            ->call('submit')
            ->assertHasErrors('nin');
    }

    public function test_kyc_upgrade_submit_rejects_missing_consent(): void
    {
        $user = User::create([
            'phone_number' => '2348012345682',
            'full_name' => 'No Consent User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 1000,
            'available_balance' => 1000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($user);

        Livewire::test(KycUpgrade::class)
            ->set('nin', '12345678901')
            ->set('bvn', '12345678901')
            ->set('nextOfKinName', 'Next of Kin')
            ->set('nextOfKinRelationship', 'Brother')
            ->set('nextOfKinPhone', '08012345678')
            ->set('ninDocument', UploadedFile::fake()->image('nin.jpg'))
            ->set('bvnDocument', UploadedFile::fake()->image('bvn.jpg'))
            ->set('consent', false)
            ->call('submit')
            ->assertHasErrors('consent');
    }

    public function test_kyc_upgrade_rejects_svg_document(): void
    {
        $user = User::create([
            'phone_number' => '2348012345683',
            'full_name' => 'Svg Upload User',
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 1000,
            'available_balance' => 1000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($user);

        Livewire::test(KycUpgrade::class)
            ->set('nin', '12345678901')
            ->set('bvn', '12345678901')
            ->set('nextOfKinName', 'Next of Kin')
            ->set('nextOfKinRelationship', 'Brother')
            ->set('nextOfKinPhone', '08012345678')
            ->set('ninDocument', UploadedFile::fake()->image('nin.svg'))
            ->set('bvnDocument', UploadedFile::fake()->image('bvn.jpg'))
            ->set('consent', true)
            ->call('submit')
            ->assertHasErrors('ninDocument');
    }
}
