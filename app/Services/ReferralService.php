<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public const REWARD_AMOUNT = 100.00;

    public function generateReferralCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        $code = strtoupper(substr(md5($user->id . now()->timestamp), 0, 8));

        $user->update(['referral_code' => $code]);

        return $code;
    }

    public function registerReferral(User $referrer, User $referred): ?Referral
    {
        if ($referrer->id === $referred->id) {
            return null;
        }

        $existing = Referral::where('referrer_id', $referrer->id)
            ->where('referred_id', $referred->id)
            ->first();

        if ($existing) {
            return null;
        }

        return Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referred->id,
            'status' => 'pending',
        ]);
    }

    public function qualifyReferral(User $referred): bool
    {
        $referral = Referral::where('referred_id', $referred->id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return false;
        }

        if ($this->hasQualified($referred)) {
            $referral->qualify(self::REWARD_AMOUNT);
            $this->creditReward($referral);
            return true;
        }

        return false;
    }

    protected function hasQualified(User $user): bool
    {
        $completedTransactions = $user->wallets()
            ->where('wallet_type', 'customer')
            ->first()
            ?->fromTransactions()
            ->where('status', 'completed')
            ->where('transaction_type', 'bank_transfer_deposit')
            ->where('amount', '>=', 100)
            ->exists();

        return $completedTransactions;
    }

    protected function creditReward(Referral $referral): void
    {
        $referrer = $referral->referrer;
        $wallet = $referrer->wallets()->where('wallet_type', 'customer')->first();

        if (!$wallet) {
            $referral->update(['reward_status' => 'failed']);
            return;
        }

        DB::transaction(function () use ($wallet, $referral) {
            $lockedWallet = Wallet::query()
                ->whereKey($wallet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedWallet->balance = round((float) $lockedWallet->balance + $referral->reward_amount, 2);
            $lockedWallet->available_balance = round((float) $lockedWallet->available_balance + $referral->reward_amount, 2);
            $lockedWallet->save();

            $referral->reward();
        });
    }

    public function getReferralStats(User $user): array
    {
        $totalReferrals = Referral::where('referrer_id', $user->id)->count();
        $qualified = Referral::where('referrer_id', $user->id)->where('status', 'qualified')->count();
        $rewarded = Referral::where('referrer_id', $user->id)->where('status', 'rewarded')->count();
        $totalEarned = Referral::where('referrer_id', $user->id)
            ->where('reward_status', 'paid')
            ->sum('reward_amount');

        return [
            'total_referrals' => $totalReferrals,
            'qualified' => $qualified,
            'rewarded' => $rewarded,
            'total_earned' => (float) $totalEarned,
        ];
    }
}
