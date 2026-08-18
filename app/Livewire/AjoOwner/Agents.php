<?php

namespace App\Livewire\AjoOwner;

use App\Helpers\PhoneNumberHelper;
use App\Jobs\SendSmsNotification;
use App\Mail\WelcomeMail;
use App\Models\Agent;
use App\Models\AgentDeletionRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class Agents extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showCreateModal = false;
    public bool $showLinkModal = false;
    public bool $showOtpModal = false;
    public bool $showOtpSuccess = false;
    public bool $showSuspendModal = false;
    public bool $showDeleteModal = false;

    public string $newFullName = '';
    public string $newPhone = '';
    public string $newEmail = '';
    public string $newBusinessName = '';
    public string $newLga = '';
    public string $newState = '';
    public string $linkPhone = '';

    public ?string $createdAgentUserId = null;
    public ?string $createdAgentPlainPin = null;
    public string $otpCode = '';
    public int $otpCooldown = 0;

    public ?string $suspendAgentId = null;
    public ?string $deleteAgentId = null;
    public string $deleteReason = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->reset(['newFullName', 'newPhone', 'newEmail', 'newBusinessName', 'newLga', 'newState']);
        $this->resetErrorBag();
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['newFullName', 'newPhone', 'newEmail', 'newBusinessName', 'newLga', 'newState']);
        $this->resetErrorBag();
    }

    public function openLinkModal(): void
    {
        $this->showLinkModal = true;
        $this->linkPhone = '';
        $this->resetErrorBag('linkPhone');
    }

    public function closeLinkModal(): void
    {
        $this->showLinkModal = false;
        $this->linkPhone = '';
        $this->resetErrorBag('linkPhone');
    }

    public function createAgent(): void
    {
        $this->validate([
            'newFullName' => ['required', 'string', 'max:255'],
            'newPhone' => ['required', 'string'],
            'newEmail' => ['required', 'email', 'max:255'],
            'newBusinessName' => ['required', 'string', 'max:255'],
            'newLga' => ['required', 'string', 'max:100'],
            'newState' => ['required', 'string', 'max:50'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $owner = $user->ajoOwner;

        if (!$owner) {
            throw new RuntimeException('Unable to resolve your Ajo owner profile.');
        }

        try {
            $normalizedPhone = PhoneNumberHelper::normalize($this->newPhone);
        } catch (\InvalidArgumentException) {
            $this->addError('newPhone', 'Enter a valid Nigerian phone number.');
            return;
        }

        if (User::where('phone_number', $normalizedPhone)->exists()) {
            $this->addError('newPhone', 'This phone number is already registered.');
            return;
        }

        if (User::where('email', $this->newEmail)->exists()) {
            $this->addError('newEmail', 'This email address is already registered.');
            return;
        }

        $plainPin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $agentUser = User::create([
            'full_name' => $this->newFullName,
            'phone_number' => $normalizedPhone,
            'email' => $this->newEmail,
            'pin_hash' => Hash::make($plainPin, ['rounds' => 12]),
            'login_pin_hash' => Hash::make($plainPin, ['rounds' => 12]),
            'transfer_pin_hash' => Hash::make($plainPin, ['rounds' => 12]),
            'status' => 'active',
            'kyc_level' => 0,
        ]);

        $agentUser->assignRole('agent');

        $agent = Agent::create([
            'user_id' => $agentUser->id,
            'ajo_owner_id' => $owner->id,
            'business_name' => $this->newBusinessName,
            'business_address' => '',
            'gps_latitude' => 0,
            'gps_longitude' => 0,
            'lga' => $this->newLga,
            'state' => $this->newState,
            'float_balance' => 0,
            'max_float' => 100000,
            'commission_rate' => 1.5,
            'total_earnings' => 0,
            'status' => 'active',
            'approved_at' => now(),
        ]);

        $this->createdAgentUserId = $agentUser->id;
        $this->createdAgentPlainPin = $plainPin;

        rescue(fn () => app(\App\Services\AdminNotificationService::class)->create([
            'type' => 'new_agent',
            'title' => 'New Agent Registered',
            'message' => "{$this->newBusinessName} ({$this->newFullName}) has been registered as an agent.",
            'action_url' => '/admin/agents',
            'action_label' => 'View Agents',
            'severity' => 'info',
            'related_id' => $agent->id,
            'related_type' => Agent::class,
        ]), report: false);

        try {
            SendSmsNotification::dispatch(
                $normalizedPhone,
                "Welcome to PayEase! Your login PIN is {$plainPin}. Keep it safe. -PayEase"
            );
        } catch (\Throwable) {
            // SMS failure is non-blocking; PIN is shown to owner
        }

        try {
            $otpService = app(OtpService::class);
            $otpService->sendOtp($agentUser, false);
            $this->otpCooldown = 60;
        } catch (\Throwable) {
            // OTP send failure is non-blocking; PIN is shown to owner
        }

        try {
            Mail::to($this->newEmail)->send(new WelcomeMail($agentUser, $plainPin));
        } catch (\Throwable) {
            // Email send failure is non-blocking
        }

        $this->showCreateModal = false;
        $this->showOtpModal = true;
        $this->otpCode = '';
    }

    public function resendOtp(): void
    {
        if (!$this->createdAgentUserId) {
            return;
        }

        $agentUser = User::find($this->createdAgentUserId);
        if (!$agentUser) {
            return;
        }

        try {
            $otpService = app(OtpService::class);
            $otpService->sendOtp($agentUser, false);
            $this->otpCooldown = 60;
            $this->dispatch('notify-info', message: 'OTP resent to agent phone.');
        } catch (\RuntimeException $e) {
            $this->dispatch('notify-error', message: $e->getMessage());
        }
    }

    public function verifyOtp(): void
    {
        if (!$this->createdAgentUserId) {
            return;
        }

        $this->validate(['otpCode' => ['required', 'string', 'size:6']]);

        $agentUser = User::find($this->createdAgentUserId);
        if (!$agentUser) {
            $this->addError('otpCode', 'Agent not found.');
            return;
        }

        $otpService = app(OtpService::class);
        if (!$otpService->verifyOtp($agentUser, $this->otpCode)) {
            $this->addError('otpCode', 'Invalid OTP code. Please try again.');
            return;
        }

        $otpService->clearOtp($agentUser);

        $this->showOtpSuccess = true;
    }

    public function closeOtpModal(): void
    {
        $this->showOtpModal = false;
        $this->showOtpSuccess = false;
        $this->otpCode = '';
        $this->createdAgentUserId = null;
        $this->createdAgentPlainPin = null;
        $this->otpCooldown = 0;
    }

    public function linkAgent(): void
    {
        $this->validate(['linkPhone' => ['required', 'string']]);

        $user = Auth::user();
        $owner = $user->ajoOwner;

        $agent = Agent::query()
            ->whereNull('ajo_owner_id')
            ->whereHas('user', function ($query): void {
                $query->where('phone_number', trim($this->linkPhone));
            })
            ->first();

        if (!$agent) {
            $this->addError('linkPhone', 'No unaffiliated PayEase agent found with that phone number.');
            return;
        }

        if (!$owner) {
            throw new RuntimeException('Unable to resolve the Ajo owner profile.');
        }

        $agent->update([
            'ajo_owner_id' => $owner->id,
        ]);

        $this->closeLinkModal();
        $this->dispatch('notify-success', message: 'Agent linked to your network successfully.');
    }

    public function confirmSuspend(string $agentId): void
    {
        $this->suspendAgentId = $agentId;
        $this->showSuspendModal = true;
    }

    public function closeSuspendModal(): void
    {
        $this->suspendAgentId = null;
        $this->showSuspendModal = false;
    }

    public function executeSuspend(): void
    {
        if (!$this->suspendAgentId) {
            return;
        }

        $agent = Agent::query()
            ->where('id', $this->suspendAgentId)
            ->where('ajo_owner_id', Auth::user()->ajoOwner?->id)
            ->with('user')
            ->first();

        if (!$agent) {
            $this->dispatch('notify-error', message: 'Agent not found.');
            return;
        }

        $oldStatus = $agent->status;
        $agent->update(['status' => 'suspended']);
        $agent->user?->update(['status' => 'suspended']);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'ajo_owner_agent_suspended',
            'entity_type' => 'agent',
            'entity_id' => $agent->id,
            'old_values' => ['status' => $oldStatus],
            'new_values' => ['status' => 'suspended'],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->closeSuspendModal();
        $this->dispatch('notify-success', message: "Agent \"{$agent->user?->full_name}\" has been suspended.");
    }

    public function confirmDelete(string $agentId): void
    {
        $this->deleteAgentId = $agentId;
        $this->deleteReason = '';
        $this->showDeleteModal = true;
        $this->resetErrorBag('deleteReason');
    }

    public function closeDeleteModal(): void
    {
        $this->deleteAgentId = null;
        $this->deleteReason = '';
        $this->showDeleteModal = false;
    }

    public function submitDeleteRequest(): void
    {
        $this->validate(['deleteReason' => ['required', 'string', 'min:10', 'max:500']]);

        if (!$this->deleteAgentId) {
            return;
        }

        $agent = Agent::query()
            ->where('id', $this->deleteAgentId)
            ->where('ajo_owner_id', Auth::user()->ajoOwner?->id)
            ->first();

        if (!$agent) {
            $this->dispatch('notify-error', message: 'Agent not found.');
            return;
        }

        $existing = AgentDeletionRequest::where('agent_id', $agent->id)
            ->where('status', 'pending')
            ->exists();

        if ($existing) {
            $this->dispatch('notify-error', message: 'A deletion request is already pending for this agent.');
            $this->closeDeleteModal();
            return;
        }

        AgentDeletionRequest::create([
            'agent_id' => $agent->id,
            'requested_by_user_id' => Auth::id(),
            'status' => 'pending',
            'reason' => $this->deleteReason,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'agent_deletion_requested',
            'entity_type' => 'agent',
            'entity_id' => $agent->id,
            'old_values' => null,
            'new_values' => ['reason' => $this->deleteReason, 'status' => 'pending'],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->closeDeleteModal();
        $this->dispatch('notify-success', message: 'Deletion request submitted. Awaiting super admin approval.');
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user();
        $owner = $user->ajoOwner;

        $query = Agent::query()
            ->where('ajo_owner_id', $owner?->id)
            ->with(['user', 'managingAjoGroups'])
            ->withCount('managingAjoGroups');

        if ($this->search !== '') {
            $search = '%' . trim($this->search) . '%';
            $query->where(function ($builder) use ($search): void {
                $builder->where('business_name', 'like', $search)
                    ->orWhere('lga', 'like', $search)
                    ->orWhere('state', 'like', $search)
                    ->orWhereHas('user', function ($userQuery) use ($search): void {
                        $userQuery->where('full_name', 'like', $search)
                            ->orWhere('phone_number', 'like', $search);
                    });
            });
        }

        $agents = $query->latest()->paginate(10);
        $memberCounts = [];
        $pendingDeletions = [];
        foreach ($agents as $agent) {
            $memberCounts[$agent->id] = (int) $agent->managingAjoGroups->sum('members_count');
            $pendingDeletions[$agent->id] = AgentDeletionRequest::where('agent_id', $agent->id)
                ->where('status', 'pending')
                ->exists();
        }

        $pendingAgentId = $this->createdAgentUserId;
        $pendingAgentName = null;
        $pendingAgentPhone = null;
        if ($pendingAgentId) {
            $pendingAgent = User::find($pendingAgentId);
            $pendingAgentName = $pendingAgent?->full_name;
            $pendingAgentPhone = $pendingAgent?->phone_number;
        }

        return view('livewire.ajo-owner.agents', [
            'agents' => $agents,
            'memberCounts' => $memberCounts,
            'pendingDeletions' => $pendingDeletions,
            'pendingAgentName' => $pendingAgentName,
            'pendingAgentPhone' => $pendingAgentPhone,
        ])->layout('components.layouts.ajo-owner');
    }
}
