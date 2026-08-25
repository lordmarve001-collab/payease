<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\AjoController;
use App\Http\Controllers\Api\DisputeController;
use App\Http\Controllers\Api\StatementController;

Route::prefix('v1')->group(function () {
    // Auth (public)
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/request-otp', [AuthController::class, 'requestOtp']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        // User
        Route::get('/user', [UserController::class, 'show']);
        Route::put('/user', [UserController::class, 'update']);
        Route::post('/user/pin', [UserController::class, 'changePin']);

        // Wallet
        Route::get('/wallet', [WalletController::class, 'index']);
        Route::get('/wallet/balance', [WalletController::class, 'balance']);
        Route::get('/wallet/account-info', [WalletController::class, 'accountInfo']);

        // Transactions
        Route::get('/transactions', [TransactionController::class, 'index']);
        Route::get('/transactions/{transaction}', [TransactionController::class, 'show']);
        Route::post('/transactions/transfer', [TransactionController::class, 'transfer']);
        Route::post('/transactions/buy-airtime', [TransactionController::class, 'buyAirtime']);
        Route::post('/transactions/pay-bill', [TransactionController::class, 'payBill']);

        // Disputes
        Route::get('/disputes', [DisputeController::class, 'index']);
        Route::post('/disputes', [DisputeController::class, 'store']);
        Route::get('/disputes/{dispute}', [DisputeController::class, 'show']);

        // Statements
        Route::get('/statements/transactions', [StatementController::class, 'transactions']);
        Route::get('/statements/download', [StatementController::class, 'download']);

        // Agent (agent role only)
        Route::middleware('role:agent')->prefix('agent')->group(function () {
            Route::get('/dashboard', [AgentController::class, 'dashboard']);
            Route::post('/cash-in', [AgentController::class, 'cashIn']);
            Route::post('/cash-out', [AgentController::class, 'cashOut']);
            Route::get('/earnings', [AgentController::class, 'earnings']);
        });

        // Ajo
        Route::get('/ajo/groups', [AjoController::class, 'groups']);
        Route::get('/ajo/groups/{group}', [AjoController::class, 'showGroup']);
        Route::get('/ajo/memberships', [AjoController::class, 'memberships']);
        Route::get('/ajo/memberships/{membership}/contributions', [AjoController::class, 'contributions']);
    });
});
