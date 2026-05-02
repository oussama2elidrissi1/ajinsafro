@props([
    'action' => '',
    'method' => 'GET',
    'resetUrl' => '',
    'gridClass' => '',
])

<section class="aj-panel" style="margin-bottom:20px;padding:20px;border-radius:24px;background:rgba(255,255,255,0.96);border:1px solid var(--ajp-line);box-shadow:var(--ajp-shadow);">
    <form method="{{ $method }}" action="{{ $action }}">
        <div class="aj-filter-grid {{ $gridClass }}">
            {{ $fields }}

            <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="aj-btn aj-btn-primary w-100">
                    <i class="bx bx-filter-alt"></i>
                    <span>Filtrer</span>
                </button>
            </div>
            @if($resetUrl)
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ $resetUrl }}" class="aj-btn aj-btn-soft w-100">
                        <i class="bx bx-reset"></i>
                        <span>Réinitialiser</span>
                    </a>
                </div>
            @endif
        </div>

        @if(isset($chips) && trim($chips) !== '')
            <div class="aj-filter-chips" style="display:flex;align-items:center;flex-wrap:wrap;gap:10px;margin-top:16px;color:var(--ajp-muted);font-size:13px;font-weight:700;">
                <span>Filtres actifs :</span>
                {{ $chips }}
                @if(isset($clearFiltersUrl) && $clearFiltersUrl)
                    <a href="{{ $clearFiltersUrl }}" class="ms-auto fw-bold text-decoration-none" style="color:#0468c8;">Tout effacer</a>
                @endif
            </div>
        @endif
    </form>
</section>
