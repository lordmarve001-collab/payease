<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\View;

class ReceiptService
{
    public function generateReceiptHtml(Transaction $transaction): string
    {
        $transaction->load(['fromWallet.user', 'toWallet.user']);

        return View::make('emails.transaction-receipt', [
            'transaction' => $transaction,
            'sender' => $transaction->fromWallet?->user,
            'recipient' => $transaction->toWallet?->user,
        ])->render();
    }

    public function generateStatementHtml(
        User $user,
        array $transactions,
        string $fromDate,
        string $toDate,
    ): string {
        return View::make('emails.statement', [
            'user' => $user,
            'transactions' => $transactions,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'totalCredit' => collect($transactions)->where('transaction_type', '!=', 'bank_transfer_withdrawal')->sum('amount'),
            'totalDebit' => collect($transactions)->where('transaction_type', 'bank_transfer_withdrawal')->sum('amount'),
        ])->render();
    }

    public function generateStatementCsv(
        User $user,
        array $transactions,
        string $fromDate,
        string $toDate,
    ): string {
        $headers = [
            'Reference',
            'Type',
            'Amount (₦)',
            'Status',
            'Channel',
            'Description',
            'Recipient',
            'Date',
        ];

        $callback = function () use ($headers, $transactions) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->reference,
                    $tx->transaction_type,
                    number_format((float) $tx->amount, 2),
                    $tx->status,
                    $tx->channel,
                    $tx->description,
                    $tx->recipient_phone ?? 'N/A',
                    $tx->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return $callback;
    }
}
