@props([
    'type' => 'neutral',
    'label',
])

@php
    $classes = match($type) {
        'success' => 'background:#ecfdf3;color:#067647;',
        'warning' => 'background:#fff7e8;color:#b54708;',
        'danger' => 'background:#fff2f0;color:#d92d20;',
        'info' => 'background:#edf6ff;color:#0550a7;',
        default => 'background:#f2f4f7;color:#475467;',
    };
@endphp

<span class="aj-badge" style="display:inline-flex;align-items:center;gap:8px;min-height:28px;padding:0 11px;border-radius:999px;font-size:12px;font-weight:800;{{ $classes }}">{{ $label }}</span>
