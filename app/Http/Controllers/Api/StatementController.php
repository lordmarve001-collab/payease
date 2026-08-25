<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatementController extends Controller
{
    public function __construct(
        protected ReceiptService $receiptService,
    ) {}

    public function transactions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'type' => 'nullable|string',
            'status' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $wallet = $user->wallets()->where('wallet_type', 'customer')->first();

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'No wallet found.',
            ], 404);
        }

        $query = Transaction::where(function ($q) use ($wallet) {
            $q->where('from_wallet_id', $wallet->id)
              ->orWhere('to_wallet_id', $wallet->id);
        });

        if (!empty($validated['from'])) {
            $query->where('created_at', '>=', $validated['from']);
        }

        if (!empty($validated['to'])) {
            $query->where('created_at', '<=', $validated['to'] . ' 23:59:59');
        }

        if (!empty($validated['type'])) {
            $query->where('transaction_type', $validated['type']);
        }

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        $transactions = $query->orderByDesc('created_at')
            ->paginate($validated['per_page'] ?? 25);

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions->items(),
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ],
        ]);
    }

    public function download(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validated = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $wallet = $user->wallets()->where('wallet_type', 'customer')->first();

        $transactions = Transaction::where(function ($q) use ($wallet) {
            $q->where('from_wallet_id', $wallet->id)
              ->orWhere('to_wallet_id', $wallet->id);
        })
            ->whereBetween('created_at', [$validated['from'], $validated['to'] . ' 23:59:59'])
            ->orderByDesc('created_at')
            ->get()
            ->toArray();

        $statementHtml = $this->receiptService->generateStatementHtml(
            $user,
            $transactions,
            $validated['from'],
            $validated['to'],
        );

        return response()->streamDownload(function () use ($statementHtml) {
            echo $statementHtml;
        }, 'payease-statement-' . $validated['from'] . '-to-' . $validated['to'] . '.html', [
            'Content-Type' => 'text/html',
        ]);
    }
}
