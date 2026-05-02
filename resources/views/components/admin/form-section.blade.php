@props([
    'title',
    'subtitle' => null,
])

<div class="card mb-4">
    @if($title)
        <div class="card-header">
            <h5 class="card-title mb-0" style="font-weight:800;letter-spacing:-0.02em;">{{ $title }}</h5>
            @if($subtitle)
                <p class="card-title-desc mb-0 mt-1" style="color:var(--ajp-muted);font-size:.85rem;font-weight:500;">{{ $subtitle }}</p>
            @endif
        </div>
    @endif
    <div class="card-body">
        {{ $slot }}
    </div>
</div>
