<?php

namespace Tests\Feature;

use App\Livewire\Customer\SendMoney;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerSendMoneyPinTest extends TestCase
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

    public function test_correct_pin_completes_transfer_and_confirm_screen_highlights_recipient_name(): void
    {
        $sender = $this->createCustomer('08040000001', 'Sender Customer', 50000, 1, '123456');
        $recipient = $this->createCustomer('08040000002', 'Recipient Person', 20000, 1, '654321');

        $this->actingAs($sender);

        Livewire::test(SendMoney::class)
            ->set('phone', $recipient->phone_number)
            ->set('amount', '1000')
            ->call('continueToConfirm')
            ->assertSet('step', 2)
            ->assertSee($recipient->full_name)
            ->assertSee('Confirm this is the right person before sending')
            ->call('continueToPinStep')
            ->assertSet('step', 25)
            ->set('pin1', '1')
            ->set('pin2', '2')
            ->set('pin3', '3')
            ->set('pin4', '4')
            ->set('pin5', '5')
            ->set('pin6', '6')
            ->call('confirmTransferPin')
            ->assertSet('step', 3)
            ->assertSee('Transaction Successful!');

        $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();

        $this->assertEquals(48990.0, (float) $senderWallet->available_balance);
        $this->assertEquals(21000.0, (float) $recipientWallet->available_balance);
    }

    public function test_incorrect_pin_blocks_transfer_and_keeps_balance_unchanged(): void
    {
        $sender = $this->createCustomer('08040000003', 'Pin Sender', 50000, 1, '123456');
        $recipient = $this->createCustomer('08040000004', 'Pin Recipient', 20000, 1, '654321');

        $this->actingAs($sender);

        Livewire::test(SendMoney::class)
            ->set('phone', $recipient->phone_number)
            ->set('amount', '1000')
            ->call('continueToConfirm')
            ->call('continueToPinStep')
            ->set('pin1', '6')
            ->set('pin2', '5')
            ->set('pin3', '4')
            ->set('pin4', '3')
            ->set('pin5', '2')
            ->set('pin6', '1')
            ->call('confirmTransferPin')
            ->assertSet('step', 25)
            ->assertSee('Incorrect PIN. 2 attempt(s) remaining.');

        $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();

        $this->assertEquals(50000.0, (float) $senderWallet->available_balance);
        $this->assertEquals(20000.0, (float) $recipientWallet->available_balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_three_incorrect_pin_attempts_trigger_temporary_lockout(): void
    {
        $sender = $this->createCustomer('08040000005', 'Lock Sender', 50000, 1, '123456');
        $recipient = $this->createCustomer('08040000006', 'Lock Recipient', 20000, 1, '654321');

        $this->actingAs($sender);

        $test = Livewire::test(SendMoney::class)
            ->set('phone', $recipient->phone_number)
            ->set('amount', '1000')
            ->call('continueToConfirm')
            ->call('continueToPinStep');

        foreach (range(1, 3) as $attempt) {
            $test->set('pin1', '6')
                ->set('pin2', '5')
                ->set('pin3', '4')
                ->set('pin4', '3')
                ->set('pin5', '2')
                ->set('pin6', '1')
                ->call('confirmTransferPin');
        }

        $test->assertSet('step', 25)
            ->assertSee('Send Money is locked.');

        $senderWallet = $sender->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();
        $recipientWallet = $recipient->wallets()->where('wallet_type', 'customer')->firstOrFail()->fresh();

        $this->assertEquals(50000.0, (float) $senderWallet->available_balance);
        $this->assertEquals(20000.0, (float) $recipientWallet->available_balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    protected function createCustomer(string $phone, string $name, float $balance, int $kycLevel, string $pin): User
    {
        $user = User::create([
            'phone_number' => $phone,
            'full_name' => $name,
            'pin_hash' => Hash::make($pin, ['rounds' => 4]),
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
}
