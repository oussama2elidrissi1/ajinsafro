@php
    $node = $node ?? [];
    $children = $node['children'] ?? [];
    $hasChildren = $children !== [];
    $depth = (int) ($depth ?? ($node['depth'] ?? 0));

    $key = strtolower($node['key'] ?? '');
    $label = strtolower($node['label'] ?? '');
    $iconMap = [
        'catalogue' => 'bx-map-alt',
        'produits'  => 'bx-map-alt',
        'voyage'    => 'bx-map',
        'reservation' => 'bx-file',
        'reservation_custom' => 'bx-edit',
        'carte'     => 'bx-edit',
        'messagerie'=> 'bx-message',
        'message'   => 'bx-message',
        'commission'=> 'bx-money',
        'commissions'=> 'bx-money',
        'dashboard' => 'bx-grid-alt',
        'profil'    => 'bx-user',
        'profile'   => 'bx-user',
        'logout'    => 'bx-log-out',
    ];
    $icon = 'bx-chevron-right';
    foreach ($iconMap as $k => $v) {
        if (str_contains($key, $k) || str_contains($label, $k)) {
            $icon = $v;
            break;
        }
    }
@endphp

@if($hasChildren)
    <details class="agent-sidebar-group {{ !empty($node['open']) ? 'is-open' : '' }}"
             {{ !empty($node['open']) ? 'open' : '' }}>
        <summary class="agent-sidebar-summary">
            <span class="agent-sidebar-summary-inner">
                <i class="bx bx-folder agent-sidebar-icon"></i>
                <span class="agent-sidebar-text">{{ $node['label'] }}</span>
            </span>
            <i class="bx bx-chevron-right agent-sidebar-chevron"></i>
        </summary>
        <div class="agent-sidebar-children">
            @foreach($children as $child)
                @include('agent_v2.partials.sidebar-node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    </details>
@else
    <a href="{{ $node['href'] ?? 'javascript:void(0);' }}"
       data-partner-nav
       class="agent-sidebar-link {{ !empty($node['active']) ? 'active' : '' }}">
        <i class="bx {{ $icon }} agent-sidebar-icon"></i>
        <span class="agent-sidebar-text">{{ $node['label'] }}</span>
        @if(($node['key'] ?? null) === 'messagerie' && ($unreadCount ?? 0) > 0)
            <span class="agent-sidebar-badge">{{ $unreadCount }}</span>
        @endif
    </a>
@endif
