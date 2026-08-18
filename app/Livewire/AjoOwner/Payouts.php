<?php

namespace App\Livewire\AjoOwner;

use App\Models\AjoGroup;
use App\Models\AjoOwner;
use App\Models\AjoPayout;
use App\Models\User;
use App\Services\AjoService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Livewire\Component;

class Payouts extends Component
{
    public string $filter = 'upcoming';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var AjoOwner $ajoOwner */
        $ajoOwner = $user->ajoOwner;
        /** @var AjoService $ajoService */
        $ajoService = app(AjoService::class);

        $groups = AjoGroup::query()
            ->where('ajo_owner_id', $ajoOwner->id)
            ->with(['members.user', 'payouts'])
            ->get();
        $groupIds = $groups->pluck('id');

        $completedPayouts = AjoPayout::query()
            ->whereIn('group_id', $groupIds)
            ->with(['group', 'user'])
            ->where('status', 'completed')
            ->latest()
            ->get()
            ->map(function (AjoPayout $payout): array {
                return [
                    'id' => $payout->id,
                    'group_name' => $payout->group?->name ?? 'Unknown Group',
                    'recipient_name' => $payout->user?->full_name ?? 'Member',
                    'amount' => (float) $payout->amount,
                    'scheduled_date' => $payout->created_at,
                    'status' => 'completed',
                    'cycle_number' => $payout->cycle_number,
                ];
            });

        $openPayouts = $groups
            ->map(function (AjoGroup $group) use ($ajoService): array {
                $nextPayout = $ajoService->getNextPayout($group);
                $recipient = $nextPayout['recipient'] ?? null;

                return [
                    'id' => $group->id . ':' . $nextPayout['cycle_number'],
                    'group_name' => $group->name,
                    'recipient_name' => $group->isSavingsPool() || $group->isContinuousPool()
                        ? 'All members (proportional)'
                        : ($recipient?->full_name ?? 'No eligible member'),
                    'amount' => (float) ($nextPayout['amount'] ?? $nextPayout['total_collected'] ?? 0),
                    'scheduled_date' => $nextPayout['scheduled_date'] ?? now(),
                    'status' => $nextPayout['status'] ?? 'upcoming',
                    'cycle_number' => $nextPayout['cycle_number'],
                ];
            })
            ->filter(fn (array $row): bool => in_array($row['status'], ['upcoming', 'overdue'], true))
            ->sortBy('scheduled_date')
            ->values();

        $payouts = match ($this->filter) {
            'completed' => $completedPayouts,
            'overdue' => $openPayouts->where('status', 'overdue')->values(),
            default => $openPayouts->where('status', 'upcoming')->values(),
        };

        $totalPool = $groups->sum(function (AjoGroup $group): float {
            return (float) $group->contribution_amount * (int) $group->members_count;
        });

        return view('livewire.ajo-owner.payouts', [
            'payouts' => $payouts,
            'totalPool' => $totalPool
        ])->layout('components.layouts.ajo-owner');
    }
}
