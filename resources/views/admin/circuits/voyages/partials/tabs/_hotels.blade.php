<div class="tab-pane" id="hotels" role="tabpanel">
                @php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                @endphp
                <p class="alert alert-info py-2 mb-3 small"><i class="bx bx-info-circle"></i> <strong>HÃ´tels</strong> "â€ Vous pouvez ajouter plusieurs hÃ´tels et les associer ÃƒÂ  un jour spÃ©cifique du circuit.</p>
                <h5 class="mb-3" id="tour-hotels-title"><i class="bx bx-hotel"></i> HÃ´tel(s) <span id="tour-hotels-period">(sÃ©jour "â€ check-in J1, check-out J{{ $lastDayNumber }})</span></h5>
                <div id="tour-hotels-anchor">
                    @include('admin.circuits.voyages.partials._tour_hotels_section')
                </div>
                <p class="text-muted small mt-3">Les images s'affichent sur la fiche circuit (site WordPress).</p>
            </div>

