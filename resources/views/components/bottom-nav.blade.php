<nav {{ $attributes->merge(['class' => 'fixed bottom-0 left-0 right-0 bg-surface border-t border-border px-2 py-2 flex flex-wrap justify-around items-center gap-y-1 shadow-elevation-2 md:hidden z-40']) }}
    x-data="{ show: false }" 
    x-init="setTimeout(() => show = true, 200)"
    :class="show ? 'translate-y-0' : 'translate-y-full'"
    class="translate-y-full transition-transform duration-300 ease-material">
    {{ $slot }}
</nav>
