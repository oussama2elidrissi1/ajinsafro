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

<div id="ws-debug-modal" class="ws-debug-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="ws-debug-modal-title" aria-hidden="true">
    <div class="ws-debug-modal__backdrop" data-ws-debug-close tabindex="-1" aria-hidden="true"></div>
    <div class="ws-debug-modal__shell">
        <div class="ws-debug-modal__panel">
            <header class="ws-debug-modal__header">
                <h2 id="ws-debug-modal-title" class="ws-debug-modal__title">DEBUG CATALOGUE</h2>
                <button type="button" class="ws-debug-modal__close" data-ws-debug-close aria-label="Fermer">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </header>
            <div class="ws-debug-modal__body">
                @if(!empty($catalogMeta['wp_connection_failed']))
                    <p class="ws-debug-modal__alert">Connexion WordPress indisponible — aucune ligne catalogue.</p>
                @else
                    <p class="ws-debug-modal__intro">
                        <strong>wp_tours</strong> = nombre de tours WordPress (comme Circuits / voyages). <strong>total_rows</strong> = packages + vols + hébergements dans le tableau.
                    </p>
                    <p class="ws-debug-modal__mono">
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
                        <p class="ws-debug-modal__ok">Vérification : {{ $p }} + {{ $v }} + {{ $h }} = {{ $tot }} (cohérent).</p>
                    @endif
                    @if(!empty($catalogMeta['wp_tour_ids']))
                        <p class="ws-debug-modal__mono ws-debug-modal__break"><span class="ws-debug-modal__label">IDs WP (ordre Circuits / voyages, desc ID)</span> : {{ implode(', ', $catalogMeta['wp_tour_ids']) }}</p>
                    @endif
                    @if(!empty($catalogMeta['laravel_wp_post_ids_matched']))
                        <p class="ws-debug-modal__mono ws-debug-modal__break"><span class="ws-debug-modal__label">wp_post_id Laravel liés</span> : {{ implode(', ', $catalogMeta['laravel_wp_post_ids_matched']) }}</p>
                    @endif
                    @if(!empty($catalogMeta['wp_tour_ids_without_laravel']))
                        <p class="ws-debug-modal__mono ws-debug-modal__break"><span class="ws-debug-modal__label">Tours WP sans fiche Laravel</span> : {{ implode(', ', $catalogMeta['wp_tour_ids_without_laravel']) }}</p>
                    @endif
                    @if(!empty($catalogMeta['laravel_duplicates_wp_post_id']))
                        <p class="ws-debug-modal__warn-title">Doublons voyages.wp_post_id (plusieurs lignes Laravel) :</p>
                        <pre class="ws-debug-modal__pre">{{ json_encode($catalogMeta['laravel_duplicates_wp_post_id'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                    @if(!empty($catalogMeta['package_price_debug']))
                        <p class="ws-debug-modal__section-title">Prix packages (meta WP <code>adult_price</code> = colonne Prix Adulte Circuits / voyages)</p>
                        <div class="ws-debug-table-wrap">
                            <table class="ws-debug-table">
                                <thead>
                                    <tr>
                                        <th>WP ID</th>
                                        <th>meta brut</th>
                                        <th>parsé</th>
                                        <th>Laravel price_from</th>
                                        <th>Affiché</th>
                                        <th>Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($catalogMeta['package_price_debug'] as $d)
                                        <tr>
                                            <td>{{ $d['wp_post_id'] ?? '' }}</td>
                                            <td class="ws-debug-table__cell-tight">{{ is_scalar($d['adult_price_meta_raw'] ?? null) ? $d['adult_price_meta_raw'] : json_encode($d['adult_price_meta_raw']) }}</td>
                                            <td>{{ $d['parsed_wp_adult'] ?? '—' }}</td>
                                            <td>{{ $d['laravel_price_from'] ?? '—' }}</td>
                                            <td><strong>{{ $d['price_label_final'] ?? '—' }}</strong></td>
                                            <td>{{ $d['price_source'] ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if(!empty($catalogMeta['package_departure_debug']))
                        @if(!empty($catalogMeta['package_departure_source_doc']))
                            <p class="ws-debug-modal__doc">{{ $catalogMeta['package_departure_source_doc'] }}</p>
                        @endif
                        <p class="ws-debug-modal__section-title">Départs packages (Disponibilité = <code>aj_travel_dates</code> / <code>TravelDate</code>)</p>
                        <div class="ws-debug-table-wrap">
                            <table class="ws-debug-table">
                                <thead>
                                    <tr>
                                        <th>WP ID</th>
                                        <th>Laravel #</th>
                                        <th>Dates actives</th>
                                        <th>ID retenu</th>
                                        <th>Date affichée</th>
                                        <th>Passé</th>
                                        <th>Flags</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($catalogMeta['package_departure_debug'] as $dd)
                                        <tr>
                                            <td>{{ $dd['wp_post_id'] ?? '' }}</td>
                                            <td>{{ $dd['laravel_voyage_id'] ?? '—' }}</td>
                                            <td class="ws-debug-table__cell-tight">{{ !empty($dd['active_travel_dates_ymd']) ? implode(', ', $dd['active_travel_dates_ymd']) : '—' }}</td>
                                            <td>{{ $dd['picked_travel_date_id'] ?? '—' }}</td>
                                            <td><strong>{{ $dd['picked_date_ymd'] ?? '—' }}</strong></td>
                                            <td>{{ !empty($dd['workspace_display_is_past']) ? 'oui' : 'non' }}</td>
                                            <td>
                                                @if(!empty($dd['no_laravel_voyage']))<span class="ws-debug-tag ws-debug-tag--warn">sans Laravel</span>@endif
                                                @if(!empty($dd['no_availability_rows']))<span class="ws-debug-tag ws-debug-tag--err">aucune dispo</span>@endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    @if(!empty($catalogMeta['package_places_debug']))
                        @if(!empty($catalogMeta['package_places_source_doc']))
                            <p class="ws-debug-modal__doc">{{ $catalogMeta['package_places_source_doc'] }}</p>
                        @endif
                        <p class="ws-debug-modal__section-title">Places / chambres (échantillon max 8 packages Laravel — même calcul que l’édition voyage)</p>
                        <pre class="ws-debug-modal__pre ws-debug-modal__pre--json">{{ json_encode($catalogMeta['package_places_debug'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    @endif
                @endif
            </div>
            <footer class="ws-debug-modal__footer">
                <button type="button" class="ws-debug-modal__footer-btn" data-ws-debug-close>Fermer</button>
            </footer>
        </div>
    </div>
</div>

<script>
(function () {
    function setupWsDebugModal() {
        var root = document.getElementById('ws-debug-modal');
        var openBtn = document.getElementById('ws-debug-modal-open');
        if (!root || !openBtn) return;
        function lockScroll() {
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
        }
        function unlockScroll() {
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        }
        function closeModal() {
            root.style.display = 'none';
            root.setAttribute('aria-hidden', 'true');
            unlockScroll();
        }
        function openModal() {
            root.style.display = 'flex';
            root.setAttribute('aria-hidden', 'false');
            lockScroll();
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
