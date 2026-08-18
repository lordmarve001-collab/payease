<?php

namespace Tests\Feature;

use App\Contracts\MmoClientInterface;
use App\Contracts\SmsClientInterface;
use App\Livewire\Admin\Settings;
use App\Models\AuditLog;
use App\Models\MmoProviderSetting;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MonnifyClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'super_admin', 'customer'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }
    }

    public function test_super_admin_can_save_notification_settings(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        Livewire::actingAs($superAdmin)
            ->test(Settings::class)
            ->set('smsDriver', 'termii')
            ->set('termiiApiKey', 'termii-secret-key')
            ->set('termiiSenderId', 'PayEase')
            ->set('mailMailer', 'smtp')
            ->set('mailHost', 'smtp.mailtrap.io')
            ->set('mailPort', '587')
            ->set('mailScheme', 'tls')
            ->set('mailUsername', 'mailer-user')
            ->set('mailPassword', 'mailer-pass')
            ->set('mailFromAddress', 'alerts@payease.ng')
            ->set('mailFromName', 'PayEase Alerts')
            ->call('saveSettings');

        $setting = SystemSetting::query()->where('key', 'notification_channels')->first();

        $this->assertNotNull($setting);
        $this->assertSame('termii', $setting->payload['sms_driver']);
        $this->assertSame('smtp', $setting->payload['mail_mailer']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'notification_settings_updated',
            'entity_type' => 'system_setting',
        ]);
    }

    public function test_regular_admin_is_blocked_from_settings_page(): void
    {
        $admin = $this->createAdminUser('admin');

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_super_admin_can_send_test_sms_with_bound_client(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        $fakeClient = new class implements SmsClientInterface
        {
            /** @var array<int, array{phone_number:string,message:string}> */
            public array $messages = [];

            public function send(string $phoneNumber, string $message): array
            {
                $this->messages[] = [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                ];

                return [
                    'status' => 'sent',
                    'provider_id' => 'sms-test-1',
                ];
            }
        };

        $this->app->instance(SmsClientInterface::class, $fakeClient);

        Livewire::actingAs($superAdmin)
            ->test(Settings::class)
            ->set('smsDriver', 'log')
            ->set('testSmsPhone', '08012345678')
            ->call('sendTestSms');

        $this->assertCount(1, $fakeClient->messages);
        $this->assertSame('08012345678', $fakeClient->messages[0]['phone_number']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'notification_sms_test_sent',
        ]);
    }

    public function test_super_admin_can_send_test_email(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        Livewire::actingAs($superAdmin)
            ->test(Settings::class)
            ->set('mailMailer', 'log')
            ->set('testEmailAddress', 'ops@payease.ng')
            ->call('sendTestEmail');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'notification_email_test_sent',
        ]);
    }

    public function test_super_admin_can_save_mmo_provider_settings_with_masked_audit_values(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        Livewire::actingAs($superAdmin)
            ->test(Settings::class)
            ->set('mmoProviders.monnify.environment', 'sandbox')
            ->set('mmoProviders.monnify.credentials.api_key', 'MK_TEST_GC3B8XG2XX')
            ->set('mmoProviders.monnify.credentials.secret_key', 'A663NRZA544DDPEM7KDN7Z8HRV6YXD8S')
            ->set('mmoProviders.monnify.credentials.contract_code', '5867418298')
            ->set('mmoProviders.monnify.credentials.wallet_id', '6254727989')
            ->call('saveMmoProvider', 'monnify');

        $setting = MmoProviderSetting::query()->where('provider', 'monnify')->firstOrFail();

        $this->assertSame('sandbox', $setting->environment);
        $this->assertSame('MK_TEST_GC3B8XG2XX', $setting->credentials['api_key']);

        $audit = AuditLog::query()->where('action', 'mmo_provider_settings_updated')->latest()->first();

        $this->assertNotNull($audit);
        $this->assertStringContainsString('G2XX', (string) data_get($audit?->new_values, 'credentials.api_key'));
        $this->assertStringNotContainsString('A663NRZA544DDPEM7KDN7Z8HRV6YXD8S', json_encode($audit?->new_values));
    }

    public function test_super_admin_can_test_monnify_connection_and_activate_runtime_binding(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        Http::fake([
            'https://sandbox.monnify.com/api/v1/auth/login' => Http::response([
                'requestSuccessful' => true,
                'responseMessage' => 'success',
                'responseCode' => '0',
                'responseBody' => [
                    'accessToken' => 'token-1234567890',
                    'expiresIn' => 1800,
                ],
            ], 200),
            'https://sandbox.monnify.com/api/v2/bank-transfer/reserved-accounts' => Http::response([
                'requestSuccessful' => true,
                'responseMessage' => 'success',
                'responseCode' => '0',
                'responseBody' => [
                    'accountReference' => 'PAYEASE-CUST-123',
                    'reservationReference' => 'NWA7DMJ0W2UDK1KN5SLF',
                    'accountName' => 'Monnify Customer',
                    'status' => 'ACTIVE',
                    'accounts' => [[
                        'bankName' => 'Moniepoint Microfinance Bank',
                        'accountNumber' => '6254727989',
                        'accountName' => 'Monnify Customer',
                    ]],
                ],
            ], 200),
        ]);

        $customer = $this->createCustomerUser();

        Livewire::actingAs($superAdmin)
            ->test(Settings::class)
            ->set('mmoProviders.monnify.environment', 'sandbox')
            ->set('mmoProviders.monnify.credentials.api_key', 'MK_TEST_GC3B8XG2XX')
            ->set('mmoProviders.monnify.credentials.secret_key', 'A663NRZA544DDPEM7KDN7Z8HRV6YXD8S')
            ->set('mmoProviders.monnify.credentials.contract_code', '5867418298')
            ->set('mmoProviders.monnify.credentials.wallet_id', '6254727989')
            ->call('testMmoProvider', 'monnify')
            ->call('activateMmoProvider', 'monnify');

        $setting = MmoProviderSetting::query()->where('provider', 'monnify')->firstOrFail();
        $walletDetails = app(MmoClientInterface::class)->createWallet($customer);

        $this->assertSame('success', $setting->last_test_status);
        $this->assertTrue($setting->is_active);
        $this->assertInstanceOf(MonnifyClient::class, app(MmoClientInterface::class));
        $this->assertSame('6254727989', $walletDetails['account_number']);
        $this->assertSame('NWA7DMJ0W2UDK1KN5SLF', $walletDetails['mmo_wallet_id']);
    }

    public function test_backup_provider_test_connection_returns_not_configured(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');

        Livewire::actingAs($superAdmin)
            ->test(Settings::class)
            ->call('testMmoProvider', 'opay');

        $setting = MmoProviderSetting::query()->where('provider', 'opay')->firstOrFail();

        $this->assertSame('untested', $setting->last_test_status);
        $this->assertSame('Not yet configured.', $setting->last_test_message);
    }

    public function test_monnify_webhook_credits_wallet_and_creates_completed_transaction(): void
    {
        $superAdmin = $this->createAdminUser('super_admin');
        $customer = $this->createCustomerUser();
        $wallet = Wallet::create([
            'user_id' => $customer->id,
            'wallet_type' => 'customer',
            'balance' => 1000,
            'available_balance' => 1000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'NWA7DMJ0W2UDK1KN5SLF',
            'account_number' => '6254727989',
            'provider_reference' => 'PAYEASE-CUST-001',
        ]);

        Livewire::actingAs($superAdmin)
            ->test(Settings::class)
            ->set('mmoProviders.monnify.environment', 'sandbox')
            ->set('mmoProviders.monnify.credentials.api_key', 'MK_TEST_GC3B8XG2XX')
            ->set('mmoProviders.monnify.credentials.secret_key', 'A663NRZA544DDPEM7KDN7Z8HRV6YXD8S')
            ->set('mmoProviders.monnify.credentials.contract_code', '5867418298')
            ->set('mmoProviders.monnify.credentials.wallet_id', '6254727989')
            ->call('saveMmoProvider', 'monnify');

        $response = $this->postJson('/webhooks/monnify', [
            'eventType' => 'SUCCESSFUL_COLLECTION',
            'eventData' => [
                'transactionReference' => 'MNFY|76|20211117154810|000001',
                'paymentReference' => 'PAY-REF-001',
                'amountPaid' => 2500,
                'paymentStatus' => 'PAID',
                'paymentMethod' => 'ACCOUNT_TRANSFER',
                'destinationAccountInformation' => [
                    'accountNumber' => '6254727989',
                ],
                'product' => [
                    'reference' => 'PAYEASE-CUST-001',
                ],
                'customer' => [
                    'name' => 'Test Customer',
                ],
            ],
        ]);

        $response->assertOk();
        $wallet->refresh();

        $this->assertSame('3500.00', (string) $wallet->available_balance);
        $this->assertDatabaseHas('transactions', [
            'transaction_type' => 'bank_transfer_deposit',
            'mmo_transaction_id' => 'MNFY|76|20211117154810|000001',
            'to_wallet_id' => $wallet->id,
            'status' => 'completed',
        ]);
    }

    protected function createAdminUser(string $role): User
    {
        $user = User::create([
            'phone_number' => $role === 'super_admin' ? '08012345000' : '08012345005',
            'full_name' => $role === 'super_admin' ? 'Super Admin User' : 'Admin User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 3,
        ]);

        $user->assignRole($role);

        return $user;
    }

    protected function createCustomerUser(): User
    {
        $user = User::create([
            'phone_number' => '08012345111',
            'full_name' => 'Monnify Customer',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 2,
            'bvn' => '21212121212',
        ]);

        $user->assignRole('customer');

        return $user;
    }
}
