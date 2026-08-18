<?php

namespace App\Livewire\AjoOwner;

use App\Models\Agent;
use App\Models\User;
use App\Services\AjoService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use RuntimeException;

class GroupCreate extends Component
{
    public int $step = 1;

    // Step 1: Model Type
    public string $modelType = 'savings_pool';

    // Step 2: Group Details
    public string $name = '';
    public string $description = '';
    public string $amount = '';
    public string $frequency = 'monthly';
    public string $membersCount = '10';
    public string $payoutOrder = 'fixed';
    public string $ownerFee = '5';
    public string $collectionPeriodDays = '30';
    public string $minContribution = '';
    public string $maxContribution = '';
    public string $targetPool = '';
    public string $contributionInterval = 'weekly';

    // Step 3: Agents (multi-select)
    public string $searchAgent = '';
    /** @var list<string> */
    public array $selectedAgentIds = [];
    public int $agentPage = 1;

    // State
    public bool $isLoading = false;
    public ?string $createdGroupId = null;

    public function updatedAmount($value): void
    {
        $this->amount = preg_replace('/[^0-9]/', '', (string) $value) ?? '';
    }

    public function updatedMinContribution($value): void
    {
        $this->minContribution = preg_replace('/[^0-9]/', '', (string) $value) ?? '';
    }

    public function updatedMaxContribution($value): void
    {
        $this->maxContribution = preg_replace('/[^0-9]/', '', (string) $value) ?? '';
    }

    public function updatedTargetPool($value): void
    {
        $this->targetPool = preg_replace('/[^0-9]/', '', (string) $value) ?? '';
    }

    public function updatedOwnerFee($value): void
    {
        $this->ownerFee = preg_replace('/[^0-9.]/', '', (string) $value) ?? '';
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate(['modelType' => ['required', 'in:rotational,savings_pool,continuous_pool']]);
            $this->step = 2;
            return;
        }

        if ($this->step === 2) {
            if ($this->modelType === 'rotational') {
                $this->validate([
                    'name' => ['required', 'string', 'min:3'],
                    'amount' => ['required', 'numeric', 'min:100'],
                    'frequency' => ['required', 'in:daily,weekly,monthly'],
                    'membersCount' => ['required', 'integer', 'min:2', 'max:100'],
                    'payoutOrder' => ['required', 'in:fixed,random'],
                ]);
            } elseif ($this->modelType === 'continuous_pool') {
                $this->validate([
                    'name' => ['required', 'string', 'min:3'],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'ownerFee' => ['required', 'numeric', 'min:0', 'max:50'],
                    'contributionInterval' => ['required', 'in:daily,every_2_days,every_3_days,every_5_days,weekly,biweekly,monthly'],
                    'collectionPeriodDays' => ['required', 'integer', 'min:1', 'max:365'],
                    'membersCount' => ['required', 'integer', 'min:2', 'max:1000'],
                    'minContribution' => ['nullable', 'numeric', 'min:0'],
                    'maxContribution' => ['nullable', 'numeric', 'gte:minContribution'],
                    'targetPool' => ['nullable', 'numeric', 'min:0'],
                ]);
            } else {
                $this->validate([
                    'name' => ['required', 'string', 'min:3'],
                    'description' => ['nullable', 'string', 'max:1000'],
                    'ownerFee' => ['required', 'numeric', 'min:0', 'max:50'],
                    'collectionPeriodDays' => ['required', 'integer', 'min:1', 'max:365'],
                    'membersCount' => ['required', 'integer', 'min:2', 'max:1000'],
                    'minContribution' => ['nullable', 'numeric', 'min:0'],
                    'maxContribution' => ['nullable', 'numeric', 'gte:minContribution'],
                    'targetPool' => ['nullable', 'numeric', 'min:0'],
                ]);
            }
            $this->step = 3;
            return;
        }

        if ($this->step === 3) {
            $this->validate([
                'selectedAgentIds' => ['required', 'array', 'min:1'],
                'selectedAgentIds.0' => ['required', 'uuid'],
            ], [
                'selectedAgentIds.required' => 'Select at least one agent to continue.',
                'selectedAgentIds.min' => 'Select at least one agent to continue.',
            ]);
            $this->step = 4;
        }
    }

    public function prevStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function toggleAgent(string $agentId): void
    {
        if (in_array($agentId, $this->selectedAgentIds, true)) {
            $this->selectedAgentIds = array_values(array_diff($this->selectedAgentIds, [$agentId]));
        } else {
            $this->selectedAgentIds[] = $agentId;
        }
    }

    public function gotoAgentPage(int $page): void
    {
        $this->agentPage = $page;
    }

    public function createGroup(): void
    {
        $this->isLoading = true;

        try {
            /** @var User $user */
            $user = Auth::user();
            $owner = $user->ajoOwner;

            $allAgents = $this->getAssignableAgents()->getCollection();
            $primaryAgentId = $this->selectedAgentIds[0];
            $primaryAgent = $allAgents->firstWhere('id', $primaryAgentId);

            if (!$owner || !$primaryAgent) {
                throw new RuntimeException('Please select a valid managing agent.');
            }

            $additionalAgentIds = array_values(array_diff($this->selectedAgentIds, [$primaryAgentId]));

            $data = [
                'name' => $this->name,
                'model_type' => $this->modelType,
                'description' => $this->description ?: null,
                'managing_agent_id' => $primaryAgent->id,
            ];

            if ($this->modelType === 'rotational') {
                $data['contribution_amount'] = (float) $this->amount;
                $data['frequency'] = $this->frequency;
                $data['members_count'] = (int) $this->membersCount;
                $data['payout_order'] = $this->payoutOrder;
            } elseif ($this->modelType === 'continuous_pool') {
                $intervalDays = app(AjoService::class)->getIntervalDays($this->contributionInterval);
                $data['contribution_amount'] = 0;
                $data['frequency'] = $this->contributionInterval;
                $data['members_count'] = (int) $this->membersCount;
                $data['payout_order'] = 'end_of_period';
                $data['owner_fee_percentage'] = (float) $this->ownerFee;
                $data['collection_period_days'] = (int) $this->collectionPeriodDays;
                $data['collection_end_date'] = now()->addDays((int) $this->collectionPeriodDays)->toDateString();
                $data['min_contribution'] = $this->minContribution ? (float) $this->minContribution : null;
                $data['max_contribution'] = $this->maxContribution ? (float) $this->maxContribution : null;
                $data['target_pool_amount'] = $this->targetPool ? (float) $this->targetPool : null;
            } else {
                $data['contribution_amount'] = 0;
                $data['frequency'] = 'monthly';
                $data['members_count'] = (int) $this->membersCount;
                $data['payout_order'] = 'end_of_period';
                $data['owner_fee_percentage'] = (float) $this->ownerFee;
                $data['collection_period_days'] = (int) $this->collectionPeriodDays;
                $data['collection_end_date'] = now()->addDays((int) $this->collectionPeriodDays)->toDateString();
                $data['min_contribution'] = $this->minContribution ? (float) $this->minContribution : null;
                $data['max_contribution'] = $this->maxContribution ? (float) $this->maxContribution : null;
                $data['target_pool_amount'] = $this->targetPool ? (float) $this->targetPool : null;
            }

            /** @var AjoService $ajoService */
            $ajoService = app(AjoService::class);
            $group = $ajoService->createGroup($owner, $data, $primaryAgent, $additionalAgentIds);

            $this->createdGroupId = $group->id;
            $this->step = 5;

            rescue(fn () => app(\App\Services\AdminNotificationService::class)->create([
                'type' => 'new_ajo_group',
                'title' => 'New Ajo Group Created',
                'message' => "{$this->name} has been created by {$user->full_name} with {$this->membersCount} members.",
                'action_url' => '/admin/ajo-groups',
                'action_label' => 'View Groups',
                'severity' => 'info',
                'related_id' => $group->id,
                'related_type' => \App\Models\AjoGroup::class,
            ]), report: false);

            $this->dispatch('notify-success', message: 'Group created successfully.');
        } catch (RuntimeException $exception) {
            $this->dispatch('notify-error', message: $exception->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        $paginator = $this->getAssignableAgents();
        $selectedAgents = Agent::with('user')->whereIn('id', $this->selectedAgentIds)->get();

        return view('livewire.ajo-owner.group-create', [
            'availableAgents' => $paginator,
            'selectedAgents' => $selectedAgents,
        ])->layout('components.layouts.ajo-owner');
    }

    protected function getAssignableAgents(): LengthAwarePaginator
    {
        /** @var User $user */
        $user = Auth::user();
        $owner = $user->ajoOwner;

        return Agent::query()
            ->with('user')
            ->where(function ($query) use ($owner): void {
                $query->where('ajo_owner_id', $owner->id)
                    ->orWhereNull('ajo_owner_id');
            })
            ->when($this->searchAgent !== '', function ($query): void {
                $search = '%' . trim($this->searchAgent) . '%';
                $query->where(function ($builder) use ($search): void {
                    $builder->where('business_name', 'like', $search)
                        ->orWhere('lga', 'like', $search)
                        ->orWhere('state', 'like', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('full_name', 'like', $search)
                                ->orWhere('phone_number', 'like', $search);
                        });
                });
            })
            ->orderByDesc('ajo_owner_id')
            ->orderBy('business_name')
            ->paginate(10, ['*'], 'page', $this->agentPage);
    }
}
