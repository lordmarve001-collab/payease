@props([
    'title',
    'id'
])

<div class="bg-surface rounded-card shadow-elevation-1 p-5 border border-border w-full h-full flex flex-col">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-bold text-text-primary text-base">{{ $title }}</h3>
        <button class="text-text-secondary hover:text-text-primary transition-colors">
            <x-lucide-more-horizontal class="w-5 h-5" />
        </button>
    </div>
    <div class="relative flex-1 w-full min-h-[250px]">
        <canvas id="{{ $id }}"></canvas>
    </div>
    {{ $slot }}
</div>
