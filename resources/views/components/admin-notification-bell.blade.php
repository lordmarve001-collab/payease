<div class="relative" x-data="{ open: false }">
    <button @click="open = !open" class="relative p-2 text-text-secondary hover:text-text-primary transition-colors rounded-full hover:bg-background">
        <x-lucide-bell class="w-5 h-5" />
        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 w-4 h-4 bg-danger text-white text-[9px] font-bold rounded-full flex items-center justify-center border-2 border-surface">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
        @endif
    </button>

    <div x-show="open" @click.outside="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="absolute right-0 top-full mt-2 w-80 sm:w-96 bg-surface rounded-card shadow-elevation-4 border border-border z-50 overflow-hidden" x-cloak>
        <div class="p-3 border-b border-border flex items-center justify-between bg-background/50">
            <h3 class="text-sm font-bold text-text-primary">Notifications</h3>
            @if($unreadCount > 0)
                <button wire:click="markAllRead" class="text-xs text-primary hover:text-primary-dark font-medium">Mark all read</button>
            @endif
        </div>

        @php
            $notifications = \App\Models\AdminNotification::latest()->take(15)->get();
        @endphp

        <div class="max-h-80 overflow-y-auto divide-y divide-border">
            @forelse($notifications as $notification)
                <div class="px-3 py-3 hover:bg-background/50 transition-colors {{ $notification->is_read ? 'opacity-60' : '' }}">
                    <div class="flex items-start gap-2.5">
                        <div class="w-2 h-2 rounded-full mt-1.5 shrink-0 {{ match($notification->severity) { 'critical' => 'bg-red-500', 'warning' => 'bg-amber-500', default => 'bg-blue-500' } }}"></div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-text-primary">{{ $notification->title }}</p>
                            <p class="text-xs text-text-secondary mt-0.5 line-clamp-2">{{ $notification->message }}</p>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[10px] text-text-secondary">{{ $notification->created_at->diffForHumans() }}</span>
                                @if($notification->action_url)
                                    <a href="{{ url($notification->action_url) }}" wire:navigate class="text-[10px] font-semibold text-primary hover:text-primary-dark" @click="open = false">{{ $notification->action_label ?? 'View' }}</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center">
                    <x-lucide-bell-off class="w-8 h-8 text-text-secondary mx-auto mb-2" />
                    <p class="text-xs text-text-secondary">No notifications yet</p>
                </div>
            @endforelse
        </div>

        @if($notifications->count() >= 15)
        <div class="p-2 border-t border-border text-center bg-background/50">
            <a href="{{ url('/admin/notifications') }}" class="text-xs font-semibold text-primary hover:text-primary-dark">View All Notifications</a>
        </div>
        @endif
    </div>
</div>
