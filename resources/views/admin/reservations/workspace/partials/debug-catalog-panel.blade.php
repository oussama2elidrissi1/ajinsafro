{{--
  Bouton FAB + modal debug catalogue — inclus en fin de <body> (@stack body-end)
  pour que position:fixed soit relatif à la fenêtre (pas à une colonne / transform parent).
--}}
@if(config('app.debug') && isset($catalogMeta))
<div id="ws-debug-fab-wrap" class="ws-debug-fab-wrap">
    <button type="button" id="ws-debug-modal-open" class="ws-debug-fab-btn" title="Ouvrir le panneau debug catalogue (APP_DEBUG)">
        <i class="fas fa-bug" aria-hidden="true"></i>
        <span>Debug test</span>
    </button>
</div>

<div id="ws-debug-modal" class="ws-debug-modal-root fixed inset-0 flex items-center justify-center p-3 sm:p-5" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="ws-debug-modal-title" aria-hidden="true">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-[2px]" data-ws-debug-close></div>
    <div class="relative z-10 flex h-full max-h-[100dvh] flex-col items-center justify-center overflow-hidden p-3 sm:p-5 pointer-events-none">
        <div class="pointer-events-auto flex max-h-[min(92vh,940px)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-amber-200/90 bg-amber-50/98 text-xs text-amber-950 shadow-2xl">
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-amber-200/80 bg-amber-100/60 px-4 py-3">
                <h2 id="ws-debug-modal-title" class="text-sm font-bold uppercase tracking-wide text-amber-900">Debug catalogue (APP_DEBUG)</h2>
                <button type="button" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-amber-900 hover:bg-amber-200/80 transition-colors" data-ws-debug-close aria-label="Fermer">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto overflow-x-hidden px-4 py-3 max-h-[calc(100dvh-11rem)] sm:max-h-[calc(100dvh-10rem)]">
                @if(!empty($catalogMeta['wp_connection_failed']))
                    <p class="text-red-800 font-semibold">Connexion WordPress indisponible — aucune ligne catalogue.</p>
                @else
                    <p class="text-[11px] text-amber-900/90 mb-2 leading-relaxed">
                        <strong>wp_tours</strong> = nombre de tours WordPress (comme Circuits / voyages). <strong>total_rows</strong> = packages + vols + hébergements dans le tableau.
                    </p>
                    <p class="font-mono leading-relaxed text-[11px]">
                        wp_tours={{ (int) ($catalogMeta['wp_tour_count'] ?? 0) }},
                        laravel_matched={{ (int) ($catalogMeta['laravel_voyage_matched'] ?? 0) }},
                        packages={{ (int) ($catalogMeta['package_rows'] ?? 0) }},
                        vols={{ (int) ($catalogMeta['vol_rows'] ?? 0) }},
                        hébergements={{ (int) ($catalogMeta['hebergement_rows'] ?? 0) }},
                        total_rows={{ (int) ($catalogMeta['total_rows'] ?? ($catalogTotalCount ?? $catalogRows->count())) }}
                    </p>
                    @php
                        $p = (int) ($catalogMeta['package_rows'] ?? 0);
                        $v = (int) ($catalogMeta['vol_rows'] ?? 0);
                        $h = (int) ($catalogMeta['hebergement_rows'] ?? 0);
                        $chk = $p + $v + $h;
                        $tot = (int) ($catalogMeta['total_rows'] ?? $catalogRows->count());
                    @endphp
                    @if($chk === $tot && $p > 0)
                        <p class="text-[11px] text-emerald-800 mt-2 font-medium">Vérification : {{ $p }} + {{ $v }} + {{ $h }} = {{ $tot }} (cohérent).</p>
                    @endif
                    @if(!empty($catalogMeta['wp_tour_ids']))
                        <p class="text-[10px] font-mono mt-2 break-all text-amber-950/90"><span class="font-sans font-bold text-amber-800">IDs WP (ordre Circuits / voyages, desc ID)</span> : {{ implode(', ', $catalogMeta['wp_tour_ids']) }}</p>
                    @endif
                    @if(!empty($catalogMeta['laravel_wp_post_ids_matched']))
                        <p class="text-[10px] font-mono mt-1 break-all text-amber-950/90"><span class="font-sans font-bold text-amber-800">wp_post_id Laravel liés</span> : {{ implode(', ', $catalogMeta['laravel_wp_post_ids_matched']) }}</p>
                    @endif
                    @if(!empty($catalogMeta['wp_tour_ids_without_laravel']))
                        <p class="text-[10px] font-mono mt-1 break-all text-amber-900"><span class="font-sans font-bold">Tours WP sans fiche Laravel</span> : {{ implode(', ', $catalogMeta['wp_tour_ids_without_laravel']) }}</p>
                    @endif
                    @if(!empty($catalogMeta['laravel_duplicates_wp_post_id']))
                        <p class="text-[10px] mt-2 text-red-800 font-semibold">Doublons voyages.wp_post_id (plusieurs lignes Laravel) :</p>
                        <pre class="text-[10px] font-mono mt-1 overflow-x-auto bg-white/60 rounded-lg p-2 border border-red-100">{{ json_encode($catalogMeta['laravel_duplicates_wp_post_id'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                    @if(!empty($catalogMeta['package_price_debug']))
                        <p class="text-[10px] font-bold text-amber-900 mt-3 mb-1">Prix packages (meta WP <code class="font-mono">adult_price</code> = colonne Prix Adulte Circuits / voyages)</p>
                        <div class="overflow-x-auto max-h-64 overflow-y-auto border border-amber-200/80 rounded-lg bg-white/70">
                            <table class="w-full text-[9px] font-mono">
                                <thead class="bg-amber-100/80 text-left">
                                    <tr>
                                        <th class="p-1.5">WP ID</th>
                                        <th class="p-1.5">meta brut</th>
                                        <th class="p-1.5">parsé</th>
                                        <th class="p-1.5">Laravel price_from</th>
                                        <th class="p-1.5">Affiché</th>
                                        <th class="p-1.5">Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($catalogMeta['package_price_debug'] as $d)
                                        <tr class="border-t border-amber-100">
                                            <td class="p-1.5 align-top">{{ $d['wp_post_id'] ?? '' }}</td>
                                            <td class="p-1.5 align-top break-all max-w-[100px]">{{ is_scalar($d['adult_price_meta_raw'] ?? null) ? $d['adult_price_meta_raw'] : json_encode($d['adult_price_meta_raw']) }}</td>
                                            <td class="p-1.5 align-top">{{ $d['parsed_wp_adult'] ?? '—' }}</td>
                                            <td class="p-1.5 align-top">{{ $d['laravel_price_from'] ?? '—' }}</td>
                                            <td class="p-1.5 align-top font-bold">{{ $d['price_label_final'] ?? '—' }}</td>
                                            <td class="p-1.5 align-top">{{ $d['price_source'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if(!empty($catalogMeta['package_departure_debug']))
                        @if(!empty($catalogMeta['package_departure_source_doc']))
                            <p class="text-[10px] text-slate-700 mt-3 mb-1 leading-snug">{{ $catalogMeta['package_departure_source_doc'] }}</p>
                        @endif
                        <p class="text-[10px] font-bold text-amber-900 mb-1">Départs packages (Disponibilité = <code class="font-mono">aj_travel_dates</code> / <code class="font-mono">TravelDate</code>)</p>
                        <div class="overflow-x-auto max-h-72 overflow-y-auto border border-amber-200/80 rounded-lg bg-white/70">
                            <table class="w-full text-[9px] font-mono">
                                <thead class="bg-amber-100/80 text-left">
                                    <tr>
                                        <th class="p-1.5">WP ID</th>
                                        <th class="p-1.5">Laravel #</th>
                                        <th class="p-1.5">Dates actives (liste)</th>
                                        <th class="p-1.5">ID retenu</th>
                                        <th class="p-1.5">Date affichée</th>
                                        <th class="p-1.5">Passé</th>
                                        <th class="p-1.5">Flags</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($catalogMeta['package_departure_debug'] as $dd)
                                        <tr class="border-t border-amber-100">
                                            <td class="p-1.5 align-top">{{ $dd['wp_post_id'] ?? '' }}</td>
                                            <td class="p-1.5 align-top">{{ $dd['laravel_voyage_id'] ?? '—' }}</td>
                                            <td class="p-1.5 align-top break-all max-w-[140px]">{{ !empty($dd['active_travel_dates_ymd']) ? implode(', ', $dd['active_travel_dates_ymd']) : '—' }}</td>
                                            <td class="p-1.5 align-top">{{ $dd['picked_travel_date_id'] ?? '—' }}</td>
                                            <td class="p-1.5 align-top font-bold">{{ $dd['picked_date_ymd'] ?? '—' }}</td>
                                            <td class="p-1.5 align-top">{{ !empty($dd['workspace_display_is_past']) ? 'oui' : 'non' }}</td>
                                            <td class="p-1.5 align-top">
                                                @if(!empty($dd['no_laravel_voyage']))<span class="text-amber-800">sans Laravel</span>@endif
                                                @if(!empty($dd['no_availability_rows']))<span class="text-red-800">aucune dispo</span>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if(!empty($catalogMeta['package_places_debug']))
                        @if(!empty($catalogMeta['package_places_source_doc']))
                            <p class="text-[10px] text-slate-700 mt-3 mb-1 leading-snug">{{ $catalogMeta['package_places_source_doc'] }}</p>
                        @endif
                        <p class="text-[10px] font-bold text-amber-900 mb-1">Places / chambres (échantillon max 8 packages Laravel — même calcul que l’édition voyage)</p>
                        <pre class="text-[9px] font-mono mt-1 overflow-x-auto max-h-80 overflow-y-auto bg-white/70 rounded-lg p-2 border border-amber-200/80 whitespace-pre-wrap">{{ json_encode($catalogMeta['package_places_debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                @endif
            </div>
            <div class="flex shrink-0 justify-end gap-2 border-t border-amber-200/80 bg-amber-50/95 px-4 py-3">
                <button type="button" class="rounded-xl border border-amber-300 bg-white px-4 py-2 text-sm font-semibold text-amber-900 shadow-sm hover:bg-amber-50 transition-colors" data-ws-debug-close>Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function setupWsDebugModal() {
        var root = document.getElementById('ws-debug-modal');
        var openBtn = document.getElementById('ws-debug-modal-open');
        if (!root || !openBtn) return;
        function closeModal() {
            root.style.display = 'none';
            root.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
        function openModal() {
            root.style.display = 'flex';
            root.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
        openBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openModal();
        });
        root.querySelectorAll('[data-ws-debug-close]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                closeModal();
            });
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape' || !root) return;
            if (root.style.display === 'none' || root.getAttribute('aria-hidden') === 'true') return;
            closeModal();
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setupWsDebugModal);
    } else {
        setupWsDebugModal();
    }
})();
</script>
@endif
