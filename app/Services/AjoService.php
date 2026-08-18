<?php

namespace App\Services;

use App\Events\AgentFloatBalanceDroppedLow;
use App\Events\TransactionCompleted;
use App\Models\Agent;
use App\Models\AjoContribution;
use App\Models\AjoGroup;
use App\Models\AjoMember;
use App\Models\AjoOwner;
use App\Models\AjoPayout;
use App\Models\AjoPayoutQueue;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AjoService
{
    protected const COMMISSION_RATE = 0.01;

    public function createGroup(AjoOwner $owner, array $data, Agent $managingAgent, array $additionalAgentIds = []): AjoGroup
    {
        if ($managingAgent->ajo_owner_id !== null && $managingAgent->ajo_owner_id !== $owner->id) {
            throw new RuntimeException('This agent is already assigned to another Ajo owner.');
        }

        return DB::transaction(function () use ($owner, $data, $managingAgent, $additionalAgentIds): AjoGroup {
            $group = AjoGroup::create([
                'ajo_owner_id' => $owner->id,
                'managing_agent_id' => $managingAgent->id,
                'name' => trim((string) ($data['name'] ?? '')),
                'model_type' => $data['model_type'] ?? 'rotational',
                'description' => $data['description'] ?? null,
                'contribution_amount' => (float) ($data['contribution_amount'] ?? 0),
                'owner_fee_percentage' => (float) ($data['owner_fee_percentage'] ?? 0),
                'collection_period_days' => $data['collection_period_days'] ?? null,
                'collection_end_date' => $data['collection_end_date'] ?? null,
                'min_contribution' => $data['min_contribution'] ?? null,
                'max_contribution' => $data['max_contribution'] ?? null,
                'target_pool_amount' => $data['target_pool_amount'] ?? null,
                'frequency' => strtolower(trim((string) ($data['frequency'] ?? 'weekly'))),
                'members_count' => (int) ($data['members_count'] ?? 0),
                'payout_order' => strtolower(trim((string) ($data['payout_order'] ?? 'fixed'))),
                'status' => 'pending',
            ]);

            $allAgentIds = array_unique(array_merge([$managingAgent->id], $additionalAgentIds));
            foreach ($allAgentIds as $agentId) {
                $group->agents()->attach($agentId, ['role' => $agentId === $managingAgent->id ? 'managing_agent' : 'field_agent']);
            }

            if ($managingAgent->ajo_owner_id === null) {
                $managingAgent->update([
                    'ajo_owner_id' => $owner->id,
                ]);
            }

            return $group->fresh(['managingAgent.user', 'agents.user']);
        });
    }

    public function addMember(AjoGroup $group, User $user, int $position = null): AjoMember
    {
        return DB::transaction(function () use ($group, $user, $position): AjoMember {
            $lockedGroup = AjoGroup::query()
                ->with('members')
                ->lockForUpdate()
                ->findOrFail($group->id);

            if ($lockedGroup->members()->count() >= (int) $lockedGroup->members_count) {
                throw new RuntimeException('This group already has its full member count.');
            }

            if ($lockedGroup->members()->where('user_id', $user->id)->exists()) {
                throw new RuntimeException('This member is already part of the group.');
            }

            $resolvedPosition = null;
            if ($lockedGroup->payout_order === 'fixed') {
                $resolvedPosition = $position ?? ((int) $lockedGroup->members()->max('position') + 1);

                if ($lockedGroup->members()->where('position', $resolvedPosition)->exists()) {
                    throw new RuntimeException('That payout position is already assigned.');
                }
            }

            $member = AjoMember::create([
                'group_id' => $lockedGroup->id,
                'user_id' => $user->id,
                'position' => $resolvedPosition,
                'status' => 'active',
            ]);

            $memberCount = $lockedGroup->members()->count();
            if ($memberCount >= (int) $lockedGroup->members_count) {
                $lockedGroup->update([
                    'status' => 'active',
                    'start_date' => $lockedGroup->start_date ?? today(),
                ]);

                if ($lockedGroup->payout_order === 'random') {
                    $this->assignRandomPositions($lockedGroup);
                }
            }

            return $member->fresh(['user', 'group']);
        });
    }

    public function logContribution(Agent $agent, AjoGroup $group, User $member, float $amount): AjoContribution
    {
        $group->loadMissing('agents');
        $isAssignedAgent = $group->agents->contains('id', $agent->id);

        if (!$isAssignedAgent && $group->managing_agent_id !== $agent->id) {
            throw new RuntimeException('Only agents assigned to this group can log contributions.');
        }

        $isFixedAmount = $group->isRotational();
        $isFlexibleAmount = $group->isSavingsPool() || $group->isContinuousPool();

        if ($isFixedAmount && (float) $amount !== (float) $group->contribution_amount) {
            throw new RuntimeException('Ajo contributions must match the group contribution amount exactly.');
        }

        if ($isFlexibleAmount) {
            if ((float) $amount <= 0) {
                throw new RuntimeException('Contribution amount must be greater than zero.');
            }
            if ($group->min_contribution !== null && (float) $amount < (float) $group->min_contribution) {
                throw new RuntimeException('Minimum contribution is ₦' . number_format((float) $group->min_contribution, 2) . '.');
            }
            if ($group->max_contribution !== null && (float) $amount > (float) $group->max_contribution) {
                throw new RuntimeException('Maximum contribution is ₦' . number_format((float) $group->max_contribution, 2) . '.');
            }
            if ($group->collection_end_date && Carbon::parse($group->collection_end_date)->isPast()) {
                throw new RuntimeException('The collection period for this group has ended.');
            }
        }

        $contribution = DB::transaction(function () use ($agent, $group, $member, $amount): AjoContribution {
            $lockedGroup = AjoGroup::query()
                ->with('members')
                ->lockForUpdate()
                ->findOrFail($group->id);
            $lockedAgent = Agent::query()->lockForUpdate()->findOrFail($agent->id);

            if ($lockedGroup->status !== 'active') {
                throw new RuntimeException('This group is not active yet.');
            }

            $lockedGroup->load('agents');
            $isAssigned = $lockedGroup->agents->contains('id', $lockedAgent->id);
            if (!$isAssigned && $lockedGroup->managing_agent_id !== $lockedAgent->id) {
                throw new RuntimeException('Only agents assigned to this group can log contributions.');
            }

            $groupMember = $lockedGroup->members()
                ->where('user_id', $member->id)
                ->where('status', 'active')
                ->first();

            if (!$groupMember) {
                throw new RuntimeException('This user is not an active member of the selected group.');
            }

            $cycleNumber = $this->getCurrentCycleNumber($lockedGroup);
            $hasPaid = AjoContribution::query()
                ->where('group_id', $lockedGroup->id)
                ->where('user_id', $member->id)
                ->where('cycle_number', $cycleNumber)
                ->exists();

            if ($hasPaid) {
                throw new RuntimeException('This member has already paid for the current cycle.');
            }

            $commission = round($amount * self::COMMISSION_RATE, 2);

            $transaction = Transaction::create([
                'reference' => 'AJOC' . Str::upper(Str::random(10)),
                'transaction_type' => 'ajo_contribution',
                'amount' => $amount,
                'commission' => $commission,
                'status' => 'completed',
                'agent_id' => $lockedAgent->user_id,
                'recipient_phone' => $member->phone_number,
                'description' => 'Ajo contribution for ' . $lockedGroup->name,
                'mmo_partner' => 'internal',
                'metadata' => [
                    'group_id' => $lockedGroup->id,
                    'group_name' => $lockedGroup->name,
                    'member_user_id' => $member->id,
                    'cycle_number' => $cycleNumber,
                    'agent_model_id' => $lockedAgent->id,
                ],
                'completed_at' => now(),
            ]);

            $contribution = AjoContribution::create([
                'group_id' => $lockedGroup->id,
                'user_id' => $member->id,
                'logged_by_agent_id' => $lockedAgent->id,
                'amount' => $amount,
                'cycle_number' => $cycleNumber,
                'status' => 'completed',
                'transaction_id' => $transaction->id,
            ]);

            $lockedAgent->update([
                'float_balance' => round((float) $lockedAgent->float_balance + $amount, 2),
                'total_earnings' => round((float) $lockedAgent->total_earnings + $commission, 2),
            ]);

            return $contribution->fresh(['user', 'transaction', 'loggedByAgent.user']);
        });

        if ($contribution->transaction) {
            event(new TransactionCompleted($contribution->transaction->fresh(['fromWallet.user', 'toWallet.user', 'agentUser'])));
        }

        return $contribution;
    }

    public function getCycleProgress(AjoGroup $group): array
    {
        $group->loadMissing('members');

        $cycleNumber = $this->getCurrentCycleNumber($group);
        $paidCount = AjoContribution::query()
            ->where('group_id', $group->id)
            ->where('cycle_number', $cycleNumber)
            ->count();
        $amountCollected = (float) AjoContribution::query()
            ->where('group_id', $group->id)
            ->where('cycle_number', $cycleNumber)
            ->sum('amount');
        $targetMembers = (int) $group->members_count;

        if ($group->isContinuousPool()) {
            $targetAmount = (float) ($group->target_pool_amount ?: 0);
            $totalIntervals = $this->getTotalIntervals($group);
            $elapsedIntervals = max(0, $cycleNumber - 1);
            $intervalProgress = $totalIntervals > 0 ? (int) min(100, round(($elapsedIntervals / $totalIntervals) * 100)) : 0;
            return [
                'cycle_number' => $cycleNumber,
                'paid_members' => $paidCount,
                'total_members' => $targetMembers,
                'amount_collected' => $amountCollected,
                'target_amount' => $targetAmount,
                'percentage' => $intervalProgress,
                'total_intervals' => $totalIntervals,
                'elapsed_intervals' => $elapsedIntervals,
                'is_complete' => $cycleNumber >= $totalIntervals,
            ];
        }

        if ($group->isSavingsPool()) {
            $targetAmount = (float) ($group->target_pool_amount ?: 0);
            return [
                'cycle_number' => $cycleNumber,
                'paid_members' => $paidCount,
                'total_members' => $targetMembers,
                'amount_collected' => $amountCollected,
                'target_amount' => $targetAmount,
                'percentage' => $targetAmount > 0
                    ? (int) min(100, round(($amountCollected / $targetAmount) * 100))
                    : ($targetMembers > 0 ? (int) round(($paidCount / $targetMembers) * 100) : 0),
                'is_complete' => $group->collection_end_date && Carbon::parse($group->collection_end_date)->isPast(),
            ];
        }

        $targetAmount = round((float) $group->contribution_amount * $targetMembers, 2);

        return [
            'cycle_number' => $cycleNumber,
            'paid_members' => $paidCount,
            'total_members' => $targetMembers,
            'amount_collected' => $amountCollected,
            'target_amount' => $targetAmount,
            'percentage' => $targetMembers > 0 ? (int) round(($paidCount / $targetMembers) * 100) : 0,
            'is_complete' => $targetMembers > 0 && $paidCount >= $targetMembers,
        ];
    }

    public function getNextPayout(AjoGroup $group): array
    {
        $group->loadMissing(['members.user', 'payouts']);

        if ($group->isSavingsPool() || $group->isContinuousPool()) {
            return $this->getSavingsPoolPayout($group);
        }

        $eligibleMembers = $group->members
            ->where('status', '!=', 'defaulted')
            ->sortBy('position')
            ->values();

        $cycleNumber = $this->getCurrentCycleNumber($group);
        $scheduledDate = $this->getScheduledDate($group, $cycleNumber);
        $payoutAmount = round((float) $group->contribution_amount * (int) $group->members_count, 2);
        $completedPayout = $group->payouts
            ->where('cycle_number', $cycleNumber)
            ->where('status', 'completed')
            ->first();

        $recipient = null;
        if ($eligibleMembers->isNotEmpty()) {
            $index = ($cycleNumber - 1) % $eligibleMembers->count();
            $recipient = $eligibleMembers->get($index)?->user;
        }

        $status = 'upcoming';
        if ($completedPayout) {
            $status = 'completed';
        } elseif ($scheduledDate->lt(today())) {
            $status = 'overdue';
        }

        return [
            'cycle_number' => $cycleNumber,
            'recipient' => $recipient,
            'scheduled_date' => $scheduledDate,
            'amount' => $payoutAmount,
            'status' => $status,
            'is_due_within_48_hours' => !$completedPayout && $scheduledDate->lte(now()->addHours(48)),
        ];
    }

    public function getSavingsPoolPayout(AjoGroup $group): array
    {
        $cycleNumber = $this->getCurrentCycleNumber($group);
        $startDate = $group->start_date ? Carbon::parse($group->start_date) : Carbon::now();
        $intervalDays = $this->getIntervalDays($group->frequency);
        $collectionEndDate = $group->isContinuousPool()
            ? $startDate->copy()->addDays($intervalDays)
            : ($group->collection_end_date ? Carbon::parse($group->collection_end_date) : null);

        $totalCollected = (float) AjoContribution::query()
            ->where('group_id', $group->id)
            ->where('cycle_number', $cycleNumber)
            ->sum('amount');

        $ownerFeePercent = (float) $group->owner_fee_percentage;
        $ownerFee = round($totalCollected * ($ownerFeePercent / 100), 2);
        $poolAfterFee = round($totalCollected - $ownerFee, 2);

        $contributingMembers = AjoContribution::query()
            ->where('group_id', $group->id)
            ->where('cycle_number', $cycleNumber)
            ->with('user')
            ->get();

        $totalContributions = (float) $contributingMembers->sum('amount');

        $memberPayouts = [];
        if ($totalContributions > 0) {
            foreach ($contributingMembers as $contribution) {
                $share = round(($contribution->amount / $totalContributions) * $poolAfterFee, 2);
                $memberPayouts[] = [
                    'user_id' => $contribution->user_id,
                    'user_name' => $contribution->user?->full_name ?? 'Unknown',
                    'contributed' => (float) $contribution->amount,
                    'payout_share' => $share,
                ];
            }
        }

        $completedPayout = $group->payouts
            ->where('cycle_number', $cycleNumber)
            ->where('status', 'completed')
            ->first();

        $status = 'collecting';
        if ($completedPayout) {
            $status = 'completed';
        } elseif ($collectionEndDate && $collectionEndDate->isPast()) {
            $status = 'ready_for_payout';
        }

        return [
            'cycle_number' => $cycleNumber,
            'recipient' => null,
            'scheduled_date' => $collectionEndDate,
            'amount' => $totalCollected,
            'total_collected' => $totalCollected,
            'owner_fee_percentage' => $ownerFeePercent,
            'owner_fee' => $ownerFee,
            'pool_after_fee' => $poolAfterFee,
            'member_payouts' => $memberPayouts,
            'contributing_members_count' => $contributingMembers->count(),
            'status' => $status,
            'is_collection_over' => $collectionEndDate && $collectionEndDate->isPast(),
            'is_due_within_48_hours' => $collectionEndDate && !$completedPayout && $collectionEndDate->lte(now()->addHours(48)),
            'days_remaining' => $collectionEndDate ? max(0, (int) today()->diffInDays($collectionEndDate, false)) : null,
        ];
    }

    public function processPayout(AjoGroup $group, User $recipient, Agent $agent, int $cycleNumber): Transaction
    {
        $wasLowFloat = $this->isBelowLowFloatThreshold($agent);

        $transaction = DB::transaction(function () use ($group, $recipient, $agent, $cycleNumber): Transaction {
            $lockedGroup = AjoGroup::query()
                ->with(['members.user', 'payouts'])
                ->lockForUpdate()
                ->findOrFail($group->id);
            $lockedAgent = Agent::query()->lockForUpdate()->findOrFail($agent->id);

            $lockedGroup->load('agents');
            $isAssigned = $lockedGroup->agents->contains('id', $lockedAgent->id);
            if (!$isAssigned && $lockedGroup->managing_agent_id !== $lockedAgent->id) {
                throw new RuntimeException('Only agents assigned to this group can process payouts.');
            }

            $currentCycle = $this->getCurrentCycleNumber($lockedGroup);
            if ($cycleNumber !== $currentCycle) {
                throw new RuntimeException('This payout is no longer for the current cycle.');
            }

            if (AjoPayout::query()->where('group_id', $lockedGroup->id)->where('cycle_number', $cycleNumber)->exists()) {
                throw new RuntimeException('This cycle payout has already been processed.');
            }

            $activeMembersCount = $lockedGroup->members()->where('status', 'active')->count();
            $paidMembersCount = AjoContribution::query()
                ->where('group_id', $lockedGroup->id)
                ->where('cycle_number', $cycleNumber)
                ->count();

            if ($paidMembersCount < $activeMembersCount) {
                throw new RuntimeException('This cycle is not fully collected yet.');
            }

            $nextPayout = $this->getNextPayout($lockedGroup);
            /** @var User|null $expectedRecipient */
            $expectedRecipient = $nextPayout['recipient'];
            if (!$expectedRecipient || !$expectedRecipient->is($recipient)) {
                throw new RuntimeException('This payout recipient is not next in the rotation.');
            }

            $payoutAmount = (float) $nextPayout['amount'];
            if ((float) $lockedAgent->float_balance < $payoutAmount) {
                throw new RuntimeException('The managing agent does not have enough float for this payout.');
            }

            $transaction = Transaction::create([
                'reference' => 'AJOP' . Str::upper(Str::random(10)),
                'transaction_type' => 'ajo_payout',
                'amount' => $payoutAmount,
                'status' => 'completed',
                'agent_id' => $lockedAgent->user_id,
                'recipient_phone' => $recipient->phone_number,
                'description' => 'Ajo payout for ' . $lockedGroup->name,
                'mmo_partner' => 'internal',
                'metadata' => [
                    'group_id' => $lockedGroup->id,
                    'group_name' => $lockedGroup->name,
                    'member_user_id' => $recipient->id,
                    'cycle_number' => $cycleNumber,
                    'cash_handover' => true,
                    'wallet_credit_applied' => false,
                    'agent_model_id' => $lockedAgent->id,
                ],
                'completed_at' => now(),
            ]);

            $lockedAgent->update([
                'float_balance' => round((float) $lockedAgent->float_balance - $payoutAmount, 2),
            ]);

            $payout = AjoPayout::create([
                'group_id' => $lockedGroup->id,
                'user_id' => $recipient->id,
                'amount' => $payoutAmount,
                'cycle_number' => $cycleNumber,
                'status' => 'completed',
                'transaction_id' => $transaction->id,
            ]);

            AjoPayoutQueue::create([
                'ajo_payout_id' => $payout->id,
                'group_id' => $lockedGroup->id,
                'member_user_id' => $recipient->id,
                'agent_id' => $lockedAgent->id,
                'amount' => $payoutAmount,
                'cycle_number' => $cycleNumber,
                'status' => 'pending',
            ]);

            return $transaction->fresh(['agentUser']);
        });

        event(new TransactionCompleted($transaction->fresh(['fromWallet.user', 'toWallet.user', 'agentUser'])));
        $this->dispatchLowFloatAlertIfCrossed($agent->fresh(), $wasLowFloat);

        return $transaction;
    }

    public function getCurrentCycleNumber(AjoGroup $group): int
    {
        if ($group->isContinuousPool()) {
            $startDate = $group->start_date ? Carbon::parse($group->start_date) : Carbon::now();
            $intervalDays = $this->getIntervalDays($group->frequency);
            $daysSinceStart = (int) $startDate->diffInDays(Carbon::now(), false);
            $cycleNumber = (int) floor($daysSinceStart / $intervalDays) + 1;
            $totalIntervals = $this->getTotalIntervals($group);
            return min($cycleNumber, $totalIntervals);
        }

        return (int) AjoPayout::query()
            ->where('group_id', $group->id)
            ->where('status', 'completed')
            ->count() + 1;
    }

    public function getContributionHistory(AjoGroup $group): Collection
    {
        return AjoContribution::query()
            ->with(['user', 'loggedByAgent.user'])
            ->where('group_id', $group->id)
            ->orderByDesc('cycle_number')
            ->orderBy('created_at')
            ->get()
            ->groupBy('cycle_number');
    }

    public function getMembersPendingForCurrentCycle(AjoGroup $group): Collection
    {
        $cycleNumber = $this->getCurrentCycleNumber($group);
        $paidMemberIds = AjoContribution::query()
            ->where('group_id', $group->id)
            ->where('cycle_number', $cycleNumber)
            ->pluck('user_id');

        return $group->members()
            ->with('user')
            ->where('status', 'active')
            ->whereNotIn('user_id', $paidMemberIds)
            ->orderBy('position')
            ->get();
    }

    protected function assignRandomPositions(AjoGroup $group): void
    {
        $members = $group->members()->where('status', 'active')->get()->shuffle()->values();

        foreach ($members as $index => $member) {
            $member->update([
                'position' => $index + 1,
            ]);
        }
    }

    public function getIntervalDays(string $frequency): int
    {
        return match (strtolower($frequency)) {
            'daily' => 1,
            'every_2_days', 'every2days', 'every_2' => 2,
            'every_3_days', 'every3days', 'every_3' => 3,
            'every_5_days', 'every5days', 'every_5' => 5,
            'weekly' => 7,
            'biweekly', 'bi_weekly' => 14,
            'monthly' => 30,
            default => 7,
        };
    }

    public function getTotalIntervals(AjoGroup $group): int
    {
        $collectionPeriodDays = (int) ($group->collection_period_days ?: 30);
        $intervalDays = $this->getIntervalDays($group->frequency);
        return max(1, (int) ceil($collectionPeriodDays / $intervalDays));
    }

    public function isFlexibleContribution(AjoGroup $group): bool
    {
        return $group->isSavingsPool() || $group->isContinuousPool();
    }

    protected function getScheduledDate(AjoGroup $group, int $cycleNumber): Carbon
    {
        $startDate = $group->start_date
            ? Carbon::parse($group->start_date)
            : today();

        if ($group->isContinuousPool()) {
            $intervalDays = $this->getIntervalDays($group->frequency);
            return $startDate->copy()->addDays($intervalDays * ($cycleNumber - 1));
        }

        return match (strtolower((string) $group->frequency)) {
            'daily' => $startDate->copy()->addDays($cycleNumber - 1),
            'monthly' => $startDate->copy()->addMonthsNoOverflow($cycleNumber - 1),
            default => $startDate->copy()->addWeeks($cycleNumber - 1),
        };
    }

    protected function dispatchLowFloatAlertIfCrossed(?Agent $agent, bool $wasLowFloat): void
    {
        if (!$agent) {
            return;
        }

        if (!$wasLowFloat && $this->isBelowLowFloatThreshold($agent)) {
            event(new AgentFloatBalanceDroppedLow($agent));
        }
    }

    protected function isBelowLowFloatThreshold(Agent $agent): bool
    {
        return (float) $agent->float_balance < ((float) $agent->max_float * 0.2);
    }
}
