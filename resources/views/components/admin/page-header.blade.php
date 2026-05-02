@props([
    'title',
    'subtitle' => null,
    'breadcrumbs' => [],
])

@php
    $currentRoute = Route::currentRouteName();
@endphp

<div class="aj-page-head" style="display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:22px;">
    <div>
        <h1 class="aj-title" style="margin:0;color:var(--ajp-ink);font-size:clamp(1.9rem, 2.2vw, 2.6rem);font-weight:800;letter-spacing:-0.04em;">{{ $title }}</h1>
        @if($subtitle)
            <p class="aj-subtitle" style="margin:8px 0 0;color:var(--ajp-muted);font-weight:500;max-width:720px;">{{ $subtitle }}</p>
        @endif
    </div>
    <div>
        @if(!empty($breadcrumbs))
            <div class="aj-breadcrumb" style="display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-bottom:14px;color:#718198;font-size:13px;font-weight:700;">
                @foreach($breadcrumbs as $i => $crumb)
                    @if($i > 0)
                        <span>/</span>
                    @endif
                    @if(!empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" style="color:#718198;text-decoration:none;">{{ $crumb['label'] }}</a>
                    @else
                        <strong style="color:#0b1f3a">{{ $crumb['label'] }}</strong>
                    @endif
                @endforeach
            </div>
        @endif
        @if(isset($actions))
            <div class="d-flex flex-wrap gap-2">{{ $actions }}</div>
        @endif
    </div>
</div>
