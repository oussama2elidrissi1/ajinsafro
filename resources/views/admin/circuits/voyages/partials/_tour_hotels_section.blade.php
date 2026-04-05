@php
    $lastDayNumber = isset($lastDayNumber) ? $lastDayNumber : (($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int) ($meta['duration_day'] ?? 1)));
    $hotelsList = $tourHotels->isEmpty() ? [null] : $tourHotels->all();
    $otherHotels = $otherTourHotelsForCopy ?? collect();
    $otherTitles = $otherTourTitles ?? [];
@endphp

<div id="tour-hotels-wrapper">
    @if($otherHotels->isNotEmpty())
        <div class="mb-3 p-3 bg-light rounded">
            <label class="form-label small mb-1">Choisir un hotel existant</label>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <select class="form-select form-select-sm" id="copy-from-hotel-select" style="max-width: 380px;">
                    <option value="">Selectionner</option>
                    @foreach($otherHotels as $oh)
                        <option value="{{ $oh->tour_id }}">{{ \Str::limit($oh->hotel_name ?: 'Hotel #'.$oh->tour_id, 40) }} - {{ \Str::limit($otherTitles[$oh->tour_id] ?? 'Voyage '.$oh->tour_id, 35) }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-sm btn-outline-primary" id="copy-from-hotel-btn">Charger</button>
            </div>
        </div>
    @endif

    <div id="tour-hotels-container">
        @foreach($hotelsList as $hi => $h)
            @php
                $hid = 'tour_hotel_image_id_' . $hi;
                $himg = optional($h)->image_id;
                $himgUrl = $himg ? \App\Services\Wp\WpHeroImageService::getAttachmentUrl((int) $himg) : '';
                $oldDayNumber = old("tour_hotels.{$hi}.day_number", optional($h)->day_number);
                $checkInDay = old("tour_hotels.{$hi}.check_in_day", optional($h)->check_in_day);
                $checkOutDay = old("tour_hotels.{$hi}.check_out_day", optional($h)->check_out_day);
                if (! $checkInDay && $oldDayNumber) {
                    $checkInDay = $oldDayNumber;
                }
                if (! $checkOutDay && $oldDayNumber) {
                    $checkOutDay = $oldDayNumber;
                }
                $checkInDay = $checkInDay ?: 1;
                $checkOutDay = $checkOutDay ?: 1;
            @endphp
            <div class="card mb-3 tour-hotel-row" data-index="{{ $hi }}" data-hotel-id="{{ optional($h)->id ?? '' }}">
                @if(optional($h)->id)
                    <input type="hidden" name="tour_hotels[{{ $hi }}][id]" value="{{ $h->id }}">
                @endif

                <div class="card-header bg-light py-3 d-flex justify-content-between align-items-start gap-3">
                    <div class="tour-hotel-card-heading">
                        <div class="tour-hotel-card-title">Hotel {{ $hi + 1 }}</div>
                        <div class="tour-hotel-card-meta text-muted small"></div>
                    </div>
                    @if($hi > 0)
                        <button type="button" class="btn btn-sm btn-outline-danger tour-remove-row" aria-label="Supprimer">×</button>
                    @endif
                </div>

                <div class="card-body tour-hotel-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="tour-hotel-section-heading">Affectation au circuit</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jour check-in</label>
                            <select class="form-select tour-hotel-check-in" name="tour_hotels[{{ $hi }}][check_in_day]" data-index="{{ $hi }}">
                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                    <option value="{{ $d }}" {{ (int) $checkInDay === $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                @endfor
                            </select>
                            <small class="text-danger d-none tour-hotel-check-in-error" data-index="{{ $hi }}">Le check-out doit etre superieur ou egal au check-in.</small>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Jour check-out</label>
                            <select class="form-select tour-hotel-check-out" name="tour_hotels[{{ $hi }}][check_out_day]" data-index="{{ $hi }}">
                                @for($d = 1; $d <= $lastDayNumber; $d++)
                                    <option value="{{ $d }}" {{ (int) $checkOutDay === $d ? 'selected' : '' }}>Jour {{ $d }}</option>
                                @endfor
                            </select>
                            <small class="text-danger d-none tour-hotel-check-out-error" data-index="{{ $hi }}">Le check-out doit etre superieur ou egal au check-in.</small>
                        </div>

                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" name="tour_hotels[{{ $hi }}][is_optional]" value="1" {{ old("tour_hotels.{$hi}.is_optional", optional($h)->is_optional ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label">Option client</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="tour-hotel-section-heading">Informations generales</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Nom de l'hotel</label>
                            <input type="text" class="form-control tour-hotel-name-input" name="tour_hotels[{{ $hi }}][hotel_name]" value="{{ old("tour_hotels.{$hi}.hotel_name", optional($h)->hotel_name ?? '') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Etoiles (0-5)</label>
                            <input type="number" class="form-control tour-hotel-stars-input" name="tour_hotels[{{ $hi }}][stars]" value="{{ old("tour_hotels.{$hi}.stars", optional($h)->stars ?? '') }}" min="0" max="5">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Ville / adresse</label>
                            <input type="text" class="form-control tour-hotel-address-input" name="tour_hotels[{{ $hi }}][address]" value="{{ old("tour_hotels.{$hi}.address", optional($h)->address ?? '') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Formule / infos</label>
                            <input type="text" class="form-control tour-hotel-meal-input" name="tour_hotels[{{ $hi }}][meal_plan]" value="{{ old("tour_hotels.{$hi}.meal_plan", optional($h)->meal_plan ?? '') }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="tour_hotels[{{ $hi }}][notes]" rows="2">{{ old("tour_hotels.{$hi}.notes", optional($h)->notes ?? '') }}</textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Image</label>
                            <input type="hidden" name="tour_hotels[{{ $hi }}][image_id]" id="{{ $hid }}" value="{{ old("tour_hotels.{$hi}.image_id", optional($h)->image_id ?? '') }}">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div id="{{ $hid }}_preview_wrap" class="border rounded overflow-hidden bg-light" style="width: 120px; height: 80px; display: {{ $himgUrl ? 'flex' : 'none' }};">
                                    <img id="{{ $hid }}_preview" src="{{ $himgUrl }}" alt="" class="img-fluid" style="max-width:100%; max-height:100%; object-fit: cover;">
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary ajtb-logistique-media-btn" data-target="tour_hotel" data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap"><i class="bx bx-images"></i> Choisir</button>
                                    <button type="button" class="btn btn-sm btn-outline-danger ajtb-logistique-media-remove" data-input="{{ $hid }}" data-preview="{{ $hid }}_preview" data-preview-wrap="{{ $hid }}_preview_wrap">×</button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-sm btn-soft-primary mb-4" id="tour-add-hotel"><i class="bx bx-plus"></i> Ajouter un hotel</button>
</div>

<script>
(function () {
    var wrapper = document.getElementById('tour-hotels-wrapper');
    var container = document.getElementById('tour-hotels-container');
    var addBtn = document.getElementById('tour-add-hotel');
    if (!wrapper || !container || !addBtn) return;
    if (container.dataset.initialized === 'true') return;
    container.dataset.initialized = 'true';

    function notifyHotelsChanged() {
        document.dispatchEvent(new CustomEvent('voyage-hotels-changed'));
    }

    function hotelStayLabel(row) {
        var checkInSel = row.querySelector('.tour-hotel-check-in');
        var checkOutSel = row.querySelector('.tour-hotel-check-out');
        if (!checkInSel || !checkOutSel) return '';
        var checkIn = parseInt(checkInSel.value || '1', 10);
        var checkOut = parseInt(checkOutSel.value || '1', 10);
        return checkIn === checkOut ? 'Sejour Jour ' + checkIn : 'Sejour J' + checkIn + ' a J' + checkOut;
    }

    function refreshHotelCard(row) {
        if (!row) return;
        var index = parseInt(row.getAttribute('data-index') || '0', 10) + 1;
        var titleEl = row.querySelector('.tour-hotel-card-title');
        var metaEl = row.querySelector('.tour-hotel-card-meta');
        var nameInp = row.querySelector('.tour-hotel-name-input');
        var starsInp = row.querySelector('.tour-hotel-stars-input');
        var addressInp = row.querySelector('.tour-hotel-address-input');
        var mealInp = row.querySelector('.tour-hotel-meal-input');
        var optionalInp = row.querySelector('input[name^="tour_hotels["][name$="[is_optional]"]');
        var hotelName = nameInp && nameInp.value.trim() ? nameInp.value.trim() : 'Hotel ' + index;
        var meta = [];

        if (titleEl) titleEl.textContent = hotelName;
        meta.push(hotelStayLabel(row));

        var stars = parseInt(starsInp && starsInp.value ? starsInp.value : '0', 10);
        if (!isNaN(stars) && stars > 0) meta.push(stars + ' etoile' + (stars > 1 ? 's' : ''));
        if (mealInp && mealInp.value.trim()) meta.push(mealInp.value.trim());
        if (addressInp && addressInp.value.trim()) meta.push(addressInp.value.trim());
        if (optionalInp && optionalInp.checked) meta.push('Option client');

        if (metaEl) metaEl.textContent = meta.filter(Boolean).join(' • ');
    }

    function updateHotelsTitle() {
        var titleEl = document.getElementById('tour-hotels-period');
        var rows = container.querySelectorAll('.tour-hotel-row');
        if (!titleEl) return;
        if (!rows.length) {
            titleEl.textContent = '(aucun hotel configure)';
            return;
        }

        var minCheckIn = null;
        var maxCheckOut = null;
        rows.forEach(function (row) {
            refreshHotelCard(row);
            var checkInSel = row.querySelector('.tour-hotel-check-in');
            var checkOutSel = row.querySelector('.tour-hotel-check-out');
            if (!checkInSel || !checkOutSel) return;
            var checkIn = parseInt(checkInSel.value || '1', 10);
            var checkOut = parseInt(checkOutSel.value || '1', 10);
            if (minCheckIn === null || checkIn < minCheckIn) minCheckIn = checkIn;
            if (maxCheckOut === null || checkOut > maxCheckOut) maxCheckOut = checkOut;
        });

        if (minCheckIn !== null && maxCheckOut !== null) {
            titleEl.textContent = minCheckIn === maxCheckOut
                ? '(sejour • Jour ' + minCheckIn + ')'
                : '(sejour • check-in J' + minCheckIn + ', check-out J' + maxCheckOut + ')';
        }
    }

    function validateCheckInOut(row) {
        var checkInSel = row.querySelector('.tour-hotel-check-in');
        var checkOutSel = row.querySelector('.tour-hotel-check-out');
        var index = row.getAttribute('data-index');
        if (!checkInSel || !checkOutSel) return;
        var checkInError = row.querySelector('.tour-hotel-check-in-error[data-index="' + index + '"]');
        var checkOutError = row.querySelector('.tour-hotel-check-out-error[data-index="' + index + '"]');
        var checkIn = parseInt(checkInSel.value || '1', 10);
        var checkOut = parseInt(checkOutSel.value || '1', 10);
        if (checkOut < checkIn) {
            checkOutSel.value = String(checkIn);
            if (checkInError) checkInError.classList.remove('d-none');
            if (checkOutError) checkOutError.classList.remove('d-none');
            window.setTimeout(function () {
                if (checkInError) checkInError.classList.add('d-none');
                if (checkOutError) checkOutError.classList.add('d-none');
            }, 2500);
        } else {
            if (checkInError) checkInError.classList.add('d-none');
            if (checkOutError) checkOutError.classList.add('d-none');
        }
    }

    function reindexRows() {
        container.querySelectorAll('.tour-hotel-row').forEach(function (row, i) {
            row.setAttribute('data-index', i);
            row.querySelectorAll('[name^="tour_hotels["]').forEach(function (input) {
                input.name = input.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + i + ']');
            });
            row.querySelectorAll('.tour-hotel-check-in, .tour-hotel-check-out, .tour-hotel-check-in-error, .tour-hotel-check-out-error').forEach(function (el) {
                el.setAttribute('data-index', i);
            });
            row.querySelectorAll('[id^="tour_hotel_image_id_"]').forEach(function (el) {
                el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + i);
            });
            row.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function (btn) {
                if (btn.getAttribute('data-input')) btn.setAttribute('data-input', 'tour_hotel_image_id_' + i);
                if (btn.getAttribute('data-preview')) btn.setAttribute('data-preview', 'tour_hotel_image_id_' + i + '_preview');
                if (btn.getAttribute('data-preview-wrap')) btn.setAttribute('data-preview-wrap', 'tour_hotel_image_id_' + i + '_preview_wrap');
            });
            refreshHotelCard(row);
        });
    }

    addBtn.addEventListener('click', function () {
        var rows = container.querySelectorAll('.tour-hotel-row');
        var last = rows[rows.length - 1];
        if (!last) return;
        var nextIndex = rows.length;
        var clone = last.cloneNode(true);
        clone.setAttribute('data-index', nextIndex);
        clone.setAttribute('data-hotel-id', '');
        clone.querySelectorAll('input[name^="tour_hotels["][name*="[id]"]').forEach(function (input) {
            input.remove();
        });
        clone.querySelectorAll('[name]').forEach(function (input) {
            if (input.name.indexOf('tour_hotels[') === 0) {
                input.name = input.name.replace(/tour_hotels\[\d+\]/, 'tour_hotels[' + nextIndex + ']');
            }
            if (input.type === 'checkbox') {
                input.checked = false;
                return;
            }
            if (input.tagName === 'TEXTAREA') {
                input.value = '';
                return;
            }
            if (input.name.indexOf('[check_in_day]') !== -1 || input.name.indexOf('[check_out_day]') !== -1) {
                input.value = '1';
                input.setAttribute('data-index', nextIndex);
                return;
            }
            if (input.type !== 'hidden') {
                input.value = '';
            }
        });
        clone.querySelectorAll('[id]').forEach(function (el) {
            if (el.id.indexOf('tour_hotel_image_id_') === 0) {
                el.id = el.id.replace(/tour_hotel_image_id_\d+/, 'tour_hotel_image_id_' + nextIndex);
            }
        });
        clone.querySelectorAll('.ajtb-logistique-media-btn, .ajtb-logistique-media-remove').forEach(function (btn) {
            if (btn.getAttribute('data-input')) btn.setAttribute('data-input', 'tour_hotel_image_id_' + nextIndex);
            if (btn.getAttribute('data-preview')) btn.setAttribute('data-preview', 'tour_hotel_image_id_' + nextIndex + '_preview');
            if (btn.getAttribute('data-preview-wrap')) btn.setAttribute('data-preview-wrap', 'tour_hotel_image_id_' + nextIndex + '_preview_wrap');
        });
        var previewWrap = clone.querySelector('[id$="_preview_wrap"]');
        if (previewWrap) previewWrap.style.display = 'none';
        if (!clone.querySelector('.tour-remove-row')) {
            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline-danger tour-remove-row';
            removeBtn.setAttribute('aria-label', 'Supprimer');
            removeBtn.textContent = '×';
            clone.querySelector('.card-header').appendChild(removeBtn);
        }
        container.appendChild(clone);
        refreshHotelCard(clone);
        updateHotelsTitle();
        notifyHotelsChanged();
    });

    container.addEventListener('click', function (event) {
        if (!event.target.classList.contains('tour-remove-row')) return;
        var row = event.target.closest('.tour-hotel-row');
        if (!row || container.querySelectorAll('.tour-hotel-row').length <= 1) return;
        row.remove();
        reindexRows();
        updateHotelsTitle();
        notifyHotelsChanged();
    });

    container.addEventListener('input', function (event) {
        if (!event.target || !event.target.name || event.target.name.indexOf('tour_hotels[') !== 0) return;
        refreshHotelCard(event.target.closest('.tour-hotel-row'));
        updateHotelsTitle();
        notifyHotelsChanged();
    });

    container.addEventListener('change', function (event) {
        if (!event.target || !event.target.name || event.target.name.indexOf('tour_hotels[') !== 0) return;
        validateCheckInOut(event.target.closest('.tour-hotel-row'));
        refreshHotelCard(event.target.closest('.tour-hotel-row'));
        updateHotelsTitle();
        notifyHotelsChanged();
    });

    var copySelect = document.getElementById('copy-from-hotel-select');
    var copyBtn = document.getElementById('copy-from-hotel-btn');
    if (copySelect && copyBtn) {
        var dataUrlBase = '{{ url("admin/circuits/tour-hotels") }}';
        copyBtn.addEventListener('click', function () {
            var tourId = copySelect.value;
            if (!tourId) return;
            fetch(dataUrlBase + '/' + tourId + '/data', { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    var firstRow = container.querySelector('.tour-hotel-row[data-index="0"]');
                    if (!firstRow) return;
                    var hotel = data.hotel || {};
                    function setValue(nameSuffix, value) {
                        var field = firstRow.querySelector('[name="tour_hotels[0][' + nameSuffix + ']"]');
                        if (!field || value === undefined || value === null) return;
                        if (field.type === 'checkbox') {
                            field.checked = !!value;
                        } else {
                            field.value = value;
                        }
                    }
                    setValue('hotel_name', hotel.hotel_name);
                    setValue('stars', hotel.stars);
                    setValue('address', hotel.address);
                    setValue('meal_plan', hotel.meal_plan);
                    setValue('notes', hotel.notes);
                    setValue('image_id', hotel.image_id);
                    setValue('check_in_day', hotel.check_in_day);
                    setValue('check_out_day', hotel.check_out_day);
                    setValue('is_optional', hotel.is_optional ? '1' : '');
                    refreshHotelCard(firstRow);
                    updateHotelsTitle();
                    notifyHotelsChanged();
                    copySelect.value = '';
                })
                .catch(function () {
                    alert('Impossible de charger l hotel.');
                });
        });
    }

    container.querySelectorAll('.tour-hotel-row').forEach(function (row) {
        validateCheckInOut(row);
        refreshHotelCard(row);
    });
    updateHotelsTitle();
    notifyHotelsChanged();
})();
</script>
