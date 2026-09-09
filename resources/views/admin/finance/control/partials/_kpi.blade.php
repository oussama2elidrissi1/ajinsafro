{{--
    Carte KPI du module Finance & Controle.
    Sobre et homogene avec le reste de l'administration : pas de gras excessif,
    pas de couleur decorative, la couleur ne sert qu'a signaler un solde negatif.
--}}
@php
    $tone = $tone ?? null;
    $valueClass = match ($tone) {
        'positive' => 'text-success',
        'negative' => 'text-danger',
        'auto' => ((float) ($raw ?? 0)) < 0 ? 'text-danger' : 'text-body',
        default => 'text-body',
    };
@endphp
<div class="col">
    <div class="card border-0 shadow-sm h-100">
        <div class="card-body py-3">
            <div class="text-muted text-uppercase small mb-1">{{ $label }}</div>
            <div class="fs-5 fw-semibold {{ $valueClass }}">{{ $value }}</div>
            @isset($hint)
                <div class="text-muted small mt-1">{{ $hint }}</div>
            @endisset
        </div>
    </div>
</div>
