<?php

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\KycDocument;
use App\Models\User;
use App\Models\Wallet;
use App\Services\AdminService;
use App\Services\KycCompletionService;
use App\Services\MonnifyWalletProvisioning;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class KycQueue extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'pending';
    public ?string $selectedDocumentId = null;
    public string $rejectionReason = '';
    public string $pendingAction = '';
    public bool $showActionModal = false;
    public bool $showDetailModal = false;
    public ?KycDocument $detailDocument = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function confirmDocumentAction(string $documentId, string $action): void
    {
        $this->selectedDocumentId = $documentId;
        $this->pendingAction = $action;
        $this->showActionModal = true;
        $this->rejectionReason = '';
    }

    public function closeActionModal(): void
    {
        $this->showActionModal = false;
        $this->selectedDocumentId = null;
        $this->pendingAction = '';
        $this->rejectionReason = '';
    }

    public function viewVerificationDetails(string $documentId): void
    {
        $this->detailDocument = KycDocument::with('user', 'submittedByAgent')->find($documentId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->detailDocument = null;
    }

    public function runDocumentAction(): void
    {
        $document = KycDocument::with('user')->findOrFail((string) $this->selectedDocumentId);
        $oldStatus = (string) $document->verification_status;

        if ($this->pendingAction === 'approve') {
            $this->approveDocument($document, $oldStatus);
        }

        if ($this->pendingAction === 'reject') {
            $this->rejectDocument($document, $oldStatus);
        }

        $this->closeActionModal();
    }

    protected function approveDocument(KycDocument $document, string $oldStatus): void
    {
        $user = $document->user;
        $oldLevel = (int) $user->kyc_level;

        $document->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
        ]);

        $kycCompletion = app(KycCompletionService::class);

        match ($document->document_type) {
            'nin_slip', 'nin' => $kycCompletion->approveDocumentAsNinVerification($user),
            'bvn_slip', 'bvn' => $kycCompletion->approveDocumentAsBvnVerification($user),
            default => null,
        };

        $tier2Completed = $kycCompletion->tryCompleteTier2($user);

        // Tier 3 documents still use direct admin approval.
        if (! $tier2Completed && $oldLevel === 2) {
            $user->update([
                'kyc_level' => 3,
                'kyc_verified_at' => now(),
            ]);

            $wallet = $user->wallets()->where('wallet_type', 'customer')->first();
            if ($wallet) {
                app(WalletService::class)->applyTierLimits($wallet, 3);
            }
        }

        $newLevel = (int) $user->fresh()->kyc_level;

        if ($tier2Completed) {
            $kycCompletion->dispatchTier2SuccessNotification($user);
        }

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'kyc_approved',
            'entity_type' => 'kyc_document',
            'entity_id' => $document->id,
            'old_values' => ['verification_status' => $oldStatus, 'kyc_level' => $oldLevel],
            'new_values' => ['verification_status' => 'verified', 'kyc_level' => $newLevel],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'KYC approved successfully.');
    }

    protected function rejectDocument(KycDocument $document, string $oldStatus): void
    {
        $this->validate([
            'rejectionReason' => ['required', 'string', 'min:3'],
        ], [
            'rejectionReason.required' => 'A rejection reason is required.',
        ]);

        $document->update([
            'verification_status' => 'rejected',
            'rejection_reason' => $this->rejectionReason,
        ]);

        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'kyc_rejected',
            'entity_type' => 'kyc_document',
            'entity_id' => $document->id,
            'old_values' => ['verification_status' => $oldStatus],
            'new_values' => ['verification_status' => 'rejected', 'reason' => $this->rejectionReason],
            'ip_address' => request()->ip(),
            'device_id' => request()->userAgent(),
        ]);

        $this->dispatch('notify-success', message: 'KYC rejected successfully.');
    }

    public function render()
    {
        $documents = KycDocument::query()
            ->with(['user', 'submittedByAgent'])
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('verification_status', $this->statusFilter))
            ->when($this->search, function ($q) {
                $q->where(function ($query): void {
                    $query->where('document_type', 'like', "%{$this->search}%")
                        ->orWhereHas('user', function ($q2) {
                            $q2->where('full_name', 'like', "%{$this->search}%")
                                ->orWhere('phone_number', 'like', "%{$this->search}%");
                        });
                });
            })
            ->latest()
            ->paginate(10);

        /** @var AdminService $adminService */
        $adminService = app(AdminService::class);

        return view('livewire.admin.kyc-queue', [
            'documents' => $documents,
            'pendingCount' => $adminService->getPendingKycCount()['pending'],
            'selectedDocument' => $this->selectedDocumentId ? KycDocument::with('user')->find($this->selectedDocumentId) : null,
        ])->layout('components.layouts.admin');
    }
}
