<?php

namespace Tests\Feature;

use App\Contracts\BillPaymentClientInterface;
use App\Livewire\Customer\KycAddress;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MockBillPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerKycAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->app->instance(BillPaymentClientInterface::class, new MockBillPaymentClient());
    }

    public function test_kyc_address_renders(): void
    {
        $user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Address User',
            'kyc_level' => 2,
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

        Livewire::test(KycAddress::class)
            ->assertSee('Address');
    }

    public function test_kyc_address_submit_requires_document(): void
    {
        $user = User::create([
            'phone_number' => '2348012345679',
            'full_name' => 'No Doc User',
            'kyc_level' => 2,
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

        Livewire::test(KycAddress::class)
            ->call('submit')
            ->assertHasErrors('addressDocument');
    }

    public function test_kyc_address_submit_creates_document(): void
    {
        $user = User::create([
            'phone_number' => '2348012345680',
            'full_name' => 'Submit Doc User',
            'kyc_level' => 2,
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

        Livewire::test(KycAddress::class)
            ->set('addressDocument', UploadedFile::fake()->image('address.jpg'))
            ->call('submit')
            ->assertRedirect(route('customer.dashboard'));

        $this->assertDatabaseHas('kyc_documents', [
            'user_id' => $user->id,
            'document_type' => 'proof_of_address',
            'verification_status' => 'pending',
        ]);
    }

    public function test_kyc_address_rejects_svg_document(): void
    {
        $user = User::create([
            'phone_number' => '2348012345684',
            'full_name' => 'Svg Address User',
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

        Livewire::test(KycAddress::class)
            ->set('addressDocument', UploadedFile::fake()->image('address.svg'))
            ->call('submit')
            ->assertHasErrors('addressDocument');
    }
}
