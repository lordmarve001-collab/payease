@props([
    'status' => 'active', // active, pending, suspended, failed, completed, verified, rejected, paid, defaulted
    'label' => null
])

@php
    $statusMap = [
        'active'    => ['bg' => 'bg-primary-light', 'text' => 'text-primary', 'dot' => 'bg-primary'],
        'completed' => ['bg' => 'bg-primary-light', 'text' => 'text-primary', 'dot' => 'bg-primary'],
        'verified'  => ['bg' => 'bg-primary-light', 'text' => 'text-primary', 'dot' => 'bg-primary'],
        'paid'      => ['bg' => 'bg-primary-light', 'text' => 'text-primary', 'dot' => 'bg-primary'],
        'upcoming'  => ['bg' => 'bg-primary-light', 'text' => 'text-primary', 'dot' => 'bg-primary'],
        'auto_verified' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700', 'dot' => 'bg-emerald-500'],
        
        'pending'   => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'dot' => 'bg-orange-500'],
        'overdue'   => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'dot' => 'bg-orange-500'],
        'manual_review' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700', 'dot' => 'bg-amber-500'],
        
        'suspended' => ['bg' => 'bg-red-100', 'text' => 'text-danger', 'dot' => 'bg-danger'],
        'failed'    => ['bg' => 'bg-red-100', 'text' => 'text-danger', 'dot' => 'bg-danger'],
        'rejected'  => ['bg' => 'bg-red-100', 'text' => 'text-danger', 'dot' => 'bg-danger'],
        'reversed'  => ['bg' => 'bg-red-100', 'text' => 'text-danger', 'dot' => 'bg-danger'],
        'defaulted' => ['bg' => 'bg-red-100', 'text' => 'text-danger', 'dot' => 'bg-danger'],
        'provider_error' => ['bg' => 'bg-rose-100', 'text' => 'text-rose-700', 'dot' => 'bg-rose-500'],
    ];

    $mapped = $statusMap[strtolower($status)] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'dot' => 'bg-gray-400'];
    $displayText = $label ?? ucfirst($status);
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider {{ $mapped['bg'] }} {{ $mapped['text'] }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $mapped['dot'] }}"></span>
    {{ $displayText }}
</span>
