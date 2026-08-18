<div class="px-4 py-6 md:p-8 max-w-xl mx-auto space-y-6">
    <div>
        <h2 class="text-2xl font-bold text-text-primary">{{ __('Language') }}</h2>
        <p class="text-text-secondary mt-1">{{ __('Choose your preferred language.') }}</p>
    </div>

    <div class="rounded-card border border-border bg-surface shadow-elevation-1 overflow-hidden">
        <div class="divide-y divide-border">
            @foreach($languages as $code => $name)
            <button wire:click="setLanguage('{{ $code }}')" class="w-full flex items-center justify-between p-5 hover:bg-background transition-colors active:bg-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full {{ $selectedLanguage === $code ? 'bg-primary-light text-primary' : 'bg-gray-100 text-text-secondary' }} flex items-center justify-center">
                        <x-lucide-globe class="w-5 h-5" />
                    </div>
                    <span class="text-sm font-medium {{ $selectedLanguage === $code ? 'text-primary font-semibold' : 'text-text-primary' }}">{{ $name }}</span>
                </div>
                @if($selectedLanguage === $code)
                <x-lucide-check class="w-5 h-5 text-primary" />
                @endif
            </button>
            @endforeach
        </div>
    </div>
</div>
