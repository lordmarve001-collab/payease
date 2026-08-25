<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Services\BillPaymentService;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function __construct(
        private TransactionService $transactionService,
        private BillPaymentService $billPaymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'type' => ['nullable', 'string', 'in:credit,debit,transfer,airtime,bill_payment,cash_in,cash_out'],
                'status' => ['nullable', 'string', 'in:pending,successful,failed,reversed'],
                'start_date' => ['nullable', 'date'],
                'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $query = $request->user()->transactions()->with([]);

            if (! empty($validated['type'])) {
                $query->where('transaction_type', $validated['type']);
            }

            if (! empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (! empty($validated['start_date'])) {
                $query->where('created_at', '>=', $validated['start_date']);
            }

            if (! empty($validated['end_date'])) {
                $query->where('created_at', '<=', $validated['end_date'] . ' 23:59:59');
            }

            $transactions = $query->orderBy('created_at', 'desc')
                ->paginate($validated['per_page'] ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'transactions' => TransactionResource::collection($transactions->items()),
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                    ],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch transactions: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $transaction = $request->user()->transactions()->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction' => new TransactionResource($transaction),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }
    }

    public function transfer(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'recipient_phone' => ['required', 'string'],
                'amount' => ['required', 'numeric', 'min:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'pin' => ['required', 'string', 'digits:4'],
            ]);

            $user = $request->user();

            if (! $user->verifyPin($validated['pin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction PIN',
                ], 422);
            }

            $transaction = $this->transactionService->initiateTransfer(
                $user,
                $validated['recipient_phone'],
                (float) $validated['amount'],
                $validated['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Transfer successful',
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
                'message' => 'Transfer failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function buyAirtime(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'phone_number' => ['required', 'string'],
                'amount' => ['required', 'numeric', 'min:50'],
                'network' => ['required', 'string', 'in:mtn,airtel,glo,9mobile'],
                'pin' => ['required', 'string', 'digits:4'],
            ]);

            $user = $request->user();

            if (! $user->verifyPin($validated['pin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction PIN',
                ], 422);
            }

            $transaction = $this->billPaymentService->buyAirtime(
                $user,
                $validated['phone_number'],
                (float) $validated['amount'],
                $validated['network']
            );

            return response()->json([
                'success' => true,
                'message' => 'Airtime purchase successful',
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
                'message' => 'Airtime purchase failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function payBill(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'biller' => ['required', 'string'],
                'customer_id' => ['required', 'string'],
                'amount' => ['required', 'numeric', 'min:100'],
                'description' => ['nullable', 'string', 'max:255'],
                'pin' => ['required', 'string', 'digits:4'],
            ]);

            $user = $request->user();

            if (! $user->verifyPin($validated['pin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction PIN',
                ], 422);
            }

            $transaction = $this->billPaymentService->payBill(
                $user,
                $validated['biller'],
                $validated['customer_id'],
                (float) $validated['amount'],
                $validated['description'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Bill payment successful',
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
                'message' => 'Bill payment failed: ' . $e->getMessage(),
            ], 422);
        }
    }
}
