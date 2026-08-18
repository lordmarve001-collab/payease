<div class="px-4 py-6 md:p-8 max-w-5xl mx-auto space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-text-primary">{{ __('My Customers') }}</h1>
        <p class="text-sm text-text-secondary">{{ __('Customers you have registered') }}</p>
    </div>

    <!-- Search -->
    <div class="relative">
        <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-text-secondary" />
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, phone, or email...') }}"
            class="w-full pl-10 pr-4 py-2.5 rounded-btn border border-border bg-white text-text-primary placeholder-text-secondary/50 focus:outline-none focus:ring-2 focus:ring-secondary/40 focus:border-secondary transition-all" />
    </div>

    <!-- Customers Table -->
    <div class="bg-surface rounded-card shadow-elevation-1 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-border text-text-secondary text-xs uppercase tracking-wider">
                    <th class="text-left px-4 py-3 font-medium">{{ __('Name') }}</th>
                    <th class="text-left px-4 py-3 font-medium">{{ __('Phone') }}</th>
                    <th class="text-left px-4 py-3 font-medium">{{ __('Email') }}</th>
                    <th class="text-left px-4 py-3 font-medium">{{ __('KYC') }}</th>
                    <th class="text-left px-4 py-3 font-medium">{{ __('Registered') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    <tr class="border-b border-border last:border-0 hover:bg-background/50 transition-colors">
                        <td class="px-4 py-3 font-medium text-text-primary">{{ $customer->full_name }}</td>
                        <td class="px-4 py-3 text-text-secondary">0{{ $customer->phone_number }}</td>
                        <td class="px-4 py-3 text-text-secondary">{{ $customer->email ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ $customer->kyc_level === 0 ? 'bg-gray-100 text-gray-600' : '' }}
                                {{ $customer->kyc_level === 1 ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $customer->kyc_level === 2 ? 'bg-yellow-100 text-yellow-700' : '' }}
                                {{ $customer->kyc_level === 3 ? 'bg-green-100 text-green-700' : '' }}">
                                Tier {{ $customer->kyc_level }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-text-secondary text-xs">{{ $customer->created_at->format('d M Y, h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-text-secondary">
                            <div class="flex flex-col items-center gap-2">
                                <x-lucide-users class="w-10 h-10 text-text-secondary/50" />
                                <p>{{ __('No customers registered yet.') }}</p>
                                <a href="{{ route('agent.create-customer') }}" wire:navigate
                                    class="text-sm text-secondary hover:underline">{{ __('Register your first customer') }}</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($customers->hasPages())
        <div class="flex justify-center">
            {{ $customers->links() }}
        </div>
    @endif
</div>
