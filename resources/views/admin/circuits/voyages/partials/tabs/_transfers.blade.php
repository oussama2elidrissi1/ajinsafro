<div class="tab-pane" id="transfers" role="tabpanel">
                @php
                    $lastDayNumber = ($programDays && $programDays->isNotEmpty()) ? $programDays->count() : max(1, (int)($meta['duration_day'] ?? 1));
                @endphp
                <h5 class="mb-3"><i class="bx bx-car"></i> Transferts (plusieurs par jour possible)</h5>

                <div id="tour-transfers-anchor">
                    @include('admin.circuits.voyages.partials._tour_transfers_section')
                </div>
            </div>

