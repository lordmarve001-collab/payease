@props([
    'status' => 'active', // active, pending, suspended, failed, completed, verified, rejected, paid, defaulted
    'label' => null
])

@php
    $statusMap = [
        'active'    => ['bg' => 'bg-primary/15', 'text' => 'text-primary-light', 'dot' => 'bg-primary'],
        'completed' => ['bg' => 'bg-primary/15', 'text' => 'text-primary-light', 'dot' => 'bg-primary'],
        'verified'  => ['bg' => 'bg-primary/15', 'text' => 'text-primary-light', 'dot' => 'bg-primary'],
        'paid'      => ['bg' => 'bg-primary/15', 'text' => 'text-primary-light', 'dot' => 'bg-primary'],
        'upcoming'  => ['bg' => 'bg-primary/15', 'text' => 'text-primary-light', 'dot' => 'bg-primary'],
        'auto_verified' => ['bg' => 'bg-emerald-500/15', 'text' => 'text-emerald-300', 'dot' => 'bg-emerald-400'],

        'pending'   => ['bg' => 'bg-orange-500/15', 'text' => 'text-orange-300', 'dot' => 'bg-orange-400'],
        'overdue'   => ['bg' => 'bg-orange-500/15', 'text' => 'text-orange-300', 'dot' => 'bg-orange-400'],
        'manual_review' => ['bg' => 'bg-amber-500/15', 'text' => 'text-amber-300', 'dot' => 'bg-amber-400'],

        'suspended' => ['bg' => 'bg-red-500/15', 'text' => 'text-red-300', 'dot' => 'bg-danger'],
        'failed'    => ['bg' => 'bg-red-500/15', 'text' => 'text-red-300', 'dot' => 'bg-danger'],
        'rejected'  => ['bg' => 'bg-red-500/15', 'text' => 'text-red-300', 'dot' => 'bg-danger'],
        'reversed'  => ['bg' => 'bg-red-500/15', 'text' => 'text-red-300', 'dot' => 'bg-danger'],
        'defaulted' => ['bg' => 'bg-red-500/15', 'text' => 'text-red-300', 'dot' => 'bg-danger'],
        'provider_error' => ['bg' => 'bg-rose-500/15', 'text' => 'text-rose-300', 'dot' => 'bg-rose-400'],
    ];

    $mapped = $statusMap[strtolower($status)] ?? ['bg' => 'bg-white/10', 'text' => 'text-text-secondary', 'dot' => 'bg-gray-400'];
    $displayText = $label ?? ucfirst($status);
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider border border-white/5 {{ $mapped['bg'] }} {{ $mapped['text'] }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $mapped['dot'] }} shadow-[0_0_6px_currentColor]"></span>
    {{ $displayText }}
</span>
