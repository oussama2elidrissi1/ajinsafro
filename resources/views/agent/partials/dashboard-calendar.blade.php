@props(['calendarEvents' => []])

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/main.min.css">
@endpush

<div class="bg-white rounded-2xl shadow-custom border border-gray-100 p-5 mt-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 border-b border-gray-100 pb-3">
        <h3 class="font-bold text-[#0e3a5a] flex items-center gap-2">
            <i class="far fa-calendar-alt text-[#0083c4]"></i>
            Calendrier des départs (réservations avec date de voyage)
        </h3>
        <p class="text-[11px] text-gray-500">Basé sur les dates de voyage liées à vos dossiers visibles.</p>
    </div>
    <div id="agent-dashboard-calendar" class="min-h-[420px] w-full"></div>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var el = document.getElementById('agent-dashboard-calendar');
            if (!el || typeof FullCalendar === 'undefined') return;
            var raw = @json($calendarEvents);
            var calendar = new FullCalendar.Calendar(el, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek' },
                buttonText: { today: "Aujourd'hui", month: 'Mois', week: 'Semaine' },
                height: 'auto',
                events: raw
            });
            calendar.render();
        });
    </script>
@endpush
