@props([
    'title',
    'searchPlaceholder' => 'Search...',
    'filters' => [],
    'paginator' => null,
])

<div class="bg-surface rounded-card shadow-elevation-1 overflow-hidden border border-border flex flex-col">
    <!-- Header / Toolbar -->
    <div class="p-4 border-b border-border flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between bg-surface/60 backdrop-blur-sm z-10">
        <h3 class="text-lg font-bold text-text-primary">{{ $title }}</h3>
        
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <!-- Search -->
            <div class="relative flex-1 sm:w-64">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <x-lucide-search class="w-4 h-4 text-text-secondary" />
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" class="block w-full pl-9 pr-3 py-2 border border-border rounded-btn bg-background text-sm placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors" placeholder="{{ $searchPlaceholder }}">
            </div>
            
            <!-- Filters -->
            @if(!empty($filters))
                <div class="flex gap-2 overflow-x-auto scrollbar-hide shrink-0">
                    <button class="p-2 border border-border rounded-btn text-text-secondary hover:text-text-primary hover:bg-surface-2 transition-colors cursor-pointer" title="Filter">
                        <x-lucide-filter class="w-4 h-4" />
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto w-full">
        <table class="w-full text-left border-collapse min-w-max">
            <thead>
                <tr class="border-b border-border bg-surface-2/50 text-xs uppercase tracking-wider text-text-secondary">
                    {{ $header }}
                </tr>
            </thead>
            <tbody class="divide-y divide-border">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    
    <!-- Empty State Fallback -->
    @if(trim($slot) === '')
        <div class="p-12 text-center flex flex-col items-center justify-center text-text-secondary">
            <div class="w-16 h-16 bg-surface-2 rounded-full flex items-center justify-center mb-4">
                <x-lucide-inbox class="w-8 h-8 text-text-secondary" />
            </div>
            <h4 class="text-base font-bold text-text-primary mb-1">No records found</h4>
            <p class="text-sm">Try adjusting your search or filters.</p>
        </div>
    @endif

    @if($paginator)
        <div class="p-4 border-t border-border flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-sm text-text-secondary bg-surface">
            <div>
                Showing
                <span class="font-medium text-text-primary">{{ $paginator->firstItem() ?? 0 }}</span>
                to
                <span class="font-medium text-text-primary">{{ $paginator->lastItem() ?? 0 }}</span>
                of
                <span class="font-medium text-text-primary">{{ $paginator->total() }}</span>
                results
            </div>
            <div class="[&>nav>div:first-child]:hidden [&_span[aria-current='page']>span]:bg-primary [&_span[aria-current='page']>span]:text-white [&_a]:border [&_a]:border-border [&_a]:rounded-btn [&_a]:px-3 [&_a]:py-1 [&_span]:border [&_span]:border-border [&_span]:rounded-btn [&_span]:px-3 [&_span]:py-1">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        </div>
    @endif
</div>
