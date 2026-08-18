<?php

namespace Tests\Feature;

use App\Contracts\BillPaymentClientInterface;
use App\Livewire\Customer\PayBills;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MockBillPaymentClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerPayBillsTest extends TestCase
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
            'balance' => 50000,
            'available_balance' => 50000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        $this->actingAs($this->user);
    }

    public function test_select_category_proceeds_to_details(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'data')
            ->assertSet('category', 'data')
            ->assertSet('step', 'details');
    }

    public function test_data_purchase_flow(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'data')
            ->call('selectDataNetwork', 'MTN')
            ->assertSet('dataNetwork', 'MTN')
            ->call('selectDataBundle', 'MTN500', '500MB', 500)
            ->assertSet('dataBundleCode', 'MTN500')
            ->assertSet('dataBundleName', '500MB')
            ->assertSet('dataPrice', 500.0)
            ->set('dataPhone', '08012345678')
            ->call('goToConfirm')
            ->assertSet('step', 'confirm')
            ->call('purchase')
            ->assertSet('step', 'result')
            ->assertSet('resultState', 'success');
    }

    public function test_cable_purchase_flow(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'cable')
            ->call('selectCableProvider', 'DSTV')
            ->assertSet('cableProvider', 'DSTV')
            ->set('cableSmartCard', '1234567890')
            ->call('selectCablePackage', 'DSTV-PREMIUM', 'Premium', 24500)
            ->assertSet('cablePackageCode', 'DSTV-PREMIUM')
            ->call('goToConfirm')
            ->assertSet('step', 'confirm')
            ->call('purchase')
            ->assertSet('step', 'result')
            ->assertSet('resultState', 'success');
    }

    public function test_electricity_purchase_flow(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'electricity')
            ->call('setElectricMeterType', 'prepaid')
            ->assertSet('electricMeterType', 'prepaid')
            ->set('electricDisco', 'IKEDC')
            ->set('electricMeterNumber', 'MTR12345')
            ->set('electricAmount', '3000')
            ->call('goToConfirm')
            ->assertSet('step', 'confirm')
            ->call('purchase')
            ->assertSet('step', 'result')
            ->assertSet('resultState', 'success');
    }

    public function test_education_purchase_flow(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'education')
            ->set('educationExamType', 'WAEC')
            ->set('educationStudentId', 'REG12345')
            ->set('educationAmount', '5000')
            ->call('goToConfirm')
            ->assertSet('step', 'confirm')
            ->call('purchase')
            ->assertSet('step', 'result')
            ->assertSet('resultState', 'success');
    }

    public function test_validation_prevents_empty_data_phone(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'data')
            ->set('dataNetwork', 'MTN')
            ->call('selectDataBundle', 'MTN500', '500MB', 500)
            ->call('goToConfirm')
            ->assertSee('Enter a valid phone number');
    }

    public function test_validation_prevents_empty_cable_smart_card(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'cable')
            ->call('selectCableProvider', 'DSTV')
            ->call('goToConfirm')
            ->assertSee('Enter your smart card number');
    }

    public function test_validation_prevents_empty_electricity_disco(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'electricity')
            ->set('electricMeterNumber', 'MTR123')
            ->set('electricAmount', '1000')
            ->call('goToConfirm')
            ->assertSee('Select your electricity distributor');
    }

    public function test_validation_prevents_empty_education_exam_type(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'education')
            ->set('educationStudentId', 'REG123')
            ->set('educationAmount', '2000')
            ->call('goToConfirm')
            ->assertSee('Select an exam type');
    }

    public function test_validation_prevents_empty_education_student_id(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'education')
            ->set('educationExamType', 'JAMB')
            ->set('educationAmount', '2000')
            ->call('goToConfirm')
            ->assertSee('Enter your student ID or registration number');
    }

    public function test_go_back_from_details_returns_to_select(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'data')
            ->call('goBack')
            ->assertSet('step', 'select')
            ->assertSet('category', '');
    }

    public function test_go_back_from_confirm_returns_to_details(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'data')
            ->call('selectDataNetwork', 'MTN')
            ->call('selectDataBundle', 'MTN500', '500MB', 500)
            ->set('dataPhone', '08012345678')
            ->call('goToConfirm')
            ->call('goBack')
            ->assertSet('step', 'details');
    }

    public function test_data_network_selection_clears_bundle(): void
    {
        Livewire::test(PayBills::class)
            ->call('selectCategory', 'data')
            ->call('selectDataNetwork', 'MTN')
            ->call('selectDataBundle', 'MTN500', '500MB', 500)
            ->assertSet('dataBundleCode', 'MTN500')
            ->call('selectDataNetwork', 'Airtel')
            ->assertSet('dataBundleCode', '')
            ->assertSet('dataPrice', 0.0);
    }
}
