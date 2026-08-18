<?php

namespace Tests\Feature;

use App\Helpers\PhoneNumberHelper;
use App\Models\AjoGroup;
use App\Models\AjoMember;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UssdMenuTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $recipient;
    protected Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'full_name' => 'John Doe',
            'phone_number' => '8031234567',
            'pin_hash' => Hash::make('123456'),
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'wallet_type' => 'customer',
            'balance' => 10000.00,
            'available_balance' => 10000.00,
            'currency' => 'NGN',
            'status' => 'active',
            'mmo_partner' => 'monnify',
        ]);

        $this->recipient = User::create([
            'full_name' => 'Jane Smith',
            'phone_number' => '8037654321',
            'pin_hash' => Hash::make('654321'),
            'kyc_level' => 1,
            'status' => 'active',
        ]);

        Wallet::create([
            'user_id' => $this->recipient->id,
            'wallet_type' => 'customer',
            'balance' => 5000.00,
            'available_balance' => 5000.00,
            'currency' => 'NGN',
            'status' => 'active',
            'mmo_partner' => 'monnify',
        ]);
    }

    protected function ussd(string $phoneNumber, string $text = ''): string
    {
        $response = $this->post('/ussd/callback', [
            'sessionId' => 'test-session-' . uniqid(),
            'serviceCode' => '*347#',
            'phoneNumber' => $phoneNumber,
            'text' => $text,
        ]);

        $response->assertStatus(200);
        return trim($response->content());
    }

    #[Test]
    public function unregistered_number_gets_register_message(): void
    {
        $response = $this->ussd('+2348111111111');
        $this->assertStringStartsWith('END ', $response);
        $this->assertStringContainsString('not registered', $response);
    }

    #[Test]
    public function main_menu_shows_options(): void
    {
        $response = $this->ussd('+2348031234567');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Welcome to PayEase', $response);
        $this->assertStringContainsString('Check Balance', $response);
        $this->assertStringContainsString('Send Money', $response);
        $this->assertStringContainsString('Buy Airtime', $response);
        $this->assertStringContainsString('My Ajo Status', $response);
        $this->assertStringContainsString('Change PIN', $response);
    }

    #[Test]
    public function check_balance_asks_for_pin(): void
    {
        $response = $this->ussd('+2348031234567', '1');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Enter your PIN', $response);
    }

    #[Test]
    public function check_balance_with_correct_pin_shows_balance(): void
    {
        $response = $this->ussd('+2348031234567', '1*123456');
        $this->assertStringStartsWith('END ', $response);
        $this->assertStringContainsString('₦10,000.00', $response);
    }

    #[Test]
    public function check_balance_with_wrong_pin_shows_remaining_attempts(): void
    {
        $response = $this->ussd('+2348031234567', '1*000000');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Incorrect PIN', $response);
        $this->assertStringContainsString('2 attempt(s) remaining', $response);
    }

    #[Test]
    public function check_balance_locks_after_three_wrong_pins(): void
    {
        $sessionId = 'test-session-lock-' . uniqid();
        $this->post('/ussd/callback', ['sessionId' => $sessionId, 'serviceCode' => '*347#', 'phoneNumber' => '+2348031234567', 'text' => '1*111111']);
        $response = $this->post('/ussd/callback', ['sessionId' => $sessionId, 'serviceCode' => '*347#', 'phoneNumber' => '+2348031234567', 'text' => '1*222222']);
        $response = $this->post('/ussd/callback', ['sessionId' => $sessionId, 'serviceCode' => '*347#', 'phoneNumber' => '+2348031234567', 'text' => '1*333333']);

        $response->assertStatus(200);
        $body = trim($response->content());
        $this->assertStringStartsWith('END ', $body);
        $this->assertStringContainsString('Too many incorrect attempts', $body);
    }

    #[Test]
    public function send_money_shows_recipient_name_on_preview(): void
    {
        $response = $this->ussd('+2348031234567', '2*8037654321*500');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Jane Smith', $response);
        $this->assertStringContainsString('Enter your PIN', $response);
    }

    #[Test]
    public function send_money_completes_transfer_with_correct_pin(): void
    {
        $response = $this->ussd('+2348031234567', '2*8037654321*500*123456');
        $this->assertStringStartsWith('END ', $response);
        $this->assertStringContainsString('Sent!', $response);
        $this->assertStringContainsString('Jane Smith', $response);

        $this->user->refresh();
        $this->recipient->refresh();

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->user->id,
            'available_balance' => 9490.00, // 10000 - 500 - 10 (min fee)
        ]);

        $this->assertDatabaseHas('wallets', [
            'user_id' => $this->recipient->id,
            'available_balance' => 5500.00, // 5000 + 500
        ]);

        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => $this->wallet->id,
            'transaction_type' => 'transfer',
            'status' => 'completed',
            'channel' => 'ussd',
        ]);

        $transaction = Transaction::where('from_wallet_id', $this->wallet->id)
            ->where('transaction_type', 'transfer')
            ->first();
        $this->assertEquals('ussd', $transaction->channel);
    }

    #[Test]
    public function send_money_with_wrong_pin_shows_retry(): void
    {
        $response = $this->ussd('+2348031234567', '2*8037654321*500*000000');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Enter your PIN', $response);
    }

    #[Test]
    public function exit_option_ends_session(): void
    {
        $response = $this->ussd('+2348031234567', '0');
        $this->assertStringStartsWith('END ', $response);
        $this->assertStringContainsString('Thank you for using PayEase', $response);
    }

    #[Test]
    public function ajo_status_shows_not_in_group_when_no_membership(): void
    {
        $response = $this->ussd('+2348031234567', '4');
        $this->assertStringStartsWith('END ', $response);
        $this->assertStringContainsString('not currently in an Ajo group', $response);
    }

    #[Test]
    public function ajo_status_shows_group_info_when_member(): void
    {
        $group = AjoGroup::create([
            'ajo_owner_id' => null,
            'managing_agent_id' => null,
            'name' => 'My Savings Group',
            'contribution_amount' => 2000.00,
            'frequency' => 'weekly',
            'members_count' => 5,
            'payout_order' => 'fixed',
            'status' => 'active',
        ]);

        AjoMember::create([
            'group_id' => $group->id,
            'user_id' => $this->user->id,
            'position' => 1,
            'status' => 'active',
        ]);

        $response = $this->ussd('+2348031234567', '4');
        $this->assertStringStartsWith('END ', $response);
        $this->assertStringContainsString('My Savings Group', $response);
    }

    #[Test]
    public function buy_airtime_shows_network_selection(): void
    {
        $response = $this->ussd('+2348031234567', '3');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Select network', $response);
        $this->assertStringContainsString('MTN', $response);
        $this->assertStringContainsString('Airtel', $response);
    }

    #[Test]
    public function buy_airtime_completes_with_correct_pin(): void
    {
        $response = $this->ussd('+2348031234567', '3*1*500*123456');
        $this->assertStringStartsWith('END ', $response);
        $this->assertStringContainsString('Airtime purchase successful', $response);
        $this->assertStringContainsString('MTN', $response);

        $this->assertDatabaseHas('transactions', [
            'from_wallet_id' => $this->wallet->id,
            'transaction_type' => 'airtime',
            'status' => 'completed',
            'channel' => 'ussd',
        ]);

        $this->wallet->refresh();
        $this->assertEquals(9500.00, $this->wallet->available_balance);
    }

    #[Test]
    public function channel_is_set_on_ussd_transactions(): void
    {
        $this->ussd('+2348031234567', '2*8037654321*500*123456');

        $transaction = Transaction::where('from_wallet_id', $this->wallet->id)
            ->where('transaction_type', 'transfer')
            ->first();

        $this->assertNotNull($transaction);
        $this->assertEquals('ussd', $transaction->channel);
    }

    #[Test]
    public function change_pin_asks_for_current_pin(): void
    {
        $response = $this->ussd('+2348031234567', '5');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Enter your current PIN', $response);
    }

    #[Test]
    public function change_pin_with_wrong_current_pin_shows_retry(): void
    {
        $response = $this->ussd('+2348031234567', '5*000000');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Incorrect PIN', $response);
        $this->assertStringContainsString('2 attempt(s) remaining', $response);
    }

    #[Test]
    public function change_pin_with_correct_current_pin_prompts_for_new_pin(): void
    {
        $response = $this->ussd('+2348031234567', '5*123456');
        $this->assertStringStartsWith('CON ', $response);
        $this->assertStringContainsString('Enter your new PIN', $response);
    }

    #[Test]
    public function change_pin_asks_for_confirmation(): void
    {
        $sessionId = 'test-session-pin-' . uniqid();

        $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456',
        ]);

        $response = $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456*654321',
        ]);

        $body = trim($response->content());
        $this->assertStringStartsWith('CON ', $body);
        $this->assertStringContainsString('Confirm your new PIN', $body);
    }

    #[Test]
    public function change_pin_rejects_mismatched_confirmation(): void
    {
        $sessionId = 'test-session-pin-conflict-' . uniqid();

        $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456',
        ]);

        $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456*654321',
        ]);

        $response = $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456*654321*999999',
        ]);

        $body = trim($response->content());
        $this->assertStringStartsWith('CON ', $body);
        $this->assertStringContainsString('do not match', $body);
    }

    #[Test]
    public function change_pin_completes_successfully(): void
    {
        $sessionId = 'test-session-pin-ok-' . uniqid();

        $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456',
        ]);

        $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456*654321',
        ]);

        $response = $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456*654321*654321',
        ]);

        $body = trim($response->content());
        $this->assertStringStartsWith('END ', $body);
        $this->assertStringContainsString('PIN has been changed successfully', $body);

        $this->user->refresh();
        $this->assertTrue(Hash::check('654321', $this->user->pin_hash));
    }

    #[Test]
    public function change_pin_rejects_non_digit_new_pin(): void
    {
        $sessionId = 'test-session-pin-format-' . uniqid();

        $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456',
        ]);

        $response = $this->post('/ussd/callback', [
            'sessionId' => $sessionId, 'serviceCode' => '*347#',
            'phoneNumber' => '+2348031234567', 'text' => '5*123456*abc',
        ]);

        $body = trim($response->content());
        $this->assertStringStartsWith('CON ', $body);
        $this->assertStringContainsString('6 digits', $body);
    }
}
