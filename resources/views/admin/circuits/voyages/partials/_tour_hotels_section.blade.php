@php
    $lastDayNumber = isset($lastDayNumber) ? $lastDayNumber : (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int) ($meta['duration_day'] ?? 1)));
    $hotelsList = $tourHotels->isEmpty() ? [null] : $tourHotels->all();
    $otherHotels = $otherTourHotelsForCopy ?? collect();
    $wpSiteUrl = rtrim((string) config('wordpress.site_url', ''), '/');
    $wpHotelsDataset = $otherHotels
        ->map(function ($hotel) {
            return [
                'id' => (int) ($hotel->wp_post_id ?? 0),
                'name' => trim((string) ($hotel->hotel_name ?? '')),
                'address' => trim((string) ($hotel->address ?? '')),
                'destination' => trim((string) ($hotel->destination ?? ($hotel->location ?? ''))),
                'stars' => is_numeric($hotel->stars ?? null) ? (int) $hotel->stars : null,
                'image_url' => trim((string) ($hotel->image_url ?? '')),
                'wp_url' => trim((string) ($hotel->wp_url ?? '')),
            ];
        })
        ->filter(function ($hotel) { return ($hotel['id'] ?? 0) > 0; })
        ->values()
        ->all();
@endphp

<div id="tour-hotels-wrapper" data-wp-site-url="{{ $wpSiteUrl }}">

    @if($otherHotels->isNotEmpty())
        <datalist id="tour-hotels-wp-datalist">
            @foreach($otherHotels as $oh)
                @php
                    $wpHotelId = (int) ($oh->wp_post_id ?? 0);
                    $hotelLabel = trim((string) ($oh->hotel_name ?? '')) !== '' ? (string) $oh->hotel_name : ('Hotel #' . $wpHotelId);
                    $hotelAddress = trim((string) ($oh->address ?? ''));
                @endphp
                @continue($wpHotelId <= 0)
                <option value="{{ $wpHotelId }} - {{ $hotelLabel }}{{ $hotelAddress !== '' ? (' - ' . $hotelAddress) : '' }}"></option>
            @endforeach
        </datalist>
    @endif

    <div id="tour-hotels-container">
        @foreach($hotelsList as $hi => $h)
            @php
                $hid = 'tour_hotel_image_id_' . $hi;
                $himg = old("tour_hotels.{$hi}.image_id", optional($h)->image_id);
                $himgUrl = $himg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int) $himg) : '';
                $oldDayNumber = old("tour_hotels.{$hi}.day_number", optional($h)->day_number);
                $checkInDay = old("tour_hotels.{$hi}.check_in_day", optional($h)->check_in_day);
                $checkOutDay = old("tour_hotels.{$hi}.check_out_day", optional($h)->check_out_day);
                if (! $checkInDay && $oldDayNumber) { $checkInDay = $oldDayNumber; }
                if (! $checkOutDay && $oldDayNumber) { $checkOutDay = $oldDayNumber; }
                $checkInDay  = (int) ($checkInDay ?: 1);
                $checkOutDay = (int) ($checkOutDay ?: $checkInDay);
                $nuits = max(0, $checkOutDay - $checkInDay);
                $linkedWpHotelId = (int) (old("tour_hotels.{$hi}.hotel_id", old("tour_hotels.{$hi}.source_hotel_id", '')) ?: 0);
                $initialHotelName = trim((string) old("tour_hotels.{$hi}.hotel_name", optional($h)->hotel_name ?? ''));
                $initialStars = (int) (old("tour_hotels.{$hi}.stars", optional($h)->stars ?? 0) ?: 0);
                $initialAddress = trim((string) old("tour_hotels.{$hi}.address", optional($h)->address ?? ''));
                $initialMealPlan = trim((string) old("tour_hotels.{$hi}.meal_plan", optional($h)->meal_plan ?? ''));
                $initialSummaryImageUrl = $himgUrl ?: '';
                $initialWpUrl = ($linkedWpHotelId > 0 && $wpSiteUrl !== '')
                    ? ($wpSiteUrl . '/?post_type=st_hotel&p=' . $linkedWpHotelId)
                    : '';
                $initialSearchValue = $linkedWpHotelId > 0
                    ? ($linkedWpHotelId . ' - ' . ($initialHotelName !== '' ? $initialHotelName : ('Hebergement #' . $linkedWpHotelId)))
                    : '';
            @endphp
            <div class="card mb-3 border tour-hotel-row"
                data-index="{{ $hi }}"
                data-hotel-id="{{ optional($h)->id ?? '' }}"
                data-source-hotel-id="{{ old("tour_hotels.{$hi}.source_hotel_id", '') }}">
                @if(optional($h)->id)
                    <input type="hidden" name="tour_hotels[{{ $hi }}][id]" value="{{ $h->id }}">
                @endif
                <input type="hidden" name="tour_hotels[{{ $hi }}][hotel_id]" value="{{ old("tour_hotels.{$hi}.hotel_id", '') }}">
                <input type="hidden" name="tour_hotels[{{ $hi }}][source_hotel_id]" value="{{ old("tour_hotels.{$hi}.source_hotel_id", '') }}">

                {{-- En-tête de la carte séjour --}}
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-start gap-3">
                    <div class="tour-hotel-card-heading lh-sm">
                        <div class="tour-hotel-card-title fw-semibold">Séjour {{ $hi + 1 }}</div>
                        <div class="tour-hotel-card-meta text-muted small mt-1"></div>
                    </div>
                    @if($hi > 0)
                        <button type="button" class="btn btn-sm btn-outline-danger tour-remove-row flex-shrink-0" aria-label="Supprimer ce séjour">
                            <i class="bx bx-trash"></i>
                        </button>
                    @endif
                </div>

                <div class="card-body tour-hotel-card-body">
                    <div class="row g-3">

                        {{-- ?"??"? SECTION : Période du séjour ?"??"? --}}
                        <div class="col-12">
                            <p class="text-uppercase text-muted fw-semibold small mb-0" style="font-size:.7rem;letter-spacing:.05em;">Période du séjour</p>
                            <hr class="mt-1 mb-2">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">Jour check-in</label>
                            <select class="form-select form-select-sm tour-hotel-check-in" name="tour_hotels[{{ $hi }}][check_in_day]" data-index="{{ $hi }}">
                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                    <option value="{{ $d }}" {{ $checkInDay === $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small mb-1">Jour check-out</label>
                            <select class="form-select form-select-sm tour-hotel-check-out" name="tour_hotels[{{ $hi }}][check_out_day]" data-index="{{ $hi }}">
                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                    <option value="{{ $d }}" {{ $checkOutDay === $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                @endfor
                            </select>
                            <small class="text-danger d-none tour-hotel-checkout-error">Check-out ?? check-in requis.</small>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <span class="tour-hotel-nights-badge badge bg-light text-dark border fw-normal px-3 py-2">
                                <i class="bx bx-moon"></i>
                                <span class="tour-hotel-nights-count">{{ $nuits }}</span> nuit{{ $nuits !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        <div class="col-md-3 d-flex align-items-end pb-1">
                            <div class="form-check mb-1">
                                <input type="checkbox" class="form-check-input" name="tour_hotels[{{ $hi }}][is_optional]" value="1" {{ old("tour_hotels.{$hi}.is_optional", optional($h)->is_optional ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label small">Option client</label>
                            </div>
                        </div>

                        {{-- ?"??"? SECTION : Identification de l'hôtel ?"??"? --}}
                        <div class="col-12 mt-2">
                            <p class="text-uppercase text-muted fw-semibold small mb-0" style="font-size:.7rem;letter-spacing:.05em;">Identification de l'hôtel</p>
                            <hr class="mt-1 mb-2">
                        </div>

                        <div class="col-12">
                            <div class="tour-hotel-wp-link-box border rounded-3 p-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-8">
                                        <label class="form-label small mb-1 fw-semibold">Lier un hebergement existant</label>
                                        <input type="text"
                                            class="form-control form-control-sm tour-hotel-wp-search"
                                            list="tour-hotels-wp-datalist"
                                            value="{{ $initialSearchValue }}"
                                            placeholder="Recherche rapide (nom ou ID)...">
                                        <div class="form-text">Utilisez le modal pour une selection complete, ou cette recherche rapide si vous connaissez deja l'hotel.</div>
                                    </div>
                                    <div class="col-lg-4 d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-primary tour-hotel-open-modal-btn flex-grow-1">
                                            <i class="bx bx-building-house"></i> Choisir un hotel
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary tour-hotel-link-wp-btn" title="Appliquer la recherche rapide">
                                            <i class="bx bx-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary tour-hotel-unlink-wp-btn {{ $linkedWpHotelId > 0 ? '' : 'd-none' }}">
                                            <i class="bx bx-unlink"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="tour-hotel-linked-summary mt-3 {{ $linkedWpHotelId > 0 ? '' : 'd-none' }}" data-linked-id="{{ $linkedWpHotelId > 0 ? $linkedWpHotelId : '' }}">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="tour-hotel-linked-summary-image-wrap {{ $initialSummaryImageUrl !== '' ? '' : 'd-none' }}">
                                            <img src="{{ $initialSummaryImageUrl }}" alt="" class="tour-hotel-linked-summary-image">
                                        </div>
                                        <div class="tour-hotel-linked-summary-content">
                                            <div class="tour-hotel-linked-summary-name fw-semibold">{{ $initialHotelName !== '' ? $initialHotelName : ($linkedWpHotelId > 0 ? ('Hebergement #' . $linkedWpHotelId) : '') }}</div>
                                            <div class="tour-hotel-linked-summary-meta text-muted small">
                                                {{ collect([
                                                    $initialStars > 0 ? ($initialStars . ' etoile' . ($initialStars > 1 ? 's' : '')) : '',
                                                    $initialAddress,
                                                    $initialMealPlan,
                                                ])->filter()->implode(' - ') }}
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <span class="badge rounded-pill bg-soft-primary text-primary tour-hotel-linked-summary-id">
                                                    {{ $linkedWpHotelId > 0 ? ('#' . $linkedWpHotelId) : '' }}
                                                </span>
                                                <a href="{{ $initialWpUrl }}" target="_blank" rel="noopener" class="small tour-hotel-linked-summary-link {{ $initialWpUrl !== '' ? '' : 'd-none' }}">
                                                    Ouvrir dans WordPress
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small mb-1">Nom de l'hôtel</label>
                            <input type="text" class="form-control form-control-sm tour-hotel-name-input tour-hotel-identity-field"
                                name="tour_hotels[{{ $hi }}][hotel_name]"
                                value="{{ old("tour_hotels.{$hi}.hotel_name", optional($h)->hotel_name ?? '') }}"
                                placeholder="Ex : Ibis Marrakech Centre">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small mb-1">?toiles</label>
                            <input type="number" class="form-control form-control-sm tour-hotel-stars-input tour-hotel-identity-field"
                                name="tour_hotels[{{ $hi }}][stars]"
                                value="{{ old("tour_hotels.{$hi}.stars", optional($h)->stars ?? '') }}"
                                min="0" max="5" placeholder="0?5">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small mb-1">Ville / adresse</label>
                            <input type="text" class="form-control form-control-sm tour-hotel-address-input tour-hotel-identity-field"
                                name="tour_hotels[{{ $hi }}][address]"
                                value="{{ old("tour_hotels.{$hi}.address", optional($h)->address ?? '') }}"
                                placeholder="Ex : Marrakech, Médina">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small mb-1">Formule / pension</label>
                            <input type="text" class="form-control form-control-sm tour-hotel-meal-input tour-hotel-identity-field"
                                name="tour_hotels[{{ $hi }}][meal_plan]"
                                value="{{ old("tour_hotels.{$hi}.meal_plan", optional($h)->meal_plan ?? '') }}"
                                placeholder="Ex : Petit-déjeuner inclus">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small mb-1">Notes internes</label>
                            <textarea class="form-control form-control-sm tour-hotel-identity-field"
                                name="tour_hotels[{{ $hi }}][notes]"
                                rows="2"
                                placeholder="Informations complémentaires pour l'équipe">{{ old("tour_hotels.{$hi}.notes", optional($h)->notes ?? '') }}</textarea>
                        </div>

                        {{-- Image --}}
                        <div class="col-12 tour-hotel-media-block">
                            <label class="form-label small mb-1">Photo de l'hôtel</label>
                            <input type="hidden" class="tour-hotel-identity-field" name="tour_hotels[{{ $hi }}][image_id]" id="{{ $hid }}" value="{{ old("tour_hotels.{$hi}.image_id", optional($h)->image_id ?? '') }}">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div id="{{ $hid }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width:120px;height:80px;display:{{ $himgUrl ? 'flex' : 'none' }};">
                                    <img id="{{ $hid }}_preview" src="{{ $himgUrl }}" alt="" class="img-fluid" style="max-width:100%;max-height:100%;object-fit:cover;">
                                </div>
                                <div class="tour-hotel-media-actions d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary ajtb-logistique-media-btn"
                                        data-target="tour_hotel" data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap">
                                        <i class="bx bx-images"></i> Choisir une photo
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove"
                                        data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap">
                                        <i class="bx bx-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>{{-- /row --}}
                </div>{{-- /card-body --}}
            </div>{{-- /card --}}
        @endforeach
    </div>{{-- /tour-hotels-container --}}

    <button type="button" class="btn btn-sm btn-soft-primary mb-4" id="tour-add-hotel">
        <i class="bx bx-plus"></i> Ajouter un séjour hôtel
    </button>
</div>{{-- /tour-hotels-wrapper --}}

<div class="modal fade" id="tour-hotel-picker-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content tour-hotel-picker-modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">Selectionner un hotel WordPress</h5>
                    <p class="text-muted small mb-0" id="tour-hotel-picker-context">Choisissez un hebergement a attacher au sejour courant.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="row g-2 align-items-end mb-3">
                    <div class="col-lg-5">
                        <label for="tour-hotel-picker-search" class="form-label small mb-1">Recherche</label>
                        <input type="text" id="tour-hotel-picker-search" class="form-control form-control-sm" placeholder="Nom, adresse ou ID WordPress...">
                    </div>
                    <div class="col-lg-3">
                        <label for="tour-hotel-picker-city" class="form-label small mb-1">Ville / destination</label>
                        <select id="tour-hotel-picker-city" class="form-select form-select-sm">
                            <option value="">Toutes les destinations</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <label for="tour-hotel-picker-stars" class="form-label small mb-1">Etoiles</label>
                        <select id="tour-hotel-picker-stars" class="form-select form-select-sm">
                            <option value="">Toutes</option>
                            <option value="5">5 etoiles</option>
                            <option value="4">4 etoiles</option>
                            <option value="3">3 etoiles</option>
                            <option value="2">2 etoiles</option>
                            <option value="1">1 etoile</option>
                            <option value="0">0 etoile</option>
                        </select>
                    </div>
                    <div class="col-lg-2 d-grid">
                        <button type="button" class="btn btn-sm btn-light border" id="tour-hotel-picker-reset">
                            <i class="bx bx-reset"></i> Reinitialiser
                        </button>
                    </div>
                </div>

                <div class="tour-hotel-picker-results" id="tour-hotel-picker-results"></div>
                <p class="text-muted small mt-2 mb-0 d-none" id="tour-hotel-picker-empty">Aucun hotel ne correspond aux filtres.</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var wrapper = document.getElementById('tour-hotels-wrapper');
    var container = document.getElementById('tour-hotels-container');
    var addBtn = document.getElementById('tour-add-hotel');
    if (!wrapper || !container || !addBtn) return;
    if (container.dataset.initialized === 'true') return;
    container.dataset.initialized = 'true';

    var wpDataBase = '{{ url("admin/circuits/wp-hotels") }}';
    var wpSiteUrl = (wrapper.getAttribute('data-wp-site-url') || '').replace(/\/+$/, '');
    var wpHotelsDataset = @json($wpHotelsDataset);

    var pickerModalEl = document.getElementById('tour-hotel-picker-modal');
    var pickerContextEl = document.getElementById('tour-hotel-picker-context');
    var pickerSearchInput = document.getElementById('tour-hotel-picker-search');
    var pickerCitySelect = document.getElementById('tour-hotel-picker-city');
    var pickerStarsSelect = document.getElementById('tour-hotel-picker-stars');
    var pickerResetBtn = document.getElementById('tour-hotel-picker-reset');
    var pickerResultsEl = document.getElementById('tour-hotel-picker-results');
    var pickerEmptyEl = document.getElementById('tour-hotel-picker-empty');
    function createModalController(modalEl) {
        if (!modalEl) return null;

        if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
            var bs5Instance = typeof window.bootstrap.Modal.getOrCreateInstance === 'function'
                ? window.bootstrap.Modal.getOrCreateInstance(modalEl)
                : new window.bootstrap.Modal(modalEl);
            return {
                mode: 'bs5',
                show: function () { bs5Instance.show(); },
                hide: function () { bs5Instance.hide(); },
                onHidden: function (callback) { modalEl.addEventListener('hidden.bs.modal', callback); }
            };
        }

        if (window.jQuery && typeof window.jQuery(modalEl).modal === 'function') {
            var $modal = window.jQuery(modalEl);
            return {
                mode: 'jq',
                show: function () { $modal.modal('show'); },
                hide: function () { $modal.modal('hide'); },
                onHidden: function (callback) { $modal.on('hidden.bs.modal', callback); }
            };
        }

        var fallbackBackdrop = null;
        var hiddenHandlers = [];
        function removeFallbackBackdrop() {
            if (!fallbackBackdrop) return;
            fallbackBackdrop.remove();
            fallbackBackdrop = null;
        }
        return {
            mode: 'fallback',
            show: function () {
                modalEl.style.display = 'block';
                modalEl.removeAttribute('aria-hidden');
                modalEl.setAttribute('aria-modal', 'true');
                modalEl.classList.add('show');
                document.body.classList.add('modal-open');
                if (!fallbackBackdrop) {
                    fallbackBackdrop = document.createElement('div');
                    fallbackBackdrop.className = 'modal-backdrop fade show';
                    document.body.appendChild(fallbackBackdrop);
                }
            },
            hide: function () {
                modalEl.classList.remove('show');
                modalEl.setAttribute('aria-hidden', 'true');
                modalEl.removeAttribute('aria-modal');
                modalEl.style.display = 'none';
                document.body.classList.remove('modal-open');
                removeFallbackBackdrop();
                hiddenHandlers.forEach(function (handler) { handler(); });
            },
            onHidden: function (callback) {
                hiddenHandlers.push(callback);
            }
        };
    }
    var pickerModal = createModalController(pickerModalEl);
    var pickerActiveRow = null;

    if (pickerModalEl && pickerModal && pickerModal.mode === 'fallback') {
        pickerModalEl.addEventListener('click', function (e) {
            if (e.target === pickerModalEl || e.target.closest('[data-bs-dismiss="modal"]')) {
                pickerModal.hide();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Escape') return;
            if (!pickerModalEl.classList.contains('show')) return;
            pickerModal.hide();
        });
    }

    function setIdentityLocked(row, locked) {
        if (!row) return;
        row.setAttribute('data-identity-locked', locked ? '1' : '0');
        row.querySelectorAll('.tour-hotel-identity-field').forEach(function (el) {
            if (el.type === 'hidden') return;
            el.readOnly = !!locked;
            el.classList.toggle('bg-light', !!locked);
        });
        row.querySelectorAll('.tour-hotel-media-actions button').forEach(function (btn) {
            btn.disabled = !!locked;
            btn.classList.toggle('opacity-50', !!locked);
        });
    }

    function parseWpHotelId(raw) {
        var value = (raw || '').toString().trim();
        if (!value) return 0;
        if (/^\d+$/.test(value)) return parseInt(value, 10) || 0;
        var matched = value.match(/^(\d+)/);
        return matched ? (parseInt(matched[1], 10) || 0) : 0;
    }

    function resolveWpHotelUrl(hotelId, fallbackUrl) {
        if (fallbackUrl) return fallbackUrl;
        if (wpSiteUrl && hotelId > 0) return wpSiteUrl + '/?post_type=st_hotel&p=' + hotelId;
        return '';
    }

    function escapeHtml(value) {
        return (value || '').toString()
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizeText(value) {
        return (value || '').toString().trim().toLowerCase();
    }

    function getHotelDestination(hotel) {
        var destination = (hotel && hotel.destination ? hotel.destination : '').toString().trim();
        if (destination) return destination;
        var address = (hotel && hotel.address ? hotel.address : '').toString().trim();
        if (!address) return '';
        var firstChunk = address.split(',')[0] || '';
        return firstChunk.trim();
    }

    function getFilteredHotelsForPicker() {
        var search = normalizeText(pickerSearchInput ? pickerSearchInput.value : '');
        var city = normalizeText(pickerCitySelect ? pickerCitySelect.value : '');
        var starsFilter = (pickerStarsSelect ? pickerStarsSelect.value : '').toString().trim();

        return wpHotelsDataset.filter(function (hotel) {
            var destination = normalizeText(getHotelDestination(hotel));
            var stars = hotel && hotel.stars !== null && hotel.stars !== undefined
                ? parseInt(hotel.stars, 10)
                : null;

            if (city && destination !== city) return false;
            if (starsFilter !== '') {
                if (stars === null || isNaN(stars) || String(stars) !== starsFilter) return false;
            }

            if (!search) return true;
            var haystack = [
                hotel.id,
                hotel.name,
                hotel.address,
                hotel.destination,
            ].join(' ').toLowerCase();
            return haystack.indexOf(search) !== -1;
        });
    }

    function renderPickerCityOptions() {
        if (!pickerCitySelect) return;
        var values = {};
        wpHotelsDataset.forEach(function (hotel) {
            var destination = getHotelDestination(hotel);
            if (!destination) return;
            values[destination] = true;
        });
        var cities = Object.keys(values).sort(function (a, b) { return a.localeCompare(b, 'fr'); });
        var optionsHtml = '<option value="">Toutes les destinations</option>';
        cities.forEach(function (city) {
            optionsHtml += '<option value="' + escapeHtml(city) + '">' + escapeHtml(city) + '</option>';
        });
        pickerCitySelect.innerHTML = optionsHtml;
    }

    function buildHotelCard(hotel) {
        var destination = getHotelDestination(hotel);
        var stars = (hotel && hotel.stars !== null && hotel.stars !== undefined && hotel.stars !== '')
            ? parseInt(hotel.stars, 10) : null;

        var starsHtml = '';
        if (stars !== null && !isNaN(stars) && stars > 0) {
            var filled = Math.min(5, stars);
            starsHtml = '<div style="color:#f59e0b;font-size:.9rem;letter-spacing:.05em;margin-bottom:6px;">';
            for (var s = 0; s < filled; s++) starsHtml += '?~.';
            for (var s = filled; s < 5; s++) starsHtml += '<span style="opacity:.2">?~.</span>';
            starsHtml += '</div>';
        }

        var imageUrl = (hotel.image_url || '').toString().trim();
        var cardMedia = imageUrl
            ? '<div style="width:100%;height:160px;overflow:hidden;background:#f1f5f9;flex-shrink:0;">'
              + '<img src="' + escapeHtml(imageUrl) + '" alt="' + escapeHtml(hotel.name || '') + '" loading="lazy" '
              + 'style="width:100%;height:100%;object-fit:cover;display:block;transition:transform .25s;" '
              + 'onmouseover="this.style.transform=\'scale(1.05)\'" onmouseout="this.style.transform=\'scale(1)\'">'
              + '</div>'
            : '<div style="width:100%;height:160px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);display:flex;align-items:center;justify-content:center;flex-shrink:0;">'
              + '<i class="bx bx-building-house" style="font-size:2.5rem;color:#94a3b8;"></i>'
              + '</div>';

        return '<article class="tph-card" style="'
            + 'border:1px solid #dbeafe;border-radius:12px;background:#fff;overflow:hidden;'
            + 'display:flex;flex-direction:column;box-shadow:0 1px 3px rgba(0,0,0,.05);'
            + 'transition:all .2s;cursor:default;"'
            + 'onmouseover="this.style.borderColor=\'#0e3a5a\';this.style.transform=\'translateY(-2px)\';this.style.boxShadow=\'0 4px 12px rgba(14,58,90,.15)\'"'
            + 'onmouseout="this.style.borderColor=\'#dbeafe\';this.style.transform=\'translateY(0)\';this.style.boxShadow=\'0 1px 3px rgba(0,0,0,.05)\'">'
            + cardMedia
            + '<div style="padding:14px;display:flex;flex-direction:column;flex:1;min-width:0;">'
            +   '<div style="font-size:.95rem;font-weight:700;color:#0e3a5a;margin-bottom:4px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">'
            +     escapeHtml(hotel.name || ('Hébergement #' + hotel.id))
            +   '</div>'
            +   starsHtml
            +   (destination
                ? '<div style="font-size:.78rem;color:#475569;margin-bottom:8px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">'
                  + '<i class="bx bx-map-pin" style="font-size:.8rem;"></i> ' + escapeHtml(destination)
                  + '</div>'
                : '')
            +   '<div style="margin-top:auto;padding-top:10px;border-top:1px solid #f0f5ff;display:flex;align-items:center;justify-content:space-between;gap:8px;">'
            +     '<span style="font-size:.7rem;font-weight:600;padding:2px 8px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;color:#64748b;">WP #' + escapeHtml(String(hotel.id || '')) + '</span>'
            +     '<button type="button" class="btn btn-sm btn-primary tour-hotel-picker-choose-btn" data-hotel-id="' + escapeHtml(String(hotel.id || '')) + '" style="padding:4px 14px;">Choisir</button>'
            +   '</div>'
            + '</div>'
            + '</article>';
    }

    function renderPickerResults() {
        if (!pickerResultsEl || !pickerEmptyEl) return;
        var hotels = getFilteredHotelsForPicker();
        var maxResults = 120;
        var limited = hotels.slice(0, maxResults);

        if (!limited.length) {
            pickerResultsEl.innerHTML = '';
            pickerEmptyEl.classList.remove('d-none');
            return;
        }

        pickerEmptyEl.classList.add('d-none');

        // Build cards HTML
        var cardsHtml = '';
        if (hotels.length > maxResults) {
            cardsHtml += '<div style="grid-column:1/-1;font-size:.78rem;color:#64748b;padding:0 2px 4px;">Affichage des ' + maxResults + ' premiers résultats sur ' + hotels.length + '.</div>';
        }
        limited.forEach(function (hotel) {
            cardsHtml += buildHotelCard(hotel);
        });

        // Wrap ALL cards in a single grid container (inline style ? not dependent on ancestor class)
        var gridHtml = '<div style="'
            + 'display:grid;'
            + 'grid-template-columns:repeat(auto-fill,minmax(260px,1fr));'
            + 'gap:16px;'
            + 'width:100%;'
            + 'padding:4px 0;'
            + '">'
            + cardsHtml
            + '</div>';

        pickerResultsEl.innerHTML = gridHtml;

        // Debug (remove after confirmation)
        console.log('[HotelPicker] grid rendered ? cards:', limited.length, '? first child tag:', pickerResultsEl.firstElementChild && pickerResultsEl.firstElementChild.tagName);
    }

    function openHotelPickerForRow(row) {
        if (!row) return;
        if (!pickerModalEl || !pickerModal) {
            alert('Le modal de selection est indisponible sur cette page.');
            return;
        }
        pickerActiveRow = row;
        var rowIndex = parseInt(row.getAttribute('data-index') || '0', 10) + 1;
        if (pickerContextEl) {
            pickerContextEl.textContent = 'Selection pour le sejour ' + rowIndex + '.';
        }
        if (pickerSearchInput) pickerSearchInput.value = '';
        if (pickerStarsSelect) pickerStarsSelect.value = '';
        if (pickerCitySelect) pickerCitySelect.value = '';
        renderPickerResults();
        pickerModal.show();
        if (pickerSearchInput) {
            window.setTimeout(function () { pickerSearchInput.focus(); }, 120);
        }
    }

    function getRowIndex(row) {
        return row ? (row.getAttribute('data-index') || '0') : '0';
    }

    function getRowField(row, suffix) {
        if (!row) return null;
        var index = getRowIndex(row);
        return row.querySelector('[name="tour_hotels[' + index + '][' + suffix + ']"]');
    }

    function setRowField(row, suffix, value) {
        var field = getRowField(row, suffix);
        if (!field || value === undefined || value === null) return;
        if (field.type === 'checkbox') {
            field.checked = !!value;
            return;
        }
        field.value = value;
    }

    function notify() {
        document.dispatchEvent(new CustomEvent('voyage-hotels-changed'));
    }

    function setRowImageFromPayload(row, imageId, imageUrl) {
        if (!row) return;
        var imageInput = getRowField(row, 'image_id');
        if (!imageInput) return;
        imageInput.value = imageId || '';

        var imageInputId = imageInput.id || '';
        if (!imageInputId) return;

        var preview = document.getElementById(imageInputId + '_preview');
        var previewWrap = document.getElementById(imageInputId + '_preview_wrap');
        if (preview) preview.src = imageUrl || '';
        if (previewWrap) previewWrap.style.display = imageUrl ? 'flex' : 'none';
    }

    function getRowImageUrl(row) {
        var imageInput = getRowField(row, 'image_id');
        if (!imageInput || !imageInput.id) return '';
        var preview = document.getElementById(imageInput.id + '_preview');
        var previewWrap = document.getElementById(imageInput.id + '_preview_wrap');
        if (!preview || !preview.src) return '';
        if (previewWrap && previewWrap.style.display === 'none') return '';
        return preview.src;
    }

    function renderLinkedSummary(row, payload) {
        if (!row) return;
        var summary = row.querySelector('.tour-hotel-linked-summary');
        var unlinkBtn = row.querySelector('.tour-hotel-unlink-wp-btn');
        if (!summary) return;

        if (!payload || !payload.id) {
            summary.classList.add('d-none');
            summary.setAttribute('data-linked-id', '');
            if (unlinkBtn) unlinkBtn.classList.add('d-none');
            return;
        }

        summary.classList.remove('d-none');
        summary.setAttribute('data-linked-id', String(payload.id));
        if (unlinkBtn) unlinkBtn.classList.remove('d-none');

        var imageWrap = summary.querySelector('.tour-hotel-linked-summary-image-wrap');
        var image = summary.querySelector('.tour-hotel-linked-summary-image');
        if (image) {
            if (payload.imageUrl) {
                image.src = payload.imageUrl;
                if (imageWrap) imageWrap.classList.remove('d-none');
            } else {
                image.removeAttribute('src');
                if (imageWrap) imageWrap.classList.add('d-none');
            }
        }

        var nameEl = summary.querySelector('.tour-hotel-linked-summary-name');
        if (nameEl) nameEl.textContent = payload.name || ('Hebergement #' + payload.id);

        var metaParts = [];
        if (payload.stars) {
            var stars = parseInt(payload.stars, 10);
            if (!isNaN(stars) && stars > 0) metaParts.push(stars + ' etoile' + (stars > 1 ? 's' : ''));
        }
        if (payload.address) metaParts.push(payload.address);
        if (payload.mealPlan) metaParts.push(payload.mealPlan);

        var metaEl = summary.querySelector('.tour-hotel-linked-summary-meta');
        if (metaEl) metaEl.textContent = metaParts.join(' - ');

        var idEl = summary.querySelector('.tour-hotel-linked-summary-id');
        if (idEl) idEl.textContent = '#' + payload.id;

        var linkEl = summary.querySelector('.tour-hotel-linked-summary-link');
        if (linkEl) {
            var wpUrl = resolveWpHotelUrl(payload.id, payload.wpUrl || '');
            if (wpUrl) {
                linkEl.href = wpUrl;
                linkEl.classList.remove('d-none');
            } else {
                linkEl.removeAttribute('href');
                linkEl.classList.add('d-none');
            }
        }
    }

    function refreshCard(row) {
        if (!row) return;
        var idx = parseInt(row.getAttribute('data-index') || '0', 10) + 1;
        var titleEl = row.querySelector('.tour-hotel-card-title');
        var metaEl = row.querySelector('.tour-hotel-card-meta');
        var nameInp = row.querySelector('.tour-hotel-name-input');
        var starsInp = row.querySelector('.tour-hotel-stars-input');
        var addrInp = row.querySelector('.tour-hotel-address-input');
        var mealInp = row.querySelector('.tour-hotel-meal-input');
        var optInp = row.querySelector('input[name$="[is_optional]"]');
        var ciSel = row.querySelector('.tour-hotel-check-in');
        var coSel = row.querySelector('.tour-hotel-check-out');
        var nightBadge = row.querySelector('.tour-hotel-nights-count');

        var ci = parseInt((ciSel || {}).value || '1', 10);
        var co = parseInt((coSel || {}).value || '1', 10);
        var nights = Math.max(0, co - ci);

        if (nightBadge) {
            var badgeWrap = nightBadge.parentElement;
            nightBadge.textContent = nights;
            if (badgeWrap) {
                badgeWrap.textContent = '';
                var icon = document.createElement('i');
                icon.className = 'bx bx-moon me-1';
                badgeWrap.appendChild(icon);
                var span = document.createElement('span');
                span.className = 'tour-hotel-nights-count';
                span.textContent = nights;
                badgeWrap.appendChild(span);
                badgeWrap.appendChild(document.createTextNode(' nuit' + (nights !== 1 ? 's' : '')));
            }
        }

        var hotelName = nameInp && nameInp.value.trim() ? nameInp.value.trim() : 'Sejour ' + idx;
        if (titleEl) titleEl.textContent = hotelName;

        var meta = [];
        var periodLabel = ci === co ? ('Jour ' + ci) : ('J' + ci + ' -> J' + co + ' - ' + nights + ' nuit' + (nights !== 1 ? 's' : ''));
        meta.push(periodLabel);
        var stars = parseInt(starsInp && starsInp.value ? starsInp.value : '0', 10);
        if (!isNaN(stars) && stars > 0) meta.push(stars + ' etoile' + (stars > 1 ? 's' : ''));
        if (addrInp && addrInp.value.trim()) meta.push(addrInp.value.trim());
        if (mealInp && mealInp.value.trim()) meta.push(mealInp.value.trim());
        if (optInp && optInp.checked) meta.push('Option client');
        if (metaEl) metaEl.textContent = meta.filter(Boolean).join(' - ');
    }

    function syncLinkedSummaryFromRow(row) {
        if (!row) return;
        var linkedId = parseWpHotelId((getRowField(row, 'hotel_id') || {}).value || (getRowField(row, 'source_hotel_id') || {}).value);
        if (!linkedId) {
            renderLinkedSummary(row, null);
            return;
        }

        var name = ((row.querySelector('.tour-hotel-name-input') || {}).value || '').trim();
        var stars = ((row.querySelector('.tour-hotel-stars-input') || {}).value || '').trim();
        var address = ((row.querySelector('.tour-hotel-address-input') || {}).value || '').trim();
        var mealPlan = ((row.querySelector('.tour-hotel-meal-input') || {}).value || '').trim();

        renderLinkedSummary(row, {
            id: linkedId,
            name: name,
            stars: stars,
            address: address,
            mealPlan: mealPlan,
            imageUrl: getRowImageUrl(row),
            wpUrl: resolveWpHotelUrl(linkedId, '')
        });

        var searchInput = row.querySelector('.tour-hotel-wp-search');
        if (searchInput && !searchInput.value.trim()) {
            searchInput.value = linkedId + (name ? (' - ' + name) : '');
        }
    }

    function updateGlobalTitle() {
        var titleEl = document.getElementById('tour-hotels-period');
        var rows = container.querySelectorAll('.tour-hotel-row');
        if (!titleEl) return;
        if (!rows.length) {
            titleEl.textContent = '(aucun sejour configure)';
            return;
        }
        var minCI = null;
        var maxCO = null;
        rows.forEach(function (row) {
            refreshCard(row);
            var ci = parseInt((row.querySelector('.tour-hotel-check-in') || {}).value || '1', 10);
            var co = parseInt((row.querySelector('.tour-hotel-check-out') || {}).value || '1', 10);
            if (minCI === null || ci < minCI) minCI = ci;
            if (maxCO === null || co > maxCO) maxCO = co;
        });
        if (minCI !== null && maxCO !== null) {
            titleEl.textContent = minCI === maxCO ? ('(Jour ' + minCI + ')') : ('(J' + minCI + ' -> J' + maxCO + ')');
        }
    }

    function validateDays(row) {
        var ciSel = row.querySelector('.tour-hotel-check-in');
        var coSel = row.querySelector('.tour-hotel-check-out');
        var errEl = row.querySelector('.tour-hotel-checkout-error');
        if (!ciSel || !coSel) return;
        var ci = parseInt(ciSel.value || '1', 10);
        var co = parseInt(coSel.value || '1', 10);
        if (co < ci) {
            coSel.value = String(ci);
            if (errEl) {
                errEl.classList.remove('d-none');
                setTimeout(function () { errEl.classList.add('d-none'); }, 2500);
            }
        } else if (errEl) {
            errEl.classList.add('d-none');
        }
    }

    function reindex() {
        container.querySelectorAll('.tour-hotel-row').forEach(function (row, i) {
            row.setAttribute('data-index', i);
            row.querySelectorAll('[name^="tour_hotels["]').forEach(function (el) {
                el.name = el.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + i + ']');
            });
            row.querySelectorAll('.tour-hotel-check-in,.tour-hotel-check-out,.tour-hotel-checkout-error').forEach(function (el) {
                el.setAttribute('data-index', i);
            });
            row.querySelectorAll('[id^="tour_hotel_image_id_"]').forEach(function (el) {
                el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + i);
            });
            row.querySelectorAll('.ajtb-logistique-media-btn,.ajtb-logistique-media-remove').forEach(function (btn) {
                if (btn.dataset.input) btn.dataset.input = 'tour_hotel_image_id_' + i;
                if (btn.dataset.preview) btn.dataset.preview = 'tour_hotel_image_id_' + i + '_preview';
                if (btn.dataset.previewWrap) btn.dataset.previewWrap = 'tour_hotel_image_id_' + i + '_preview_wrap';
            });
            refreshCard(row);
            syncLinkedSummaryFromRow(row);
        });
    }

    function applyWpHotelPayloadToRow(row, hotelPayload) {
        if (!row || !hotelPayload) return;

        var linkedId = parseWpHotelId(hotelPayload.wp_post_id || hotelPayload.hotel_id || hotelPayload.source_hotel_id || hotelPayload.id);
        var resolvedAddress = hotelPayload.address || hotelPayload.city || hotelPayload.location || '';
        var resolvedNotes = hotelPayload.notes || hotelPayload.description || hotelPayload.post_excerpt || '';
        var resolvedWpUrl = resolveWpHotelUrl(linkedId, hotelPayload.wp_url || '');

        setRowField(row, 'hotel_name', hotelPayload.hotel_name || '');
        setRowField(row, 'stars', hotelPayload.stars || '');
        setRowField(row, 'address', resolvedAddress);
        setRowField(row, 'meal_plan', hotelPayload.meal_plan || '');
        setRowField(row, 'notes', resolvedNotes);
        setRowField(row, 'image_id', hotelPayload.image_id || '');
        setRowField(row, 'hotel_id', linkedId || '');
        setRowField(row, 'source_hotel_id', linkedId || '');
        setRowField(row, 'check_in_day', hotelPayload.check_in_day);
        setRowField(row, 'check_out_day', hotelPayload.check_out_day);
        setRowField(row, 'is_optional', hotelPayload.is_optional ? '1' : '');

        row.setAttribute('data-source-hotel-id', linkedId ? String(linkedId) : '');
        setRowImageFromPayload(row, hotelPayload.image_id || '', hotelPayload.image_url || '');

        var searchInput = row.querySelector('.tour-hotel-wp-search');
        if (searchInput) {
            searchInput.value = linkedId ? (linkedId + ' - ' + (hotelPayload.hotel_name || '')) : '';
        }

        renderLinkedSummary(row, {
            id: linkedId,
            name: hotelPayload.hotel_name || '',
            stars: hotelPayload.stars || '',
            address: resolvedAddress,
            mealPlan: hotelPayload.meal_plan || '',
            imageUrl: hotelPayload.image_url || '',
            wpUrl: resolvedWpUrl
        });

        validateDays(row);
        refreshCard(row);
        updateGlobalTitle();
        notify();
    }

    function fetchWpHotelData(wpHotelId) {
        return fetch(wpDataBase + '/' + wpHotelId + '/data', {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            if (!r.ok) throw new Error('http_error');
            return r.json();
        }).then(function (data) {
            return data && data.hotel ? data.hotel : null;
        });
    }

    function linkWpHotelToRow(row, wpHotelId, triggerBtn) {
        if (!row || !wpHotelId) return Promise.resolve(false);
        if (triggerBtn) triggerBtn.disabled = true;
        return fetchWpHotelData(wpHotelId)
            .then(function (hotelPayload) {
                if (!hotelPayload) {
                    alert('Aucune donnee hotel WordPress trouvee pour cette selection.');
                    return false;
                }
                applyWpHotelPayloadToRow(row, hotelPayload);
                return true;
            })
            .catch(function () {
                alert('Impossible de charger les donnees de cet hebergement WordPress.');
                return false;
            })
            .finally(function () {
                if (triggerBtn) triggerBtn.disabled = false;
            });
    }

    function unlinkWpHotelFromRow(row) {
        if (!row) return;
        setRowField(row, 'hotel_id', '');
        setRowField(row, 'source_hotel_id', '');
        row.setAttribute('data-source-hotel-id', '');
        var searchInput = row.querySelector('.tour-hotel-wp-search');
        if (searchInput) searchInput.value = '';
        renderLinkedSummary(row, null);
        refreshCard(row);
        updateGlobalTitle();
        notify();
    }

    addBtn.addEventListener('click', function () {
        var rows = container.querySelectorAll('.tour-hotel-row');
        var last = rows[rows.length - 1];
        if (!last) return;
        var nextIdx = rows.length;
        var defaultDay = parseInt((last.querySelector('.tour-hotel-check-out') || {}).value || '1', 10);
        if (isNaN(defaultDay) || defaultDay < 1) defaultDay = 1;

        var clone = last.cloneNode(true);
        clone.setAttribute('data-index', nextIdx);
        clone.setAttribute('data-hotel-id', '');
        clone.setAttribute('data-source-hotel-id', '');

        clone.querySelectorAll('input[name*="[id]"]').forEach(function (el) { el.remove(); });
        clone.querySelectorAll('[name]').forEach(function (el) {
            if (el.name.indexOf('tour_hotels[') === 0) {
                el.name = el.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + nextIdx + ']');
            }
            if (el.type === 'checkbox') { el.checked = false; return; }
            if (el.tagName === 'TEXTAREA') { el.value = ''; return; }
            if (el.name.indexOf('[check_in_day]') !== -1 || el.name.indexOf('[check_out_day]') !== -1) {
                el.value = String(defaultDay);
                el.setAttribute('data-index', nextIdx);
                return;
            }
            if (el.name.indexOf('[source_hotel_id]') !== -1 || el.name.indexOf('[hotel_id]') !== -1) {
                el.value = '';
                return;
            }
            if (el.type !== 'hidden') el.value = '';
        });

        clone.querySelectorAll('[id^="tour_hotel_image_id_"]').forEach(function (el) {
            el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + nextIdx);
        });
        clone.querySelectorAll('.ajtb-logistique-media-btn,.ajtb-logistique-media-remove').forEach(function (btn) {
            if (btn.dataset.input) btn.dataset.input = 'tour_hotel_image_id_' + nextIdx;
            if (btn.dataset.preview) btn.dataset.preview = 'tour_hotel_image_id_' + nextIdx + '_preview';
            if (btn.dataset.previewWrap) btn.dataset.previewWrap = 'tour_hotel_image_id_' + nextIdx + '_preview_wrap';
        });

        var prevWrap = clone.querySelector('[id$="_preview_wrap"]');
        if (prevWrap) prevWrap.style.display = 'none';

        var wpSearch = clone.querySelector('.tour-hotel-wp-search');
        if (wpSearch) wpSearch.value = '';
        renderLinkedSummary(clone, null);

        if (!clone.querySelector('.tour-remove-row')) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'btn btn-sm btn-outline-danger tour-remove-row flex-shrink-0';
            btn.setAttribute('aria-label', 'Supprimer ce sejour');
            btn.innerHTML = '<i class="bx bx-trash"></i>';
            clone.querySelector('.card-header').appendChild(btn);
        }

        setIdentityLocked(clone, false);
        container.appendChild(clone);
        refreshCard(clone);
        updateGlobalTitle();
        notify();
    });

    container.addEventListener('click', function (e) {
        var removeBtn = e.target.closest('.tour-remove-row');
        if (removeBtn) {
            var rowToRemove = e.target.closest('.tour-hotel-row');
            if (!rowToRemove || container.querySelectorAll('.tour-hotel-row').length <= 1) return;
            rowToRemove.remove();
            reindex();
            updateGlobalTitle();
            notify();
            return;
        }

        var openModalBtn = e.target.closest('.tour-hotel-open-modal-btn');
        if (openModalBtn) {
            var rowToOpen = e.target.closest('.tour-hotel-row');
            if (!rowToOpen) return;
            openHotelPickerForRow(rowToOpen);
            return;
        }

        var linkBtn = e.target.closest('.tour-hotel-link-wp-btn');
        if (linkBtn) {
            var rowToLink = e.target.closest('.tour-hotel-row');
            if (!rowToLink) return;
            var searchInput = rowToLink.querySelector('.tour-hotel-wp-search');
            var wpHotelId = parseWpHotelId(searchInput ? searchInput.value : '');
            if (!wpHotelId) {
                alert('Selectionnez un hebergement valide (ex: 14758 - Nom).');
                return;
            }
            linkWpHotelToRow(rowToLink, wpHotelId, linkBtn);
            return;
        }

        var unlinkBtn = e.target.closest('.tour-hotel-unlink-wp-btn');
        if (unlinkBtn) {
            var rowToUnlink = e.target.closest('.tour-hotel-row');
            unlinkWpHotelFromRow(rowToUnlink);
        }
    });

    container.addEventListener('keydown', function (e) {
        if (!e.target.classList || !e.target.classList.contains('tour-hotel-wp-search')) return;
        if (e.key !== 'Enter') return;
        e.preventDefault();
        var row = e.target.closest('.tour-hotel-row');
        if (!row) return;
        var btn = row.querySelector('.tour-hotel-link-wp-btn');
        if (btn) btn.click();
    });

    container.addEventListener('input', function (e) {
        var row = e.target.closest('.tour-hotel-row');
        if (!row) return;
        if (!e.target || !e.target.name || e.target.name.indexOf('tour_hotels[') !== 0) return;
        refreshCard(row);
        syncLinkedSummaryFromRow(row);
        updateGlobalTitle();
        notify();
    });

    container.addEventListener('change', function (e) {
        var row = e.target.closest('.tour-hotel-row');
        if (!row) return;
        if (!e.target || !e.target.name || e.target.name.indexOf('tour_hotels[') !== 0) return;
        validateDays(row);
        refreshCard(row);
        syncLinkedSummaryFromRow(row);
        updateGlobalTitle();
        notify();
    });

    if (pickerSearchInput) {
        pickerSearchInput.addEventListener('input', renderPickerResults);
    }
    if (pickerCitySelect) {
        pickerCitySelect.addEventListener('change', renderPickerResults);
    }
    if (pickerStarsSelect) {
        pickerStarsSelect.addEventListener('change', renderPickerResults);
    }
    if (pickerResetBtn) {
        pickerResetBtn.addEventListener('click', function () {
            if (pickerSearchInput) pickerSearchInput.value = '';
            if (pickerCitySelect) pickerCitySelect.value = '';
            if (pickerStarsSelect) pickerStarsSelect.value = '';
            renderPickerResults();
        });
    }
    if (pickerResultsEl) {
        pickerResultsEl.addEventListener('click', function (e) {
            var chooseBtn = e.target.closest('.tour-hotel-picker-choose-btn');
            if (!chooseBtn) return;
            if (!pickerActiveRow) return;
            var wpHotelId = parseWpHotelId(chooseBtn.getAttribute('data-hotel-id') || '');
            if (!wpHotelId) return;
            linkWpHotelToRow(pickerActiveRow, wpHotelId, chooseBtn).then(function (ok) {
                if (ok && pickerModal) pickerModal.hide();
            });
        });
    }
    if (pickerModal && typeof pickerModal.onHidden === 'function') {
        pickerModal.onHidden(function () {
            pickerActiveRow = null;
        });
    }

    renderPickerCityOptions();
    renderPickerResults();

    container.querySelectorAll('.tour-hotel-row').forEach(function (row) {
        validateDays(row);
        syncLinkedSummaryFromRow(row);
        refreshCard(row);
    });
    updateGlobalTitle();
    notify();
})();
</script>

