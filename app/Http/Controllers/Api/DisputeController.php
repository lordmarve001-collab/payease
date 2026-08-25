<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DisputeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]);

            $disputes = Dispute::where('user_id', $request->user()->id)
                ->with('transaction')
                ->orderBy('created_at', 'desc')
                ->paginate($validated['per_page'] ?? 20);

            return response()->json([
                'success' => true,
                'data' => [
                    'disputes' => $disputes->items(),
                    'pagination' => [
                        'current_page' => $disputes->currentPage(),
                        'last_page' => $disputes->lastPage(),
                        'per_page' => $disputes->perPage(),
                        'total' => $disputes->total(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch disputes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'transaction_id' => ['required', 'string', 'exists:transactions,id'],
                'reason' => ['required', 'string', 'max:500'],
                'description' => ['nullable', 'string', 'max:2000'],
            ]);

            $user = $request->user();
            $transaction = Transaction::findOrFail($validated['transaction_id']);

            if ($transaction->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only dispute your own transactions',
                ], 403);
            }

            $existingDispute = Dispute::where('transaction_id', $transaction->id)
                ->where('status', '!=', 'resolved')
                ->exists();

            if ($existingDispute) {
                return response()->json([
                    'success' => false,
                    'message' => 'An open dispute already exists for this transaction',
                ], 422);
            }

            $dispute = Dispute::create([
                'user_id' => $user->id,
                'transaction_id' => $transaction->id,
                'reason' => $validated['reason'],
                'description' => $validated['description'] ?? null,
                'status' => 'open',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dispute submitted successfully',
                'data' => [
                    'dispute' => $dispute->load('transaction'),
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
                'message' => 'Failed to create dispute: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $dispute = Dispute::where('user_id', $request->user()->id)
                ->with('transaction')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'dispute' => $dispute,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dispute not found',
            ], 404);
        }
    }
}
