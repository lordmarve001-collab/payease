<?php

namespace App\Livewire\AjoOwner;

use App\Models\KycDocument;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Kyc extends Component
{
    use WithFileUploads;
    public int $kycLevel = 0;

    public bool $showUploadModal = false;
    public ?string $uploadDocType = null;
    public string $documentType = '';
    public $documentFile;
    public bool $submitting = false;

    public string $bvn = '';
    public string $nin = '';

    protected function rules(): array
    {
        return [
            'bvn' => ['required_if:uploadDocType,bvn', 'nullable', 'string', 'size:11', 'digits:11'],
            'nin' => ['required_if:uploadDocType,nin', 'nullable', 'string', 'size:11', 'digits:11'],
            'documentType' => ['required', 'string', 'in:nin_slip,bvn_slip,government_id,utility_bill,passport_photograph'],
            'documentFile' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf'],
        ];
    }

    public function mount(): void
    {
        $this->kycLevel = (int) Auth::user()->kyc_level;
    }

    public function openUploadModal(string $docType): void
    {
        $this->uploadDocType = $docType;
        $this->documentType = '';
        $this->documentFile = null;
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->uploadDocType = null;
        $this->documentType = '';
        $this->documentFile = null;
        $this->resetValidation();
    }

    public function saveBvn(): void
    {
        $this->validate(['bvn' => 'required|string|size:11|digits:11']);

        /** @var User $user */
        $user = Auth::user();
        $user->update(['bvn' => $this->bvn]);

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'bvn',
            'document_url' => 'bvn:' . $this->bvn,
            'verification_status' => 'pending',
            'auto_verified' => false,
        ]);

        $this->bvn = '';
        $this->closeUploadModal();
        $this->dispatch('notify-success', message: 'BVN submitted for review.');
    }

    public function saveNin(): void
    {
        $this->validate(['nin' => 'required|string|size:11|digits:11']);

        /** @var User $user */
        $user = Auth::user();
        $user->update(['nin' => $this->nin]);

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => 'nin',
            'document_url' => 'nin:' . $this->nin,
            'verification_status' => 'pending',
            'auto_verified' => false,
        ]);

        $this->nin = '';
        $this->closeUploadModal();
        $this->dispatch('notify-success', message: 'NIN submitted for review.');
    }

    public function uploadDocument(): void
    {
        $this->validate();

        /** @var User $user */
        $user = Auth::user();

        $path = $this->documentFile->store('kyc/' . $user->id, 'public');

        KycDocument::create([
            'user_id' => $user->id,
            'document_type' => $this->documentType,
            'document_url' => Storage::url($path),
            'verification_status' => 'pending',
            'auto_verified' => false,
        ]);

        $this->closeUploadModal();
        $this->dispatch('notify-success', message: 'Document submitted for review.');
    }

    public function resubmitNin(): void
    {
        $this->uploadDocType = 'nin';
        $this->nin = '';
        $this->showUploadModal = true;
    }

    public function resubmitBvn(): void
    {
        $this->uploadDocType = 'bvn';
        $this->bvn = '';
        $this->showUploadModal = true;
    }

    public function resubmitDocument(): void
    {
        $this->uploadDocType = 'document';
        $this->documentType = '';
        $this->documentFile = null;
        $this->showUploadModal = true;
    }

    public function render()
    {
        /** @var User $user */
        $user = Auth::user()->fresh();
        $this->kycLevel = (int) $user->kyc_level;

        $documents = KycDocument::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.ajo-owner.kyc', [
            'user' => $user,
            'documents' => $documents,
        ])->layout('components.layouts.ajo-owner');
    }
}
