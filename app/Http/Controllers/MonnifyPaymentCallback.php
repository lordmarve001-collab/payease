<?php

namespace App\Http\Controllers;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\MonnifyClient;
use App\Services\MmoProviderSettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonnifyPaymentCallback extends Controller
{
    public function handleRedirect(Request $request, MmoProviderSettingService $providerSettingService)
    {
        $paymentReference = $request->query('paymentReference') ?? $request->query('transactionRef');

        if (!$paymentReference) {
            return $this->redirectWith('error', 'Payment reference not found.');
        }

        $transaction = Transaction::where('reference', $paymentReference)->first();

        if ($transaction && $transaction->status === 'completed') {
            return $this->redirectForUser($transaction->agent_id ?? $transaction->metadata['user_id'] ?? null, 'success', 'Payment confirmed! Your wallet has been credited with ₦' . number_format($transaction->amount, 2) . '.');
        }

        try {
            $setting = $providerSettingService->getProviderSetting('monnify');
            $client = new MonnifyClient(is_array($setting->credentials) ? $setting->credentials : [], (string) $setting->environment);
            $result = $client->getTransactionStatusByPaymentReference($paymentReference);

            $transactionInfo = (array) ($result['transaction'] ?? $result);
            $paymentStatus = strtolower((string) ($transactionInfo['paymentStatus'] ?? $transactionInfo['status'] ?? ''));
            $amountPaid = (float) ($transactionInfo['amountPaid'] ?? $transactionInfo['amount'] ?? 0);
            $monnifyReference = (string) ($transactionInfo['transactionReference'] ?? $transactionInfo['monnifyTransactionReference'] ?? $paymentReference);

            Log::channel('monnify')->info('Card payment callback queried', [
                'payment_reference' => $paymentReference,
                'status' => $paymentStatus,
                'amount_paid' => $amountPaid,
            ]);

            if (!in_array($paymentStatus, ['paid', 'success', 'successful', 'completed'], true) || $amountPaid <= 0) {
                return $this->redirectWith('info', 'Payment is still being processed. Your wallet will be credited once confirmed.');
            }

            $pendingTransaction = $transaction ?? Transaction::where('reference', $paymentReference)->first();

            if ($pendingTransaction && $pendingTransaction->status === 'completed') {
                return $this->redirectForUser($pendingTransaction->agent_id ?? $pendingTransaction->metadata['user_id'] ?? null, 'success', 'Payment confirmed! Your wallet has been credited with ₦' . number_format($pendingTransaction->amount, 2) . '.');
            }

            $userId = $pendingTransaction?->agent_id ?? $transactionInfo['metadata']['user_id'] ?? null;

            if (!$userId) {
                Log::channel('monnify')->error('Card payment callback: cannot identify user', [
                    'payment_reference' => $paymentReference,
                ]);
                return $this->redirectWith('error', 'Could not identify your wallet. Contact support.');
            }

            $wallet = Wallet::query()
                ->where('user_id', $userId)
                ->where('wallet_type', 'customer')
                ->first();

            if (!$wallet) {
                Log::channel('monnify')->error('Card payment callback: no wallet', [
                    'user_id' => $userId,
                    'payment_reference' => $paymentReference,
                ]);
                return $this->redirectWith('error', 'Wallet not found. Contact support.');
            }

            $completedTransaction = DB::transaction(function () use ($wallet, $pendingTransaction, $paymentReference, $amountPaid, $monnifyReference, $transactionInfo): Transaction {
                $lockedWallet = Wallet::query()
                    ->whereKey($wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedWallet->balance = round((float) $lockedWallet->balance + $amountPaid, 2);
                $lockedWallet->available_balance = round((float) $lockedWallet->available_balance + $amountPaid, 2);
                $lockedWallet->save();

                if ($pendingTransaction) {
                    $pendingTransaction->update([
                        'status' => 'completed',
                        'mmo_transaction_id' => $monnifyReference,
                        'completed_at' => now(),
                        'metadata' => array_merge($pendingTransaction->metadata ?? [], [
                            'payment_status' => $transactionInfo['paymentStatus'] ?? null,
                            'provider_payload' => $transactionInfo,
                        ]),
                    ]);
                    return $pendingTransaction->fresh(['toWallet.user']);
                }

                return Transaction::create([
                    'reference' => $paymentReference,
                    'transaction_type' => 'wallet_funding',
                    'amount' => $amountPaid,
                    'status' => 'completed',
                    'to_wallet_id' => $lockedWallet->id,
                    'recipient_phone' => $lockedWallet->user?->phone_number,
                    'description' => 'Card funding via Monnify',
                    'mmo_partner' => 'monnify',
                    'mmo_transaction_id' => $monnifyReference,
                    'metadata' => [
                        'user_id' => $lockedWallet->user_id,
                        'initiated_via' => 'card_payment',
                        'payment_status' => $transactionInfo['paymentStatus'] ?? null,
                        'provider_payload' => $transactionInfo,
                    ],
                    'completed_at' => now(),
                ])->fresh(['toWallet.user']);
            });

            event(new TransactionCompleted($completedTransaction));

            Log::channel('monnify')->info('Card payment callback credited wallet', [
                'payment_reference' => $paymentReference,
                'user_id' => $userId,
                'wallet_id' => $wallet->id,
                'amount' => $amountPaid,
            ]);

            return $this->redirectForUser($userId, 'success', 'Payment confirmed! Your wallet has been credited with ₦' . number_format($amountPaid, 2) . '.');
        } catch (\Exception $e) {
            Log::channel('monnify')->error('Card payment callback error', [
                'payment_reference' => $paymentReference,
                'error' => $e->getMessage(),
            ]);

            return $this->redirectWith('info', 'Payment is being processed. Your wallet will be credited shortly.');
        }
    }

    protected function redirectForUser(?string $userId, string $type, string $message)
    {
        if (!$userId) {
            return $this->redirectWith($type, $message);
        }

        $user = \App\Models\User::find($userId);

        if ($user && $user->hasRole('ajo-owner')) {
            return redirect()->route('ajo-owner.add-fund')->with($type, $message);
        }

        return redirect()->route('customer.add-money')->with($type, $message);
    }

    protected function redirectWith(string $type, string $message)
    {
        $fallbackRoute = redirect()->back()->getTargetUrl() !== ''
            ? redirect()->back()
            : redirect()->route('customer.add-money');

        return $fallbackRoute->with($type, $message);
    }
}
