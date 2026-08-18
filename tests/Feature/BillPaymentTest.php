<?php

namespace Tests\Feature;

use App\Contracts\BillPaymentClientInterface;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BillPaymentService;
use App\Services\MockBillPaymentClient;
use App\Services\VTPassClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BillPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Wallet $wallet;
    protected BillPaymentClientInterface $mockClient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockBillPaymentClient();
        $this->app->instance(BillPaymentClientInterface::class, $this->mockClient);

        $this->user = User::create([
            'phone_number' => '2348012345678',
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'balance' => 10000,
            'available_balance' => 10000,
            'currency' => 'NGN',
            'status' => 'active',
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'wll-' . uniqid(),
        ]);

        $this->actingAs($this->user);
    }

    /* ---------- BillPaymentService ---------- */

    public function test_purchase_airtime_success(): void
    {
        $service = app(BillPaymentService::class);

        $result = $service->purchaseAirtime('08012345678', 'MTN', 500, 'web');

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('reference', $result);

        $this->wallet->refresh();
        $this->assertEquals(9500, (float) $this->wallet->balance);

        $this->assertDatabaseHas('transactions', [
            'reference' => $result['reference'],
            'transaction_type' => 'airtime',
            'amount' => 500,
            'channel' => 'web',
        ]);
    }

    public function test_purchase_airtime_insufficient_balance(): void
    {
        $this->wallet->update(['balance' => 100, 'available_balance' => 100]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient wallet balance.');

        $service = app(BillPaymentService::class);
        $service->purchaseAirtime('08012345678', 'MTN', 500, 'web');
    }

    public function test_purchase_data_success(): void
    {
        $service = app(BillPaymentService::class);

        $result = $service->purchaseData('08012345678', 'MTN', 'MTN500', 300, 'web');

        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('reference', $result);

        $this->wallet->refresh();
        $this->assertEquals(9700, (float) $this->wallet->balance);
    }

    public function test_purchase_cable_success(): void
    {
        $service = app(BillPaymentService::class);

        $result = $service->purchaseCable('1234567890', 'PREMIUM', 'DSTV', 2000, 'web');

        $this->assertEquals('success', $result['status']);
        $this->wallet->refresh();
        $this->assertEquals(8000, (float) $this->wallet->balance);
    }

    public function test_purchase_electricity_success(): void
    {
        $service = app(BillPaymentService::class);

        $result = $service->purchaseElectricity('METER123', 'IKEDC', 3000, 'web');

        $this->assertEquals('success', $result['status']);
        $this->wallet->refresh();
        $this->assertEquals(7000, (float) $this->wallet->balance);
    }

    public function test_get_data_bundles(): void
    {
        $service = app(BillPaymentService::class);

        $result = $service->getDataBundles('MTN');

        $this->assertArrayHasKey('bundles', $result);
    }

    public function test_ussd_channel_is_recorded(): void
    {
        $service = app(BillPaymentService::class);

        $result = $service->purchaseAirtime('08012345678', 'MTN', 200, 'ussd');

        $this->assertDatabaseHas('transactions', [
            'reference' => $result['reference'],
            'channel' => 'ussd',
        ]);
    }

    /* ---------- MockBillPaymentClient ---------- */

    public function test_mock_client_returns_success(): void
    {
        $client = new MockBillPaymentClient();

        $result = $client->purchaseAirtime('08012345678', 'MTN', 500, 'REF-001');
        $this->assertEquals('success', $result['status']);
        $this->assertEquals('REF-001', $result['transaction_id']);

        $this->assertTrue($client->testConnection()['status'] === 'success');
    }

    /* ---------- VTPassClient ---------- */

    public function test_vtpass_client_requires_configuration(): void
    {
        $this->expectException(\RuntimeException::class);

        $client = new VTPassClient('', '');
        $client->purchaseAirtime('08012345678', 'MTN', 100, 'REF-TEST');
    }

    public function test_vtpass_phone_normalization(): void
    {
        $client = new VTPassClient('key', 'user', 'sandbox');

        $ref = new \ReflectionClass($client);
        $method = $ref->getMethod('phone');

        $this->assertEquals('2348012345678', $method->invoke($client, '08012345678'));
        $this->assertEquals('2348012345678', $method->invoke($client, '8012345678'));
        $this->assertEquals('2348012345678', $method->invoke($client, '+2348012345678'));
    }

    public function test_vtpass_network_codes(): void
    {
        $client = new VTPassClient('key', 'user', 'sandbox');

        $ref = new \ReflectionClass($client);
        $method = $ref->getMethod('networkCode');

        $this->assertEquals('1', $method->invoke($client, 'MTN'));
        $this->assertEquals('2', $method->invoke($client, 'Airtel'));
        $this->assertEquals('3', $method->invoke($client, 'glo'));
        $this->assertEquals('4', $method->invoke($client, '9mobile'));
    }

    /* ---------- BillPaymentSettingsService ---------- */

    public function test_settings_service_get_and_save(): void
    {
        $settingsService = app(\App\Services\BillPaymentSettingsService::class);

        $settingsService->saveSettings([
            'vtpass_api_key' => 'test-key',
            'vtpass_username' => 'test-user',
            'vtpass_environment' => 'sandbox',
        ]);

        $settings = $settingsService->getSettings();
        $this->assertEquals('test-key', $settings['vtpass_api_key']);
        $this->assertEquals('sandbox', $settings['vtpass_environment']);
    }

    public function test_settings_service_make_client(): void
    {
        $settingsService = app(\App\Services\BillPaymentSettingsService::class);

        $settingsService->saveSettings([
            'vtpass_api_key' => 'key-123',
            'vtpass_username' => 'user-456',
            'vtpass_environment' => 'sandbox',
        ]);

        $client = $settingsService->makeClient();
        $this->assertInstanceOf(VTPassClient::class, $client);
    }
}
