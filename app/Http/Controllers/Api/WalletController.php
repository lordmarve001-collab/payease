<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private WalletService $walletService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $wallets = $request->user()->wallets()->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'wallets' => WalletResource::collection($wallets),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch wallets: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function balance(Request $request): JsonResponse
    {
        try {
            $wallet = $request->user()->wallets()->where('wallet_type', 'main')->first();

            if (! $wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'No wallet found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'wallet' => new WalletResource($wallet),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function accountInfo(Request $request): JsonResponse
    {
        try {
            $wallet = $request->user()->wallets()->where('wallet_type', 'main')->first();

            if (! $wallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'No wallet found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'account_number' => $wallet->account_number,
                    'status' => $wallet->status,
                    'daily_limit' => $wallet->daily_limit,
                    'single_txn_limit' => $wallet->single_txn_limit,
                    'daily_used' => $wallet->daily_used ?? 0,
                    'daily_remaining' => max(0, ($wallet->daily_limit ?? 0) - ($wallet->daily_used ?? 0)),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch account info: ' . $e->getMessage(),
            ], 500);
        }
    }
}
