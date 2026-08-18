<?php

namespace Tests\Feature;

use App\Contracts\SmsClientInterface;
use App\Jobs\SendSmsNotification;
use App\Livewire\Auth\VerifyOtp;
use App\Models\User;
use App\Models\Wallet;
use App\Services\OtpService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class SmsNotificationsTest extends TestCase
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

    public function test_send_sms_notification_job_calls_bound_sms_client(): void
    {
        $fakeClient = new class implements SmsClientInterface
        {
            /** @var array<int, array{phone_number:string,message:string}> */
            public array $sent = [];

            public function send(string $phoneNumber, string $message): array
            {
                $this->sent[] = [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                ];

                return [
                    'status' => 'sent',
                    'provider_id' => 'fake-provider-id',
                ];
            }
        };

        $this->app->instance(SmsClientInterface::class, $fakeClient);

        SendSmsNotification::dispatchSync('08030000001', 'Test SMS from PayEase');

        $this->assertCount(1, $fakeClient->sent);
        $this->assertSame('+2348030000001', $fakeClient->sent[0]['phone_number']);
        $this->assertSame('Test SMS from PayEase', $fakeClient->sent[0]['message']);
    }

    public function test_transfer_dispatches_two_sms_alerts_with_formatted_messages(): void
    {
        Queue::fake();

        $sender = $this->createCustomer('08030000001', 'Sender Customer', 50000, 1);
        $recipient = $this->createCustomer('08030000002', 'Recipient Customer', 20000, 1);

        $transaction = app(TransactionService::class)->initiateTransfer($sender, $recipient->phone_number, 10000);

        $this->assertSame('completed', $transaction->status);
        Queue::assertPushed(SendSmsNotification::class, 2);
        Queue::assertPushed(SendSmsNotification::class, function (SendSmsNotification $job) use ($transaction, $sender): bool {
            return $job->phoneNumber === $sender->phone_number
                && $job->message === sprintf(
                    'You sent ₦10,000.00 to Recipient Customer. Ref: %s. New balance: ₦39,950.00. -PayEase',
                    $transaction->reference
                );
        });
        Queue::assertPushed(SendSmsNotification::class, function (SendSmsNotification $job) use ($transaction, $recipient): bool {
            return $job->phoneNumber === $recipient->phone_number
                && $job->message === sprintf(
                    'You received ₦10,000.00 from Sender Customer. Ref: %s. New balance: ₦30,000.00. -PayEase',
                    $transaction->reference
                );
        });
    }

    public function test_failed_sms_send_does_not_affect_underlying_transaction_completion(): void
    {
        $sender = $this->createCustomer('08030000003', 'Failure Sender', 50000, 1);
        $recipient = $this->createCustomer('08030000004', 'Failure Recipient', 20000, 1);

        $this->app->instance(SmsClientInterface::class, new class implements SmsClientInterface
        {
            public function send(string $phoneNumber, string $message): array
            {
                return [
                    'status' => 'failed',
                    'provider_id' => null,
                    'error' => 'Simulated SMS provider outage.',
                ];
            }
        });

        $transaction = app(TransactionService::class)->initiateTransfer($sender, $recipient->phone_number, 10000);

        $this->assertSame('completed', $transaction->fresh()->status);
        $this->assertEquals(39950.0, (float) $sender->wallets()->firstOrFail()->fresh()->available_balance);
        $this->assertEquals(30000.0, (float) $recipient->wallets()->firstOrFail()->fresh()->available_balance);
    }

    public function test_otp_resend_is_blocked_within_cooldown_window(): void
    {
        $user = $this->createCustomer('08030000005', 'OTP Customer', 5000, 0);
        app(OtpService::class)->sendOtp($user, enforceCooldown: false);

        session(['otp_user_id' => $user->id]);

        Livewire::test(VerifyOtp::class)
            ->call('resendOtp')
            ->assertSee('Please wait');
    }

    public function test_otp_rate_limit_blocks_fourth_send_within_ten_minutes(): void
    {
        $user = $this->createCustomer('08030000006', 'Rate Limit Customer', 5000, 0);
        $otpService = app(OtpService::class);

        $otpService->sendOtp($user, enforceCooldown: false);
        $this->travel(61)->seconds();

        $otpService->sendOtp($user);
        $this->travel(61)->seconds();

        $otpService->sendOtp($user);
        $this->travel(61)->seconds();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Too many OTP requests for this number. Please try again in a few minutes.');

        $otpService->sendOtp($user);
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
}
