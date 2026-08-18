<div class="px-4 py-6 md:p-8 max-w-4xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ __('KYC Submissions') }}</h1>
        <p class="text-sm text-text-secondary">{{ __('Track the status of KYC documents you\'ve submitted on behalf of customers.') }}</p>
    </div>

    <!-- Filter Chips -->
    <div class="flex gap-2 overflow-x-auto pb-2">
        <button wire:click="$set('statusFilter', 'all')" class="{{ $statusFilter === 'all' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">
            {{ __('All') }}
        </button>
        <button wire:click="$set('statusFilter', 'pending')" class="{{ $statusFilter === 'pending' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">
            {{ __('Pending') }}
        </button>
        <button wire:click="$set('statusFilter', 'verified')" class="{{ $statusFilter === 'verified' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">
            {{ __('Verified') }}
        </button>
        <button wire:click="$set('statusFilter', 'rejected')" class="{{ $statusFilter === 'rejected' ? 'bg-secondary text-white border-secondary' : 'bg-surface text-text-secondary border-border hover:bg-gray-50' }} px-4 py-1.5 rounded-full border text-sm font-medium transition-colors whitespace-nowrap">
            {{ __('Rejected') }}
        </button>
    </div>

    <!-- Documents List -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-text-secondary text-xs uppercase tracking-wider">
                    <th class="text-left px-4 py-3 font-medium">{{ __('Customer') }}</th>
                    <th class="text-left px-4 py-3 font-medium">{{ __('Document') }}</th>
                    <th class="text-left px-4 py-3 font-medium">{{ __('Status') }}</th>
                    <th class="text-left px-4 py-3 font-medium">{{ __('Submitted') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr class="border-b border-border last:border-0 hover:bg-background/50 transition-colors">
                        <td class="px-4 py-3">
                            <p class="font-medium text-text-primary">{{ $doc->user?->full_name ?? __('Unknown') }}</p>
                            <p class="text-xs text-text-secondary">{{ $doc->user?->phone_number ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-text-secondary">
                            {{ ucwords(str_replace('_', ' ', $doc->document_type)) }}
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $statusClass = match ($doc->verification_status) {
                                    'verified' => 'bg-green-100 text-green-700',
                                    'rejected' => 'bg-red-100 text-red-700',
                                    'pending_review' => 'bg-yellow-100 text-yellow-700',
                                    default => 'bg-amber-100 text-amber-700',
                                };
                            @endphp
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusClass }}">
                                {{ ucwords(str_replace('_', ' ', $doc->verification_status ?? 'pending')) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-text-secondary text-xs">
                            {{ $doc->created_at->format('d M Y, h:i A') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-text-secondary">
                            <div class="flex flex-col items-center gap-2">
                                <x-lucide-file-check class="w-10 h-10 text-text-secondary/50" />
                                <p>{{ __('No KYC submissions yet.') }}</p>
                                <a href="{{ route('agent.upgrade-customer') }}" wire:navigate
                                    class="text-sm text-secondary hover:underline">{{ __('Submit a KYC upgrade') }}</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($documents->hasPages())
        <div class="flex justify-center">
            {{ $documents->links() }}
        </div>
    @endif
</div>
