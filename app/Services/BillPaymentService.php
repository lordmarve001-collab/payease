<?php

namespace App\Services;

use App\Contracts\BillPaymentClientInterface;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BillPaymentService
{
    public function __construct(
        protected BillPaymentClientInterface $client,
        protected WalletService $walletService,
    ) {
    }

    public function purchaseAirtime(string $phoneNumber, string $network, float $amount, string $channel = 'web', ?User $user = null): array
    {
        return $this->process('airtime', $amount, $channel, $user, function (User $u, string $reference) use ($phoneNumber, $network, $amount) {
            return $this->client->purchaseAirtime($phoneNumber, $network, $amount, $reference);
        });
    }

    public function purchaseData(string $phoneNumber, string $network, string $bundleCode, float $price, string $channel = 'web', ?User $user = null): array
    {
        return $this->process('data', $price, $channel, $user, function (User $u, string $reference) use ($phoneNumber, $network, $bundleCode, $price) {
            return $this->client->purchaseData($phoneNumber, $network, $bundleCode, $reference);
        });
    }

    public function purchaseCable(string $smartCardNumber, string $packageCode, string $provider, float $price, string $channel = 'web', ?User $user = null): array
    {
        return $this->process('cable', $price, $channel, $user, function (User $u, string $reference) use ($smartCardNumber, $packageCode, $provider, $price) {
            return $this->client->purchaseCableSubscription($smartCardNumber, $packageCode, $provider, $price, $reference);
        });
    }

    public function purchaseEducation(string $studentId, string $examType, float $amount, string $channel = 'web', ?User $user = null): array
    {
        return $this->process('education', $amount, $channel, $user, function (User $u, string $reference) use ($studentId, $examType, $amount) {
            return $this->client->purchaseEducation($studentId, $examType, $amount, $reference);
        });
    }

    public function purchaseElectricity(string $meterNumber, string $disco, float $amount, string $channel = 'web', string $meterType = 'prepaid', ?User $user = null): array
    {
        return $this->process('electricity', $amount, $channel, $user, function (User $u, string $reference) use ($meterNumber, $disco, $amount, $meterType) {
            return $this->client->purchaseElectricity($meterNumber, $disco, $amount, $reference, $meterType);
        });
    }

    public function getDataBundles(string $network): array
    {
        return $this->client->getDataBundles($network);
    }

    public function queryTransaction(string $requestId): array
    {
        return $this->client->queryTransaction($requestId);
    }

    protected function process(string $type, float $amount, string $channel, ?User $user, callable $apiCall): array
    {
        $user ??= auth()->user();

        if (!$user) {
            throw new RuntimeException('User not authenticated.');
        }

        if ($amount <= 0) {
            throw new RuntimeException('Amount must be greater than zero.');
        }

        $wallet = $this->walletService->getCustomerWallet($user);

        if (!$wallet) {
            throw new RuntimeException('Wallet not found.');
        }

        if (!in_array(strtolower((string) $wallet->status), ['active', 'verified', 'pending_kyc'], true)) {
            throw new RuntimeException('Your wallet is not active.');
        }

        $limitViolation = $this->walletService->getLimitViolation($wallet, $amount);
        if ($limitViolation !== null) {
            throw new RuntimeException($limitViolation);
        }

        if ((float) $wallet->available_balance < $amount) {
            throw new RuntimeException('Insufficient wallet balance.');
        }

        $reference = $this->generateReference(strtoupper(substr($type, 0, 3)));

        try {
            $result = DB::transaction(function () use ($user, $wallet, $amount, $reference, $type, $channel, $apiCall): array {
                $lockedWallet = Wallet::whereKey($wallet->id)->lockForUpdate()->firstOrFail();

                if ((float) $lockedWallet->available_balance < $amount) {
                    throw new RuntimeException('Insufficient wallet balance.');
                }

                $lockedWallet->balance = round((float) $lockedWallet->balance - $amount, 2);
                $lockedWallet->available_balance = round((float) $lockedWallet->available_balance - $amount, 2);
                $lockedWallet->save();

                $result = $apiCall($user, $reference);

                $isSuccess = strtolower((string) ($result['status'] ?? '')) === 'success';

                if (!$isSuccess) {
                    $lockedWallet->balance = round((float) $lockedWallet->balance + $amount, 2);
                    $lockedWallet->available_balance = round((float) $lockedWallet->available_balance + $amount, 2);
                    $lockedWallet->save();
                }

                $this->log($user, $reference, $type, $amount, $channel, $result, $isSuccess ? 'completed' : 'failed');

                return $result;
            });

            return array_merge($result, ['reference' => $reference]);
        } catch (\Throwable $e) {
            Log::channel('billpayment')->error("{$type} purchase failed", [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'reference' => $reference ?? null,
            ];
        }
    }

    protected function log(User $user, string $reference, string $type, float $amount, string $channel, array $result, string $status): Transaction
    {
        $wallet = $this->walletService->getCustomerWallet($user);

        $txn = Transaction::create([
            'user_id' => $user->id,
            'reference' => $reference,
            'transaction_type' => $type,
            'amount' => $amount,
            'status' => $status,
            'from_wallet_id' => $wallet?->id,
            'description' => ucfirst($type) . ' purchase via ' . strtoupper($channel),
            'channel' => $channel,
            'payment_method' => 'wallet',
            'metadata' => [
                'provider_response' => $result,
                'channel' => $channel,
            ],
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        Event::dispatch('transaction.completed', [$txn]);

        return $txn;
    }

    protected function generateReference(string $prefix): string
    {
        do {
            $reference = $prefix . '-' . strtoupper(uniqid() . bin2hex(random_bytes(4)));
        } while (Transaction::where('reference', $reference)->exists());

        return $reference;
    }
}
