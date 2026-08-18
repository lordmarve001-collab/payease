<?php

namespace App\Http\Controllers;

use App\Events\TransactionCompleted;
use App\Models\AuditLog;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\MonnifyClient;
use App\Services\MmoProviderSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MonnifyWebhookController extends Controller
{
    public function __invoke(Request $request, MmoProviderSettingService $providerSettingService): JsonResponse
    {
        $payload = $request->getContent();

        Log::channel('monnify')->info('Monnify webhook received', [
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'body' => $request->json()->all(),
        ]);

        $setting = $providerSettingService->getProviderSetting('monnify');
        $client = new MonnifyClient(is_array($setting->credentials) ? $setting->credentials : [], (string) $setting->environment);

        if (!$client->isAllowedWebhookIp($request->ip())) {
            Log::channel('monnify')->warning('Monnify webhook rejected: unauthorized IP', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Unauthorized webhook origin.'], 403);
        }

        if (!$client->verifyWebhookSignature($request->header('monnify-signature'), $payload)) {
            Log::channel('monnify')->warning('Monnify webhook rejected: invalid signature', ['ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid Monnify signature.'], 403);
        }

        $body = $request->json()->all();
        $eventType = strtolower((string) ($body['eventType'] ?? ''));

        if (!str_contains($eventType, 'successful') || (!str_contains($eventType, 'collection') && !str_contains($eventType, 'payment'))) {
            Log::channel('monnify')->info('Monnify webhook ignored: non-deposit event', ['event_type' => $body['eventType'] ?? null]);

            return response()->json(['message' => 'Webhook received.'], 200);
        }

        $eventData = (array) ($body['eventData'] ?? []);
        $transactionReference = (string) ($eventData['transactionReference'] ?? '');
        $accountNumber = (string) data_get($eventData, 'destinationAccountInformation.accountNumber', data_get($eventData, 'destinationAccountInformation.accountNumber'));
        $amount = (float) ($eventData['amountPaid'] ?? 0);

        if ($transactionReference === '' || $accountNumber === '' || $amount <= 0) {
            Log::channel('monnify')->warning('Monnify webhook ignored: missing required fields', [
                'transaction_reference' => $transactionReference,
                'account_number' => $accountNumber,
                'amount' => $amount,
            ]);

            return response()->json(['message' => 'Webhook ignored.'], 202);
        }

        if (Transaction::query()->where('mmo_transaction_id', $transactionReference)->exists()) {
            Log::channel('monnify')->info('Monnify webhook ignored: duplicate', ['transaction_reference' => $transactionReference]);

            return response()->json(['message' => 'Duplicate webhook ignored.'], 200);
        }

        $wallet = Wallet::query()
            ->where('account_number', $accountNumber)
            ->orWhere('wallet_account_number', $accountNumber)
            ->orWhere('provider_reference', data_get($eventData, 'product.reference'))
            ->first();

        if (!$wallet) {
            Log::channel('monnify')->warning('Monnify webhook: no wallet mapped', [
                'account_number' => $accountNumber,
                'account_reference' => data_get($eventData, 'product.reference'),
            ]);

            AuditLog::create([
                'user_id' => null,
                'action' => 'monnify_webhook_unmatched_account',
                'entity_type' => 'wallet',
                'entity_id' => null,
                'old_values' => null,
                'new_values' => [
                    'account_number' => $accountNumber,
                    'account_reference' => data_get($eventData, 'product.reference'),
                    'amount' => $amount,
                    'transaction_reference' => $transactionReference,
                ],
                'ip_address' => $request->ip(),
                'device_id' => null,
            ]);

            return response()->json(['message' => 'No wallet mapped to this account number yet.'], 202);
        }

        $transaction = DB::transaction(function () use ($wallet, $eventData, $transactionReference, $accountNumber, $amount): Transaction {
            $lockedWallet = Wallet::query()
                ->whereKey($wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedWallet->balance = round((float) $lockedWallet->balance + $amount, 2);
            $lockedWallet->available_balance = round((float) $lockedWallet->available_balance + $amount, 2);
            $lockedWallet->save();

            return Transaction::create([
                'reference' => (string) ($eventData['paymentReference'] ?? $transactionReference),
                'transaction_type' => 'bank_transfer_deposit',
                'amount' => $amount,
                'status' => 'completed',
                'to_wallet_id' => $lockedWallet->id,
                'recipient_phone' => $lockedWallet->user?->phone_number,
                'description' => 'Monnify reserved-account deposit',
                'mmo_partner' => 'monnify',
                'mmo_transaction_id' => $transactionReference,
                'metadata' => [
                    'event_type' => $eventData['paymentStatus'] ?? null,
                    'payment_reference' => $eventData['paymentReference'] ?? null,
                    'transaction_reference' => $transactionReference,
                    'account_number' => $accountNumber,
                    'account_reference' => data_get($eventData, 'product.reference'),
                    'payer_name' => data_get($eventData, 'customer.name'),
                    'payment_method' => $eventData['paymentMethod'] ?? null,
                    'provider_payload' => $eventData,
                ],
                'completed_at' => now(),
            ])->fresh(['toWallet.user']);
        });

        Log::channel('monnify')->info('Monnify webhook processed successfully', [
            'transaction_id' => $transaction->id,
            'reference' => $transaction->reference,
            'amount' => $amount,
            'wallet_id' => $wallet->id,
        ]);

        event(new TransactionCompleted($transaction));

        return response()->json(['message' => 'Webhook processed.'], 200);
    }
}
