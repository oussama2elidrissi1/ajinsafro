@extends('layouts.master-ajinsafro')
@section('title')
    Calendrier des départs
@endsection
@section('content')
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="page-title mb-0 font-size-18">Calendrier des départs</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.reservations.index') }}">Réservations</a></li>
                        <li class="breadcrumb-item active">Calendrier</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label for="voyage-filter" class="form-label small mb-1">Filtrer par voyage</label>
                            <select id="voyage-filter" name="voyage" class="form-select form-select-sm">
                                <option value="">Tous les voyages</option>
                                @foreach($voyages as $voyage)
                                    <option value="{{ $voyage->id }}" {{ (int) ($selectedVoyageId ?? 0) === $voyage->id ? 'selected' : '' }}>
                                        {{ $voyage->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" id="refresh-calendar" class="btn btn-sm btn-primary mt-3">
                                <i class="bx bx-refresh"></i> Actualiser
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="reservations-calendar"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <!-- Calendar libs (déjà utilisés par la démo) -->
    <script src="{{ URL::asset('build/libs/moment/min/moment.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/jquery-ui-dist/jquery-ui.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/fullcalendar/index.global.min.js') }}"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('reservations-calendar');
            if (!calendarEl || typeof FullCalendar === 'undefined') {
                return;
            }

            var voyageFilter = document.getElementById('voyage-filter');
            var refreshBtn = document.getElementById('refresh-calendar');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                firstDay: 1,
                height: 'auto',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek,listWeek'
                },
                events: {
                    url: '{{ route('admin.reservations.calendrier.events') }}',
                    method: 'GET',
                    extraParams: function () {
                        return {
                            voyage: voyageFilter && voyageFilter.value ? voyageFilter.value : ''
                        };
                    },
                    failure: function () {
                        console.error('Impossible de charger les événements du calendrier.');
                    }
                },
                eventClick: function (info) {
                    if (info.event.url) {
                        window.open(info.event.url, '_blank');
                        info.jsEvent.preventDefault();
                    }
                },
                eventDidMount: function (info) {
                    var props = info.event.extendedProps || {};
                    var pieces = [];
                    if (props.destination) {
                        pieces.push('Destination : ' + props.destination);
                    }
                    if (props.price_from && props.currency_symbol) {
                        pieces.push('Prix dès ' + props.price_from + ' ' + props.currency_symbol);
                    }
                    if (pieces.length) {
                        info.el.title = pieces.join('\\n');
                    }
                }
            });

            calendar.render();

            if (voyageFilter) {
                voyageFilter.addEventListener('change', function () {
                    calendar.refetchEvents();
                });
            }
            if (refreshBtn) {
                refreshBtn.addEventListener('click', function () {
                    calendar.refetchEvents();
                });
            }
        });
    </script>

    <script src="{{ URL::asset('build/js/app.js') }}"></script>
@endpush
