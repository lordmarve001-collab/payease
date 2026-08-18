<?php

namespace App\Http\Controllers;

use App\Helpers\PhoneNumberHelper;
use App\Models\AjoMember;
use App\Models\User;
use App\Services\AjoService;
use App\Services\BillPaymentService;
use App\Services\TransactionService;
use App\Services\UssdSessionStore;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UssdController extends Controller
{
    protected const MAX_TEXT_LENGTH = 180;
    protected const PIN_MAX_ATTEMPTS = 3;

    public function __construct(
        protected WalletService $walletService,
        protected TransactionService $transactionService,
        protected AjoService $ajoService,
        protected BillPaymentService $billPaymentService,
        protected UssdSessionStore $sessionStore,
    ) {
    }

    public function __invoke(Request $request): \Illuminate\Http\Response
    {
        $sessionId = $request->input('sessionId');
        $phoneNumber = $request->input('phoneNumber');
        $text = $request->input('text', '');

        $user = $this->resolveUser($phoneNumber);

        if ($user === null) {
            return $this->end('You are not registered on PayEase. Please visit an agent or download the app to register.');
        }

        if ($text === '') {
            $this->sessionStore->clearAll($sessionId);
            return $this->mainMenu();
        }

        $parts = explode('*', $text);
        $option = $parts[0] ?? '';

        if ($option === '0') {
            $this->sessionStore->clearAll($sessionId);
            return $this->end('Thank you for using PayEase.');
        }

        if ($option === '1') {
            return $this->handleBalanceCheck($user, $parts, $sessionId);
        }

        if ($option === '2') {
            return $this->handleSendMoney($user, $parts, $sessionId);
        }

        if ($option === '3') {
            return $this->handleBuyAirtime($user, $parts, $sessionId);
        }

        if ($option === '4') {
            return $this->handleAjoStatus($user, $sessionId);
        }

        if ($option === '5') {
            return $this->handleChangePin($user, $parts, $sessionId);
        }

        return $this->mainMenu();
    }

    protected function mainMenu(): \Illuminate\Http\Response
    {
        return $this->con("Welcome to PayEase\n1. Check Balance\n2. Send Money\n3. Buy Airtime\n4. My Ajo Status\n5. Change PIN\n0. Exit");
    }

    protected function handleBalanceCheck(User $user, array $parts, string $sessionId): \Illuminate\Http\Response
    {
        if (count($parts) === 1) {
            $this->sessionStore->setTempData($sessionId, ['flow' => 'balance']);
            $remaining = $this->pinRetryMessage($sessionId);
            return $this->con('Enter your PIN to check balance:' . $remaining);
        }

        $pin = $parts[1] ?? '';

        if (!$user->verifyLoginPin($pin)) {
            return $this->handleWrongPin($sessionId);
        }

        $this->sessionStore->clearAll($sessionId);

        try {
            $balance = $this->walletService->getBalance($user);
            return $this->end('Your balance is ₦' . number_format($balance, 2) . '.');
        } catch (RuntimeException $e) {
            return $this->end('Unable to fetch balance. Please try again later.');
        }
    }

    protected function handleSendMoney(User $user, array $parts, string $sessionId): \Illuminate\Http\Response
    {
        if (count($parts) === 1) {
            $this->sessionStore->setTempData($sessionId, ['flow' => 'send_money']);
            return $this->con('Enter recipient phone number:');
        }

        $recipientPhone = $parts[1];

        if (count($parts) === 2) {
            $recipient = $this->transactionService->lookupRecipientByPhone($recipientPhone);
            if (!$recipient) {
                return $this->end('Recipient not found. Please try again.');
            }
            if ($recipient->is($user)) {
                return $this->end('You cannot send money to yourself.');
            }
            $this->sessionStore->setTempData($sessionId, [
                'flow' => 'send_money',
                'recipient_phone' => $recipientPhone,
                'recipient_name' => $recipient->full_name,
            ]);
            return $this->con('Send to ' . $recipient->full_name . ".\nEnter amount:");
        }

        if (count($parts) === 3) {
            $amount = (float) $parts[2];
            if ($amount <= 0) {
                return $this->end('Invalid amount. Please try again.');
            }

            try {
                $preview = $this->transactionService->getTransferPreview($user, $recipientPhone, $amount);
            } catch (RuntimeException $e) {
                return $this->end($e->getMessage());
            }

            if (!$preview['can_proceed']) {
                return $this->end((string) $preview['message']);
            }

            $this->sessionStore->setTempData($sessionId, [
                'flow' => 'send_money',
                'recipient_phone' => $recipientPhone,
                'recipient_name' => $preview['recipient']->full_name,
                'amount' => $amount,
            ]);
            $remaining = $this->pinRetryMessage($sessionId);
            return $this->con('Send ₦' . number_format($amount, 2) . ' to ' . $preview['recipient']->full_name . ".\nEnter your PIN to confirm:" . $remaining);
        }

        if (count($parts) === 4) {
            $pin = $parts[3] ?? '';

            if (!$user->verifyTransferPin($pin)) {
                return $this->handleWrongPin($sessionId, function () use ($user, $recipientPhone, $parts) {
                    return $this->con('Send ₦' . number_format((float) $parts[2], 2) . ".\nEnter your PIN to confirm:");
                });
            }

            $this->sessionStore->clearAll($sessionId);

            try {
                $transaction = $this->transactionService->initiateTransfer($user, $recipientPhone, (float) $parts[2]);
                $transaction->update(['channel' => 'ussd']);
                $balance = $this->walletService->getBalance($user);
                $recipient = $this->transactionService->lookupRecipientByPhone($recipientPhone);
                $recipientName = $recipient ? $recipient->full_name : $recipientPhone;
                return $this->end('Sent! ₦' . number_format((float) $parts[2], 2) . ' to ' . $recipientName . '. New balance: ₦' . number_format($balance, 2) . '.');
            } catch (RuntimeException $e) {
                return $this->end($e->getMessage());
            }
        }

        return $this->end('Invalid input. Please try again.');
    }

    protected function handleBuyAirtime(User $user, array $parts, string $sessionId): \Illuminate\Http\Response
    {
        $networks = ['MTN', 'Airtel', 'Glo', '9mobile'];

        if (count($parts) === 1) {
            $this->sessionStore->setTempData($sessionId, ['flow' => 'buy_airtime']);
            return $this->con("Select network:\n1. MTN\n2. Airtel\n3. Glo\n4. 9mobile\n0. Back");
        }

        if (count($parts) === 2) {
            $networkIndex = (int) $parts[1];
            if ($networkIndex < 1 || $networkIndex > 4) {
                return $this->end('Invalid selection. Please try again.');
            }
            $this->sessionStore->setTempData($sessionId, [
                'flow' => 'buy_airtime',
                'network_index' => $networkIndex,
            ]);
            return $this->con('Enter amount for ' . $networks[$networkIndex - 1] . ':');
        }

        if (count($parts) === 3) {
            $amount = (float) $parts[2];
            if ($amount <= 0) {
                return $this->end('Invalid amount. Please try again.');
            }
            $networkIndex = (int) $parts[1];
            $this->sessionStore->setTempData($sessionId, [
                'flow' => 'buy_airtime',
                'network_index' => $networkIndex,
                'amount' => $amount,
            ]);
            $remaining = $this->pinRetryMessage($sessionId);
            return $this->con('Buy ₦' . number_format($amount, 2) . ' ' . $networks[$networkIndex - 1] . " airtime.\nEnter your PIN to confirm:" . $remaining);
        }

        if (count($parts) === 4) {
            $pin = $parts[3] ?? '';
            $amount = (float) $parts[2];
            $networkIndex = (int) $parts[1];

            if (!$user->verifyTransferPin($pin)) {
                return $this->handleWrongPin($sessionId, function () use ($amount, $networkIndex, $networks) {
                    $remaining = $this->pinRetryMessage();
                    return $this->con('Buy ₦' . number_format($amount, 2) . ' ' . $networks[$networkIndex - 1] . " airtime.\nEnter your PIN to confirm:" . $remaining);
                });
            }

            $this->sessionStore->clearAll($sessionId);

            try {
                $result = $this->billPaymentService->purchaseAirtime(
                    $user->phone_number,
                    $networks[$networkIndex - 1],
                    $amount,
                    'ussd',
                    $user
                );

                if (($result['status'] ?? '') !== 'success') {
                    return $this->end($result['error'] ?? 'Airtime purchase failed.');
                }

                return $this->end('Airtime purchase successful! ₦' . number_format($amount, 2) . ' ' . $networks[$networkIndex - 1] . '.');
            } catch (RuntimeException $e) {
                return $this->end($e->getMessage());
            }
        }

        return $this->end('Invalid input. Please try again.');
    }

    protected function handleAjoStatus(User $user, string $sessionId): \Illuminate\Http\Response
    {
        $this->sessionStore->clearAll($sessionId);

        $membership = AjoMember::where('user_id', $user->id)
            ->whereHas('group', function ($q) {
                $q->whereIn('status', ['active', 'pending']);
            })
            ->with('group')
            ->first();

        if (!$membership) {
            return $this->end('You\'re not currently in an Ajo group. Visit an agent to join one.');
        }

        $group = $membership->group;
        $progress = $this->ajoService->getCycleProgress($group);
        $nextPayout = $this->ajoService->getNextPayout($group);

        $payoutDate = $nextPayout['scheduled_date']?->format('d M Y') ?? 'N/A';

        return $this->end(
            'Group: ' . $group->name . '. You\'ve paid ' . $progress['paid_members'] . '/' . $progress['total_members'] . ' this cycle. Next payout: ' . $payoutDate . '.'
        );
    }

    protected function handleChangePin(User $user, array $parts, string $sessionId): \Illuminate\Http\Response
    {
        if (count($parts) === 1) {
            $this->sessionStore->setTempData($sessionId, ['flow' => 'change_pin']);
            return $this->con('Enter your current PIN:');
        }

        if (count($parts) === 2) {
            $currentPin = $parts[1];

            if (!$user->verifyLoginPin($currentPin)) {
                return $this->handleWrongPin($sessionId);
            }

            $this->sessionStore->setTempData($sessionId, [
                'flow' => 'change_pin',
                'current_pin_verified' => true,
            ]);
            $this->sessionStore->clearRetry($sessionId);
            return $this->con('Enter your new PIN (6 digits):');
        }

        if (count($parts) === 3) {
            $newPin = $parts[2];

            if (!preg_match('/^\d{6}$/', $newPin)) {
                return $this->con('PIN must be exactly 6 digits. Enter your new PIN:');
            }

            $this->sessionStore->setTempData($sessionId, [
                'flow' => 'change_pin',
                'current_pin_verified' => true,
                'new_pin' => $newPin,
            ]);
            return $this->con('Confirm your new PIN:');
        }

        if (count($parts) === 4) {
            $confirmPin = $parts[3];
            $data = $this->sessionStore->getTempData($sessionId);
            $newPin = $data['new_pin'] ?? '';

            if ($confirmPin !== $newPin) {
                $this->sessionStore->setTempData($sessionId, [
                    'flow' => 'change_pin',
                    'current_pin_verified' => true,
                    'new_pin' => $newPin,
                ]);
                return $this->con('PINs do not match. Enter your new PIN again:');
            }

            if (!preg_match('/^\d{6}$/', $newPin)) {
                return $this->con('PIN must be exactly 6 digits. Enter your new PIN:');
            }

            $hashed = Hash::make($newPin);
            $user->update([
                'pin_hash' => $hashed,
                'login_pin_hash' => $hashed,
                'transfer_pin_hash' => $hashed,
            ]);
            $this->sessionStore->clearAll($sessionId);

            return $this->end('Your PIN has been changed successfully.');
        }

        return $this->end('Invalid input. Please try again.');
    }

    protected function handleWrongPin(string $sessionId, ?callable $retryCallback = null): \Illuminate\Http\Response
    {
        $attempts = $this->sessionStore->incrementRetry($sessionId);
        $remaining = self::PIN_MAX_ATTEMPTS - $attempts;

        if ($remaining <= 0) {
            $this->sessionStore->clearAll($sessionId);
            return $this->end('Too many incorrect attempts. Session ended. Please start again.');
        }

        if ($retryCallback) {
            return $retryCallback();
        }

        return $this->con('Incorrect PIN. ' . $remaining . ' attempt(s) remaining. Enter your PIN again:');
    }

    protected function pinRetryMessage(string $sessionId = ''): string
    {
        if ($sessionId === '') {
            return '';
        }
        $attempts = $this->sessionStore->getRetryCount($sessionId);
        if ($attempts === 0) {
            return '';
        }
        $remaining = self::PIN_MAX_ATTEMPTS - $attempts;
        return "\n" . $remaining . ' attempt(s) remaining.';
    }

    protected function resolveUser(string $phoneNumber): ?User
    {
        $normalized = null;
        try {
            $normalized = PhoneNumberHelper::normalize($phoneNumber);
        } catch (\InvalidArgumentException) {
            return null;
        }

        if ($normalized === null) {
            return null;
        }

        return User::where('phone_number', $normalized)->first();
    }

    protected function con(string $message): \Illuminate\Http\Response
    {
        $text = 'CON ' . $this->truncate($message);
        return response($text, 200)->header('Content-Type', 'text/plain');
    }

    protected function end(string $message): \Illuminate\Http\Response
    {
        $text = 'END ' . $this->truncate($message);
        return response($text, 200)->header('Content-Type', 'text/plain');
    }

    protected function truncate(string $text): string
    {
        if (strlen($text) > self::MAX_TEXT_LENGTH) {
            return substr($text, 0, self::MAX_TEXT_LENGTH - 3) . '...';
        }
        return $text;
    }
}
