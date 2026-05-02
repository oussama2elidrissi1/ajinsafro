@props([
    'title' => 'Aucun résultat',
    'message' => 'Ajustez vos filtres ou créez un nouvel élément.',
    'actionUrl' => null,
    'actionLabel' => null,
    'icon' => 'bx bx-folder-open',
])

<div class="aj-empty">
    <div class="mb-3">
        <i class="{{ $icon }}" style="font-size: 2.5rem; color: var(--ajp-primary);"></i>
    </div>
    <h5 class="mb-2" style="color: var(--ajp-ink); font-weight: 800;">{{ $title }}</h5>
    <p class="text-muted mb-3" style="font-weight: 600;">{{ $message }}</p>
    @if($actionUrl && $actionLabel)
        <a href="{{ $actionUrl }}" class="aj-btn aj-btn-primary">
            <i class="bx bx-plus"></i>
            <span>{{ $actionLabel }}</span>
        </a>
    @endif
</div>
