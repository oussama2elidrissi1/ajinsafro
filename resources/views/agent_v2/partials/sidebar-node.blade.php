@php
    $node = $node ?? [];
    $children = $node['children'] ?? [];
    $hasChildren = $children !== [];
    $depth = (int) ($depth ?? ($node['depth'] ?? 0));
    $paddingClass = match ($depth) {
        0 => 'px-4',
        1 => 'px-3',
        default => 'px-3 ms-3 border-start border-gray-100',
    };
    $summaryTextClass = $depth === 0 ? 'text-[12px]' : 'text-[11px]';
@endphp

@if($hasChildren)
    <details class="agent-nav-group rounded-xl border border-transparent hover:border-gray-100 {{ !empty($node['open']) ? 'bg-gray-50/80' : '' }}"
             {{ !empty($node['open']) ? 'open' : '' }}>
        <summary class="flex items-center justify-between gap-2 {{ $paddingClass }} py-2.5 cursor-pointer list-none select-none {{ $summaryTextClass }} font-bold text-[#0e3a5a] [&::-webkit-details-marker]:hidden">
            <span class="normal-case tracking-tight">{{ $node['label'] }}</span>
            <i class="fas fa-chevron-right text-[10px] text-gray-400 transition-transform agent-nav-chevron"></i>
        </summary>
        <div class="pb-2 pt-0 flex flex-col gap-0.5 {{ $depth === 0 ? 'px-1' : 'px-2' }}">
            @foreach($children as $child)
                @include('agent_v2.partials.sidebar-node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    </details>
@else
    <a href="{{ $node['href'] ?? 'javascript:void(0);' }}"
       data-partner-nav
       class="flex items-center justify-between gap-3 {{ $paddingClass }} py-2.5 rounded-lg {{ !empty($node['active']) ? 'bg-[#e6f3fa]/60 text-[#0083c4] font-semibold' : 'text-gray-600 hover:bg-white hover:text-[#0083c4] font-medium' }} transition-colors">
        <span class="flex items-center gap-3">
            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ !empty($node['active']) ? 'bg-[#0083c4]' : 'bg-gray-300' }}"></span>
            <span class="leading-snug">{{ $node['label'] }}</span>
        </span>
        @if(($node['key'] ?? null) === 'messagerie' && ($unreadCount ?? 0) > 0)
            <span class="rounded-full bg-[#0b57d0] px-2 py-0.5 text-[11px] font-semibold text-white">{{ $unreadCount }}</span>
        @endif
    </a>
@endif
