<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.mock_mmo.latency_min_ms', 0);
        Config::set('services.mock_mmo.latency_max_ms', 0);
        Config::set('services.mock_mmo.failure_rate', 0);
        Config::set('services.mock_mmo.force_fail', null);
    }

    public function test_successful_transfer_updates_balances_and_fee_correctly(): void
    {
        $sender = $this->createCustomer('08030000001', 'Sender Customer', 50000, 1);
        $recipient = $this->createCustomer('08030000002', 'Recipient Customer', 20000, 1);

        $transaction = app(TransactionService::class)->initiateTransfer($sender, $recipient->phone_number, 10000);

        $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();

        $this->assertSame('completed', $transaction->status);
        $this->assertEquals(50.0, (float) $transaction->fee);
        $this->assertEquals(39950.0, (float) $senderWallet->available_balance);
        $this->assertEquals(30000.0, (float) $recipientWallet->available_balance);
    }

    public function test_transfer_is_rejected_when_balance_is_insufficient(): void
    {
        $sender = $this->createCustomer('08030000003', 'Low Balance Sender', 1000, 1);
        $recipient = $this->createCustomer('08030000004', 'Recipient Customer', 20000, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance for this transfer.');

        try {
            app(TransactionService::class)->initiateTransfer($sender, $recipient->phone_number, 5000);
        } finally {
            $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
            $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();

            $this->assertEquals(1000.0, (float) $senderWallet->available_balance);
            $this->assertEquals(20000.0, (float) $recipientWallet->available_balance);
            $this->assertDatabaseCount('transactions', 0);
        }
    }

    public function test_mmo_debit_failure_rolls_back_balances_and_marks_transaction_failed(): void
    {
        Config::set('services.mock_mmo.force_fail', 'debit');

        $sender = $this->createCustomer('08030000005', 'Debit Failure Sender', 50000, 1);
        $recipient = $this->createCustomer('08030000006', 'Debit Failure Recipient', 20000, 1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mock MMO debit request failed.');

        try {
            app(TransactionService::class)->initiateTransfer($sender, $recipient->phone_number, 10000);
        } finally {
            $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
            $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
            $failedTransaction = Transaction::query()->first();

            $this->assertEquals(50000.0, (float) $senderWallet->available_balance);
            $this->assertEquals(20000.0, (float) $recipientWallet->available_balance);
            $this->assertNotNull($failedTransaction);
            $this->assertSame('failed', $failedTransaction->status);
        }
    }

    public function test_process_cash_in_updates_customer_balance_agent_float_and_earnings(): void
    {
        $customer = $this->createCustomer('08030000007', 'Cash In Customer', 10000, 1);
        $agent = $this->createAgent('08030000008', 'Cash In Agent', 50000, 1.5);

        $transaction = app(TransactionService::class)->processCashIn($agent, $customer, 4000);

        $customerWallet = $customer->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $agent = $agent->fresh();

        $this->assertSame('completed', $transaction->status);
        $this->assertSame('deposit', $transaction->transaction_type);
        $this->assertEquals(60.0, (float) $transaction->commission);
        $this->assertEquals(14000.0, (float) $customerWallet->available_balance);
        $this->assertEquals(54000.0, (float) $agent->float_balance);
        $this->assertEquals(60.0, (float) $agent->total_earnings);
    }

    public function test_process_cash_out_updates_customer_balance_agent_float_and_earnings(): void
    {
        $customer = $this->createCustomer('08030000009', 'Cash Out Customer', 10000, 1);
        $agent = $this->createAgent('08030000010', 'Cash Out Agent', 50000, 1.5);

        $transaction = app(TransactionService::class)->processCashOut($agent, $customer, 4000);

        $customerWallet = $customer->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $agent = $agent->fresh();

        $this->assertSame('completed', $transaction->status);
        $this->assertSame('withdrawal', $transaction->transaction_type);
        $this->assertEquals(60.0, (float) $transaction->commission);
        $this->assertEquals(6000.0, (float) $customerWallet->available_balance);
        $this->assertEquals(46000.0, (float) $agent->float_balance);
        $this->assertEquals(60.0, (float) $agent->total_earnings);
    }

    public function test_process_cash_out_is_rejected_when_agent_float_is_insufficient(): void
    {
        $customer = $this->createCustomer('08030000011', 'Low Float Customer', 10000, 1);
        $agent = $this->createAgent('08030000012', 'Low Float Agent', 1000, 1.5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient float balance. Request a float top-up.');

        try {
            app(TransactionService::class)->processCashOut($agent, $customer, 4000);
        } finally {
            $customerWallet = $customer->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
            $agent = $agent->fresh();

            $this->assertEquals(10000.0, (float) $customerWallet->available_balance);
            $this->assertEquals(1000.0, (float) $agent->float_balance);
            $this->assertDatabaseCount('transactions', 0);
        }
    }

    public function test_cash_in_credit_failure_rolls_back_customer_balance_and_agent_float(): void
    {
        Config::set('services.mock_mmo.force_fail', 'credit');

        $customer = $this->createCustomer('08030000013', 'Credit Fail Customer', 10000, 1);
        $agent = $this->createAgent('08030000014', 'Credit Fail Agent', 50000, 1.5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mock MMO credit request failed.');

        try {
            app(TransactionService::class)->processCashIn($agent, $customer, 4000);
        } finally {
            $customerWallet = $customer->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
            $agent = $agent->fresh();
            $failedTransaction = Transaction::query()->first();

            $this->assertEquals(10000.0, (float) $customerWallet->available_balance);
            $this->assertEquals(50000.0, (float) $agent->float_balance);
            $this->assertEquals(0.0, (float) $agent->total_earnings);
            $this->assertNotNull($failedTransaction);
            $this->assertSame('failed', $failedTransaction->status);
        }
    }

    public function test_cash_out_debit_failure_rolls_back_customer_balance_and_agent_float(): void
    {
        Config::set('services.mock_mmo.force_fail', 'debit');

        $customer = $this->createCustomer('08030000015', 'Debit Fail Customer', 10000, 1);
        $agent = $this->createAgent('08030000016', 'Debit Fail Agent', 50000, 1.5);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Mock MMO debit request failed.');

        try {
            app(TransactionService::class)->processCashOut($agent, $customer, 4000);
        } finally {
            $customerWallet = $customer->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
            $agent = $agent->fresh();
            $failedTransaction = Transaction::query()->first();

            $this->assertEquals(10000.0, (float) $customerWallet->available_balance);
            $this->assertEquals(50000.0, (float) $agent->float_balance);
            $this->assertEquals(0.0, (float) $agent->total_earnings);
            $this->assertNotNull($failedTransaction);
            $this->assertSame('failed', $failedTransaction->status);
        }
    }

    protected function createCustomer(string $phone, string $name, float $balance, int $kycLevel): User
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => $kycLevel,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'customer',
            'balance' => $balance,
            'available_balance' => $balance,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'WALLET-' . Str::upper(Str::random(10)),
        ]);

        return $user;
    }

    protected function createAgent(string $phone, string $name, float $floatBalance, float $commissionRate): Agent
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make('123456', ['rounds' => 4]),
            'status' => 'active',
            'kyc_level' => 2,
        ]);

        Wallet::create([
            'user_id' => $user->id,
            'wallet_type' => 'agent',
            'balance' => $floatBalance,
            'available_balance' => $floatBalance,
            'currency' => 'NGN',
            'status' => 'active',
            'daily_limit' => 500000,
            'single_txn_limit' => 200000,
            'mmo_partner' => 'mock',
            'mmo_wallet_id' => 'AGENT-' . Str::upper(Str::random(10)),
        ]);

        return Agent::create([
            'user_id' => $user->id,
            'business_name' => $name . ' Business',
            'business_address' => '12 Market Road',
            'gps_latitude' => 6.5244,
            'gps_longitude' => 3.3792,
            'lga' => 'Ikeja',
            'state' => 'Lagos',
            'float_balance' => $floatBalance,
            'max_float' => 100000,
            'commission_rate' => $commissionRate,
            'total_earnings' => 0,
            'status' => 'active',
            'approved_at' => now(),
        ]);
    }
}
