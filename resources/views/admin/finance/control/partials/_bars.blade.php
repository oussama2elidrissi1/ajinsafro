{{--
    Graphique en barres horizontales, rendu en CSS pur.
    Choix volontaire : aucune librairie JS supplementaire n'est chargee dans l'admin,
    et la lecture reste exacte (valeur affichee a cote de chaque barre).
--}}
@php
    $max = collect($rows)->max($valueKey) ?: 0;
@endphp

@forelse ($rows as $row)
    @php($value = (float) $row[$valueKey])
    <div class="mb-2">
        <div class="d-flex justify-content-between small">
            <span class="text-truncate me-2">{{ $row[$labelKey] }}</span>
            <span class="text-muted text-nowrap">{{ number_format($value, 2, ',', ' ') }} DH</span>
        </div>
        <div class="progress" style="height: 6px;">
            <div class="progress-bar bg-primary"
                 role="progressbar"
                 style="width: {{ $max > 0 ? round($value / $max * 100, 1) : 0 }}%"
                 aria-valuenow="{{ $value }}"
                 aria-valuemin="0"
                 aria-valuemax="{{ $max }}"></div>
        </div>
    </div>
@empty
    <p class="text-muted small mb-0">Aucune donnee sur la periode.</p>
@endforelse
