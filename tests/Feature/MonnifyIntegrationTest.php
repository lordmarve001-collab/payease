<?php

namespace Tests\Feature;

use App\Contracts\MmoClientInterface;
use App\Events\TransactionCompleted;
use App\Models\AuditLog;
use App\Models\MmoProviderSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\MockMmoClient;
use App\Services\MonnifyWalletProvisioning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonnifyIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['customer', 'agent', 'ajo_owner', 'admin', 'super_admin'] as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        config(['services.platform_liquidity.master_balance' => 10000000]);
    }

    public function test_valid_monnify_webhook_credits_wallet_and_creates_transaction(): void
    {
        $customer = $this->createCustomerWithMonnifyWallet('08030001001', 'Webhook Customer', 5000);

        $response = $this->postJson('/webhooks/monnify', [
            'eventType' => 'SUCCESSFUL_COLLECTION',
            'eventData' => [
                'transactionReference' => 'MNFY|WEBHOOK|001|20260716',
                'paymentReference' => 'PAY-REF-WEBHOOK-001',
                'amountPaid' => 15000,
                'paymentStatus' => 'PAID',
                'paymentMethod' => 'ACCOUNT_TRANSFER',
                'destinationAccountInformation' => [
                    'accountNumber' => $customer->wallets()->first()->account_number,
                ],
                'product' => [
                    'reference' => $customer->wallets()->first()->provider_reference,
                ],
                'customer' => [
                    'name' => 'Webhook Customer',
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('transactions', [
            'transaction_type' => 'bank_transfer_deposit',
            'mmo_transaction_id' => 'MNFY|WEBHOOK|001|20260716',
            'status' => 'completed',
            'amount' => 15000,
        ]);

        $wallet = $customer->wallets()->first()->fresh();
        $this->assertEquals(20000.0, (float) $wallet->available_balance);
    }

    public function test_duplicate_webhook_reference_is_not_double_credited(): void
    {
        $customer = $this->createCustomerWithMonnifyWallet('08030001002', 'Dup Customer', 5000);
        $wallet = $customer->wallets()->first();
        $paymentRef = 'MNFY|DUP|001|20260716';

        $payload = [
            'eventType' => 'SUCCESSFUL_COLLECTION',
            'eventData' => [
                'transactionReference' => $paymentRef,
                'paymentReference' => 'PAY-REF-DUP-001',
                'amountPaid' => 10000,
                'paymentStatus' => 'PAID',
                'paymentMethod' => 'ACCOUNT_TRANSFER',
                'destinationAccountInformation' => [
                    'accountNumber' => $wallet->account_number,
                ],
                'product' => [
                    'reference' => $wallet->provider_reference,
                ],
                'customer' => [
                    'name' => 'Dup Customer',
                ],
            ],
        ];

        $response1 = $this->postJson('/webhooks/monnify', $payload);
        $response1->assertOk();

        $response2 = $this->postJson('/webhooks/monnify', $payload);
        $response2->assertOk();

        $this->assertDatabaseCount('transactions', 1);
        $wallet->refresh();
        $this->assertEquals(15000.0, (float) $wallet->available_balance);
    }

    public function test_webhook_with_unknown_account_logs_and_returns_202(): void
    {
        $response = $this->postJson('/webhooks/monnify', [
            'eventType' => 'SUCCESSFUL_COLLECTION',
            'eventData' => [
                'transactionReference' => 'MNFY|UNKNOWN|001|20260716',
                'paymentReference' => 'PAY-UNKNOWN-001',
                'amountPaid' => 5000,
                'paymentStatus' => 'PAID',
                'paymentMethod' => 'ACCOUNT_TRANSFER',
                'destinationAccountInformation' => [
                    'accountNumber' => '9999999999',
                ],
                'product' => [
                    'reference' => 'UNKNOWN-REF',
                ],
                'customer' => [
                    'name' => 'Unknown',
                ],
            ],
        ]);

        $response->assertStatus(202);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'monnify_webhook_unmatched_account',
        ]);
    }

    public function test_tier_zero_user_defers_monnify_wallet_creation(): void
    {
        $user = User::create([
            'phone_number' => '08030001003',
            'full_name' => 'Tier Zero User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 0,
        ]);

        $user->assignRole('customer');

        $provisioning = new MonnifyWalletProvisioning(new MockMmoClient());
        $wallet = $provisioning->provisionReservedAccount($user);

        $this->assertNotNull($wallet);
        $this->assertSame('pending_kyc', $wallet->status);
        $this->assertNull($wallet->account_number);
        $this->assertNull($wallet->mmo_wallet_id);
    }

    public function test_tier_one_user_gets_monnify_wallet_provisioned(): void
    {
        $user = User::create([
            'phone_number' => '08030001004',
            'full_name' => 'Tier One User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 1,
            'bvn' => '22233344455',
        ]);

        $user->assignRole('customer');

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 0,
            'available_balance' => 0,
            'currency' => 'NGN',
            'status' => 'pending_kyc',
            'daily_limit' => 5000,
            'single_txn_limit' => 2000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'PENDING-' . Str::upper(Str::random(10)),
        ]);

        $provisioning = new MonnifyWalletProvisioning(new MockMmoClient());
        $wallet = $provisioning->provisionReservedAccount($user);

        $this->assertNotNull($wallet);
        $this->assertSame('active', $wallet->status);
        $this->assertNotNull($wallet->account_number);
        $this->assertNotNull($wallet->mmo_wallet_id);
        $this->assertSame('monnify', $wallet->mmo_partner);
    }

    public function test_monnify_provisioning_stores_wallet_account_number(): void
    {
        $user = User::create([
            'phone_number' => '08030001005',
            'full_name' => 'Account Number User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 1,
            'nin' => '33344455566',
        ]);

        $user->assignRole('customer');

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 0,
            'available_balance' => 0,
            'currency' => 'NGN',
            'status' => 'pending_kyc',
            'daily_limit' => 5000,
            'single_txn_limit' => 2000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'PENDING-' . Str::upper(Str::random(10)),
        ]);

        $provisioning = new MonnifyWalletProvisioning(new MockMmoClient());
        $wallet = $provisioning->provisionReservedAccount($user);

        $this->assertNotNull($wallet->wallet_account_number);
        $this->assertNotEmpty($wallet->wallet_account_number);
    }

    public function test_disbursement_creates_pending_otp_transaction(): void
    {
        $customer = $this->createCustomerWithMonnifyWallet('08030001006', 'Disburse Customer', 100000, 2);

        $otpMock = $this->createOtpRequiredMock();
        $this->app->instance(MmoClientInterface::class, $otpMock);

        Event::fake([TransactionCompleted::class]);

        $transactionService = app(\App\Services\TransactionService::class);
        $transaction = $transactionService->initiateBankTransferDisbursement(
            $customer,
            '044',
            '1234567890',
            'Recipient Person',
            25000,
            'Test bank transfer'
        );

        $this->assertNotNull($transaction);
        $this->assertSame('pending_disbursement_otp', $transaction->status);
        $this->assertSame('bank_transfer_out', $transaction->transaction_type);
        $this->assertEquals(25000.0, (float) $transaction->amount);
        $this->assertEquals(100.0, (float) $transaction->fee);

        $wallet = $customer->wallets()->where('wallet_type', 'customer')->first()->fresh();
        $expectedBalance = 100000 - 25000 - 100;
        $this->assertEquals($expectedBalance, (float) $wallet->available_balance);

        Event::assertNotDispatched(TransactionCompleted::class);
    }

    public function test_disbursement_completes_after_otp_validation(): void
    {
        $customer = $this->createCustomerWithMonnifyWallet('08030001007', 'OTP Customer', 100000, 2);

        $otpMock = $this->createOtpRequiredMock();
        $this->app->instance(MmoClientInterface::class, $otpMock);

        $transactionService = app(\App\Services\TransactionService::class);
        $transaction = $transactionService->initiateBankTransferDisbursement(
            $customer,
            '044',
            '1234567891',
            'OTP Recipient',
            30000,
        );

        $this->assertSame('pending_disbursement_otp', $transaction->status);

        Event::fake([TransactionCompleted::class]);

        $completedTransaction = $transactionService->completeDisbursementOtp($transaction->id, '1234');

        $this->assertSame('completed', $completedTransaction->status);
        $this->assertTrue($completedTransaction->metadata['otp_validated'] ?? false);
    }

    public function test_cannot_validate_otp_for_non_pending_transaction(): void
    {
        $customer = $this->createCustomerWithMonnifyWallet('08030001008', 'Already Done', 100000, 2);

        $transactionService = app(\App\Services\TransactionService::class);

        $transaction = Transaction::create([
            'reference' => 'DISB' . Str::upper(Str::random(10)),
            'transaction_type' => 'bank_transfer_out',
            'amount' => 10000,
            'status' => 'completed',
            'from_wallet_id' => $customer->wallets()->first()->id,
            'recipient_phone' => $customer->phone_number,
            'description' => 'Already completed',
            'mmo_partner' => 'monnify',
            'metadata' => [],
            'completed_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not pending OTP');

        $transactionService->completeDisbursementOtp($transaction->id, '1234');
    }

    public function test_disbursement_insufficient_balance_rolls_back(): void
    {
        $customer = $this->createCustomerWithMonnifyWallet('08030001009', 'Low Bal', 1000, 2);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $transactionService = app(\App\Services\TransactionService::class);
        $transactionService->initiateBankTransferDisbursement(
            $customer,
            '044',
            '1234567892',
            'Should Fail',
            50000,
        );

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_disbursement_otp_queue_shows_pending_transactions(): void
    {
        $admin = User::create([
            'phone_number' => '08030001010',
            'full_name' => 'Disbursement Admin',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 3,
        ]);
        $admin->assignRole('admin');

        $customer = $this->createCustomerWithMonnifyWallet('08030001011', 'Queue Customer', 100000, 2);

        $transaction = Transaction::create([
            'reference' => 'DISB' . Str::upper(Str::random(10)),
            'transaction_type' => 'bank_transfer_out',
            'amount' => 20000,
            'fee' => 100,
            'status' => 'pending_disbursement_otp',
            'from_wallet_id' => $customer->wallets()->first()->id,
            'recipient_phone' => $customer->phone_number,
            'description' => 'Pending disbursement',
            'mmo_partner' => 'monnify',
            'metadata' => [
                'destination_bank_code' => '044',
                'destination_account_number' => '9876543210',
                'destination_account_name' => 'Queue Recipient',
            ],
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Disbursements::class)
            ->assertSee('9876543210')
            ->assertSee('Queue Recipient')
            ->assertSee('20,000.00');
    }

    public function test_balance_card_shows_pending_state_for_unprovisioned_wallet(): void
    {
        $user = User::create([
            'phone_number' => '08030001020',
            'full_name' => 'Pending Wallet User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 0,
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 0,
            'available_balance' => 0,
            'currency' => 'NGN',
            'status' => 'pending_kyc',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
        ]);

        $walletService = app(\App\Services\WalletService::class);
        $display = $walletService->getAccountDisplay($wallet);

        $this->assertTrue($display['is_pending']);
        $this->assertFalse($display['is_copyable']);
        $this->assertSame('Account Number Pending', $display['headline']);
        $this->assertNotNull($display['message']);
    }

    public function test_balance_card_shows_real_account_for_provisioned_wallet(): void
    {
        $user = User::create([
            'phone_number' => '08030001021',
            'full_name' => 'Provisioned Wallet User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 1,
        ]);

        $wallet = Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 10000,
            'available_balance' => 10000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'REF-123',
            'account_number' => '6254727989',
            'wallet_account_number' => '6254727989',
        ]);

        $walletService = app(\App\Services\WalletService::class);
        $display = $walletService->getAccountDisplay($wallet);

        $this->assertFalse($display['is_pending']);
        $this->assertTrue($display['is_copyable']);
        $this->assertSame('6254727989', $display['account_number']);
        $this->assertSame('Account Number', $display['headline']);
    }

    public function test_reconcile_command_finds_no_mismatches_when_balances_match(): void
    {
        $user = User::create([
            'phone_number' => '08030001012',
            'full_name' => 'Reconcile User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 1,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 5000,
            'available_balance' => 5000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'MOCK-WALLET-001',
        ]);

        $this->fakeMonnifyForReconcile();

        $exitCode = \Illuminate\Support\Facades\Artisan::call('payease:reconcile-monnify-balances');
        $this->assertSame(0, $exitCode, 'Command output: ' . \Illuminate\Support\Facades\Artisan::output());
    }

    public function test_reconcile_command_flags_mismatch_when_balances_differ(): void
    {
        $user = User::create([
            'phone_number' => '08030001013',
            'full_name' => 'Mismatch User',
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 1,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => 5000,
            'available_balance' => 5000,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'MOCK-WALLET-002',
        ]);

        $this->fakeMonnifyForReconcile(monnifyBalance: 10000.0);

        $exitCode = \Illuminate\Support\Facades\Artisan::call('payease:reconcile-monnify-balances');
        $this->assertSame(0, $exitCode, 'Command output: ' . \Illuminate\Support\Facades\Artisan::output());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'balance_mismatch',
            'entity_type' => 'wallet',
        ]);
    }

    protected function createOtpRequiredMock(): MmoClientInterface
    {
        return new class extends MockMmoClient
        {
            public function initiateDisbursement(
                string $destinationBankCode,
                string $destinationAccountNumber,
                string $destinationAccountName,
                float $amount,
                string $reference,
                string $narration = '',
            ): array {
                return [
                    'status' => 'pending',
                    'transaction_reference' => $reference,
                    'external_reference' => 'MOCK-DISB-' . strtoupper(substr(md5($reference), 0, 10)),
                    'requires_otp' => true,
                    'otp_reference' => 'OTP-REF-' . strtoupper(substr(md5($reference), 0, 8)),
                ];
            }
        };
    }

    protected function fakeMonnifyForReconcile(float $monnifyBalance = 5000.0): void
    {
        // Ensure the monnify provider setting exists with real credentials
        MmoProviderSetting::query()->updateOrCreate(
            ['provider' => 'monnify'],
            [
                'is_active' => true,
                'environment' => 'sandbox',
                'credentials' => [
                    'api_key' => 'test-api-key',
                    'secret_key' => 'test-secret-key',
                    'contract_code' => '123456',
                ],
                'last_test_status' => 'success',
            ],
        );

        Http::fake([
            'sandbox.monnify.com/*' => function ($request) use ($monnifyBalance) {
                if (str_contains($request->url(), '/auth/login')) {
                    return Http::response([
                        'requestSuccessful' => true,
                        'responseBody' => ['accessToken' => 'fake-access-token', 'expiresIn' => 3600],
                    ]);
                }

                return Http::response([
                    'requestSuccessful' => true,
                    'responseBody' => ['availableBalance' => $monnifyBalance],
                ]);
            },
        ]);
    }

    protected function createCustomerWithMonnifyWallet(string $phone, string $name, float $balance, int $kycLevel = 1): User
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => $kycLevel,
            'bvn' => '22233344455',
        ]);

        $user->assignRole('customer');

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => $balance,
            'available_balance' => $balance,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'monnify',
            'mmo_wallet_id' => 'MONNIFY-' . strtoupper(Str::random(8)),
            'account_number' => '6' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'wallet_account_number' => '6' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'provider_reference' => 'PAYEASE-' . strtoupper(Str::random(8)),
        ]);

        return $user;
    }
}
