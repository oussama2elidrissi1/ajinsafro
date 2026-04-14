<div class="tab-pane" id="hotels" role="tabpanel" data-ve-pane-title="Hôtels">
                @php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                @endphp
                <h5 class="mb-3" id="tour-hotels-title"><i class="bx bx-hotel"></i> Hôtels du voyage <span id="tour-hotels-period">(séjour — check-in J1, check-out J{{ $lastDayNumber }})</span></h5>
                <div id="tour-hotels-anchor">
                    @include('admin.circuits.voyages.partials._tour_hotels_section')
                </div>
                @include('admin.circuits.voyages.partials._departure_room_allocations')
            </div>
