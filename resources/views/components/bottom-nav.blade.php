<nav {{ $attributes->merge(['class' => 'fixed bottom-0 left-0 right-0 z-40 md:hidden px-2 pb-[max(0.5rem,env(safe-area-inset-bottom))] pt-2']) }}
    x-data="{ show: false }"
    x-init="setTimeout(() => show = true, 200)"
    :class="show ? 'translate-y-0' : 'translate-y-full'"
    class="translate-y-full transition-transform duration-500 ease-spring">
    <div class="glass-strong rounded-sheet shadow-elevation-4 px-2 py-2 flex flex-wrap justify-around items-center gap-y-1">
        {{ $slot }}
    </div>
</nav>
