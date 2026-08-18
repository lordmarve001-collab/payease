<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Agent;
use App\Models\AgentDeletionRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Agents extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public ?string $selectedAgentId = null;
    public string $pendingAction = '';
    public bool $showActionModal = false;

    public ?string $viewingAgentId = null;
    public bool $showViewModal = false;

    public ?string $deletionRequestId = null;
    public bool $showDeletionModal = false;
    public string $deletionAdminNotes = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmAgentAction(string $agentId, string $action): void
    {
        $this->selectedAgentId = $agentId;
        $this->pendingAction = $action;
        $this->showActionModal = true;
    }

    public function closeActionModal(): void
    {
        $this->showActionModal = false;
        $this->selectedAgentId = null;
        $this->pendingAction = '';
    }

    public function viewAgent(string $agentId): void
    {
        $this->viewingAgentId = $agentId;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->viewingAgentId = null;
        $this->showViewModal = false;
    }

    public function runAgentAction(): void
    {
        $agent = Agent::with('user')->findOrFail((string) $this->selectedAgentId);
        $oldStatus = (string) $agent->status;

        if ($this->pendingAction === 'approve') {
            $agent->update([
                'status' => 'active',
                'approved_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'agent_approved',
                'entity_type' => 'agent',
                'entity_id' => $agent->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'active', 'approved_at' => now()->toIso8601String()],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Agent approved successfully.');
        } elseif ($this->pendingAction === 'suspend') {
            $agent->update([
                'status' => 'suspended',
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'agent_suspended',
                'entity_type' => 'agent',
                'entity_id' => $agent->id,
                'old_values' => ['status' => $oldStatus],
                'new_values' => ['status' => 'suspended'],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Agent suspended successfully.');
        }

        $this->closeActionModal();
    }

    public function confirmDeletionRequest(string $requestId, string $action): void
    {
        $this->deletionRequestId = $requestId;
        $this->pendingAction = $action;
        $this->deletionAdminNotes = '';
        $this->showDeletionModal = true;
    }

    public function closeDeletionModal(): void
    {
        $this->deletionRequestId = null;
        $this->pendingAction = '';
        $this->deletionAdminNotes = '';
        $this->showDeletionModal = false;
    }

    public function processDeletionRequest(): void
    {
        $request = AgentDeletionRequest::with(['agent', 'requestedBy'])->find($this->deletionRequestId);
        if (!$request || $request->status !== 'pending') {
            $this->closeDeletionModal();
            return;
        }

        if ($this->pendingAction === 'approve') {
            $agent = $request->agent;
            if ($agent) {
                $agent->update(['status' => 'deleted']);
                $agent->user?->update(['status' => 'suspended']);
            }

            $request->update([
                'status' => 'approved',
                'reviewed_by_user_id' => Auth::id(),
                'admin_notes' => $this->deletionAdminNotes ?: null,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'agent_deletion_approved',
                'entity_type' => 'agent_deletion_request',
                'entity_id' => $request->id,
                'old_values' => ['status' => 'pending'],
                'new_values' => ['status' => 'approved'],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Agent deletion approved. Agent has been removed.');
        } else {
            $request->update([
                'status' => 'rejected',
                'reviewed_by_user_id' => Auth::id(),
                'admin_notes' => $this->deletionAdminNotes ?: null,
                'reviewed_at' => now(),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'agent_deletion_rejected',
                'entity_type' => 'agent_deletion_request',
                'entity_id' => $request->id,
                'old_values' => ['status' => 'pending'],
                'new_values' => ['status' => 'rejected'],
                'ip_address' => request()->ip(),
                'device_id' => request()->userAgent(),
            ]);

            $this->dispatch('notify-success', message: 'Agent deletion request rejected.');
        }

        $this->closeDeletionModal();
    }

    public function render()
    {
        $agents = Agent::query()
            ->with('user')
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($query): void {
                    $query->where('business_name', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($q2) {
                            $q2->where('full_name', 'like', "%{$this->search}%")
                                ->orWhere('phone_number', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);

        $pendingDeletions = AgentDeletionRequest::with(['agent', 'requestedBy'])
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('livewire.admin.agents', [
            'agents' => $agents,
            'selectedAgent' => $this->selectedAgentId ? Agent::with('user')->find($this->selectedAgentId) : null,
            'viewingAgent' => $this->viewingAgentId ? Agent::with(['user', 'ajoOwner', 'assignedGroups'])->find($this->viewingAgentId) : null,
            'pendingDeletions' => $pendingDeletions,
        ])->layout('components.layouts.admin');
    }
}
