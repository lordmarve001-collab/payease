<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Agent;
use App\Services\TransactionService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgentController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private WalletService $walletService
    ) {}

    public function dashboard(Request $request): JsonResponse
    {
        try {
            $agent = $request->user()->agent;

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered as an agent',
                ], 403);
            }

            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();

            $stats = [
                'total_cash_in' => $agent->transactions()
                    ->where('transaction_type', 'cash_in')
                    ->where('status', 'successful')
                    ->sum('amount'),
                'total_cash_out' => $agent->transactions()
                    ->where('transaction_type', 'cash_out')
                    ->where('status', 'successful')
                    ->sum('amount'),
                'today_cash_in' => $agent->transactions()
                    ->where('transaction_type', 'cash_in')
                    ->where('status', 'successful')
                    ->where('created_at', '>=', $today)
                    ->sum('amount'),
                'today_cash_out' => $agent->transactions()
                    ->where('transaction_type', 'cash_out')
                    ->where('status', 'successful')
                    ->where('created_at', '>=', $today)
                    ->sum('amount'),
                'month_earnings' => $agent->earnings()
                    ->where('created_at', '>=', $thisMonth)
                    ->sum('amount'),
                'total_earnings' => $agent->earnings()->sum('amount'),
                'total_transactions' => $agent->transactions()->count(),
                'pending_commissions' => $agent->earnings()
                    ->where('status', 'pending')
                    ->sum('amount'),
            ];

            return response()->json([
                'success' => true,
                'data' => [
                    'stats' => $stats,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cashIn(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_phone' => ['required', 'string'],
                'amount' => ['required', 'numeric', 'min:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);

            $agent = $request->user()->agent;

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered as an agent',
                ], 403);
            }

            $transaction = $this->transactionService->initiateInternalTransfer(
                $request->user(),
                $agent,
                $validated['customer_phone'],
                (float) $validated['amount'],
                'agent',
                $validated['description'] ?? 'Cash In'
            );

            return response()->json([
                'success' => true,
                'message' => 'Cash in successful',
                'data' => [
                    'transaction' => new TransactionResource($transaction),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cash in failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function cashOut(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_phone' => ['required', 'string'],
                'amount' => ['required', 'numeric', 'min:100'],
                'description' => ['nullable', 'string', 'max:255'],
            ]);

            $agent = $request->user()->agent;

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered as an agent',
                ], 403);
            }

            $transaction = $this->transactionService->initiateInternalTransfer(
                $request->user(),
                $agent,
                $validated['customer_phone'],
                (float) $validated['amount'],
                'agent',
                $validated['description'] ?? 'Cash Out'
            );

            return response()->json([
                'success' => true,
                'message' => 'Cash out successful',
                'data' => [
                    'transaction' => new TransactionResource($transaction),
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cash out failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function earnings(Request $request): JsonResponse
    {
        try {
            $agent = $request->user()->agent;

            if (! $agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not registered as an agent',
                ], 403);
            }

            $earnings = $agent->earnings()
                ->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => [
                    'earnings' => TransactionResource::collection($earnings->items()),
                    'pagination' => [
                        'current_page' => $earnings->currentPage(),
                        'last_page' => $earnings->lastPage(),
                        'per_page' => $earnings->perPage(),
                        'total' => $earnings->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch earnings: ' . $e->getMessage(),
            ], 500);
        }
    }
}
