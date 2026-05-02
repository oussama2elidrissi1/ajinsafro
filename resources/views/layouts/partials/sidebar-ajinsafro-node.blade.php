@php
    $node = $node ?? [];
    $children = $node['children'] ?? [];
    $hasChildren = $children !== [];
    $itemClasses = $hasChildren && ($node['open'] ?? false) ? 'mm-active' : '';
    $toggleClasses = trim(($hasChildren ? 'has-arrow waves-effect ' : 'waves-effect ') . (($node['active'] ?? false) ? 'mm-active active' : ''));
@endphp

<li class="{{ $itemClasses }}" data-menu-key="{{ $node['key'] ?? '' }}" data-menu-open="{{ !empty($node['open']) ? '1' : '0' }}">
    @if($hasChildren)
        <a href="javascript:void(0);" class="{{ $toggleClasses }}" aria-expanded="{{ !empty($node['open']) ? 'true' : 'false' }}">
            @if(!empty($node['icon']))
                <i class="{{ $node['icon'] }}"></i>
            @endif
            <span>{{ $node['label'] }}</span>
        </a>
        <ul class="sub-menu mm-collapse {{ !empty($node['open']) ? 'mm-show' : '' }}" data-menu-open="{{ !empty($node['open']) ? '1' : '0' }}">
            @foreach($children as $child)
                @include('layouts.partials.sidebar-ajinsafro-node', ['node' => $child])
            @endforeach
        </ul>
    @else
        <a href="{{ $node['href'] ?? 'javascript:void(0);' }}"
           class="{{ $toggleClasses }}"
           data-menu-active="{{ !empty($node['active']) ? '1' : '0' }}">
            @if(!empty($node['icon']))
                <i class="{{ $node['icon'] }}"></i>
            @endif
            <span>{{ $node['label'] }}</span>
            @if(($node['key'] ?? null) === 'messagerie' && ($unreadCount ?? 0) > 0)
                <span class="badge rounded-pill bg-primary float-end">{{ $unreadCount }}</span>
            @endif
        </a>
    @endif
</li>
