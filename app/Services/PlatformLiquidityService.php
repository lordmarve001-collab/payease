<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PlatformLiquidityService
{
    public function getCustomerWalletBalances(): float
    {
        return (float) Wallet::query()
            ->where('wallet_type', 'customer')
            ->where('status', 'active')
            ->sum('balance');
    }

    public function getAgentUnsettledObligations(): float
    {
        return (float) Agent::query()
            ->whereIn('status', ['active', 'approved', 'pending'])
            ->sum('float_balance');
    }

    public function getPlatformMasterBalance(): float
    {
        return (float) config('services.platform_liquidity.master_balance', 0);
    }

    public function getAvailablePlatformFunds(): float
    {
        return max(0, $this->getPlatformMasterBalance() - $this->getCustomerWalletBalances() - $this->getAgentUnsettledObligations());
    }

    public function getLiquiditySnapshot(): array
    {
        $customerBalances = $this->getCustomerWalletBalances();
        $agentObligations = $this->getAgentUnsettledObligations();
        $platformFunds = $this->getPlatformMasterBalance();
        $available = max(0, $platformFunds - $customerBalances - $agentObligations);

        return [
            'customer_wallet_balances' => $customerBalances,
            'agent_unsettled_obligations' => $agentObligations,
            'platform_master_balance' => $platformFunds,
            'available_platform_funds' => $available,
            'minimum_threshold' => $this->getMinimumThreshold(),
            'is_healthy' => $available >= $this->getMinimumThreshold(),
        ];
    }

    public function getMinimumThreshold(): float
    {
        return (float) config('services.platform_liquidity.minimum_threshold', 50000);
    }

    public function assertSufficientLiquidity(float $amount): void
    {
        $snapshot = $this->getLiquiditySnapshot();

        if (! $snapshot['is_healthy']) {
            $shortfall = $snapshot['minimum_threshold'] - $snapshot['available_platform_funds'];

            Log::channel('monnify')->alert('Platform liquidity below threshold', [
                'available' => $snapshot['available_platform_funds'],
                'threshold' => $snapshot['minimum_threshold'],
                'shortfall' => $shortfall,
                'requested_amount' => $amount,
            ]);

            throw new RuntimeException(
                'Platform liquidity is below the safety threshold. Disbursement of ₦' . number_format($amount, 2) . ' cannot proceed. Shortfall: ₦' . number_format($shortfall, 2) . '.'
            );
        }

        if ($amount > $snapshot['available_platform_funds']) {
            Log::channel('monnify')->alert('Disbursement exceeds available platform funds', [
                'available' => $snapshot['available_platform_funds'],
                'requested_amount' => $amount,
            ]);

            throw new RuntimeException(
                'Disbursement of ₦' . number_format($amount, 2) . ' exceeds available platform funds of ₦' . number_format($snapshot['available_platform_funds'], 2) . '.'
            );
        }
    }
}
