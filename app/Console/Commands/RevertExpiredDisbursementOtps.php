<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevertExpiredDisbursementOtps extends Command
{
    protected $signature = 'disbursement:revert-expired-otps {--hours=24 : Hours after which a pending OTP transaction is considered expired}';

    protected $description = 'Refund wallets for bank-transfer disbursement OTPs that were never authorized.';

    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $cutoff = now()->subHours($hours);

        $transactions = Transaction::query()
            ->where('status', 'pending_disbursement_otp')
            ->where('created_at', '<', $cutoff)
            ->whereNotNull('from_wallet_id')
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No expired pending disbursement OTPs found.');
            return self::SUCCESS;
        }

        $refunded = 0;

        foreach ($transactions as $transaction) {
            try {
                DB::transaction(function () use ($transaction): void {
                    $lockedWallet = Wallet::query()
                        ->whereKey($transaction->from_wallet_id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $total = round((float) $transaction->amount + (float) $transaction->fee, 2);
                    $lockedWallet->balance = round((float) $lockedWallet->balance + $total, 2);
                    $lockedWallet->available_balance = round((float) $lockedWallet->available_balance + $total, 2);
                    $lockedWallet->save();

                    $transaction->update([
                        'status' => 'reversed',
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'reversed_at' => now()->toDateTimeString(),
                            'reversal_reason' => 'OTP authorization timeout',
                        ]),
                    ]);
                });

                $refunded++;

                Log::channel('monnify')->info('Expired disbursement OTP refunded', [
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'amount' => $transaction->amount,
                ]);
            } catch (\Exception $e) {
                Log::channel('monnify')->error('Failed to refund expired disbursement OTP', [
                    'transaction_id' => $transaction->id,
                    'reference' => $transaction->reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Refunded {$refunded} expired disbursement OTP transaction(s).");

        return self::SUCCESS;
    }
}
