@props([
    'days' => [],
])

@php
    $days = $days instanceof \Illuminate\Support\Collection ? $days->values() : collect($days)->values();
@endphp

@if($days->isEmpty())
    <p class="text-gray-600 rounded-lg bg-gray-100 px-4 py-3">L’itinéraire détaillé sera bientôt disponible.</p>
@else
<section id="itinerary" class="itinerary" aria-label="Programme du circuit">
    <div class="flex flex-col lg:flex-row items-start justify-start gap-3 lg:gap-2.5">
        {{-- Colonne gauche : Plan de séjour (timeline) — mobile: barre horizontale scrollable --}}
        <nav class="itinerary__nav day-plan-nav lg:sticky lg:top-24 flex flex-col lg:w-[240px] lg:flex-none lg:shrink-0 py-1 pr-0 mr-0 min-w-0 max-lg:flex-row max-lg:overflow-x-auto max-lg:gap-2 max-lg:pb-2 max-lg:mb-2" aria-label="Plan de séjour">
            <h3 class="day-plan__title text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3 px-0 max-lg:mb-0 max-lg:shrink-0 max-lg:mr-1">Plan de séjour</h3>
            <ul class="day-plan list-none m-0 p-0 pl-5 relative before:content-[''] before:absolute before:left-[5px] before:top-2 before:bottom-2 before:w-px before:bg-gray-200 max-lg:flex max-lg:flex-row max-lg:flex-nowrap max-lg:p-0 max-lg:before:hidden max-lg:shrink-0 max-lg:gap-2">
                @foreach($days as $index => $day)
                    @php
                        $dayNum = $day->day_number ?? ($index + 1);
                        $isFree = ($day->day_type ?? '') === 'libre';
                        $label = $isFree ? 'Jour libre' : (trim($day->title ?? '') !== '' ? \Illuminate\Support\Str::limit($day->title, 24) : 'Jour ' . $dayNum);
                        $isActive = $index === 0;
                    @endphp
                    <li class="day-plan__item relative max-lg:shrink-0">
                        <button
                            type="button"
                            class="day-plan__link aj-day-nav-item flex items-center gap-2.5 w-full py-1.5 pr-2.5 pl-0 border-0 bg-transparent text-left cursor-pointer rounded-full text-sm text-gray-800 transition-colors hover:bg-gray-100 {{ $isActive ? 'active is-active bg-blue-600 text-white hover:bg-blue-600' : '' }}"
                            data-day-index="{{ $index }}"
                            data-day="{{ $dayNum }}"
                            data-aj-nav-day="{{ $dayNum }}"
                            id="aj-day-nav-{{ $dayNum }}"
                        >
                            <span class="day-plan__dot shrink-0 w-2 h-2 rounded-full {{ $isActive ? 'bg-white' : 'bg-gray-300' }}"></span>
                            <span class="day-plan__label truncate">{{ e($label) }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Colonne droite : Détails par jour --}}
        <div class="itinerary__content min-w-0 flex-1 space-y-4 pl-0 ml-0">
            @foreach($days as $index => $day)
                @php
                    $dayNum = $day->day_number ?? ($index + 1);
                @endphp
                <div
                    class="ajtb-day-content-panel day-card rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden"
                    id="aj-day-panel-{{ $dayNum }}"
                    data-aj-day-panel="{{ $dayNum }}"
                    data-day="{{ $dayNum }}"
                    data-day-index="{{ $index }}"
                    role="tabpanel"
                    aria-labelledby="aj-day-nav-{{ $dayNum }}"
                >
                    <x-front.itinerary-day :day="$day" :isFirst="$index === 0" />
                </div>
            @endforeach
        </div>
    </div>

    <script>
        (function() {
            var section = document.getElementById('itinerary');
            if (!section) return;
            var tabs = section.querySelectorAll('[data-aj-nav-day]');
            if (!tabs.length) return;
            function setActive(dayNum) {
                dayNum = String(dayNum);
                tabs.forEach(function(t) {
                    var on = t.getAttribute('data-aj-nav-day') === dayNum;
                    t.classList.toggle('active', on);
                    t.classList.toggle('is-active', on);
                    t.setAttribute('aria-selected', on ? 'true' : 'false');
                    var dot = t.querySelector('.day-plan__dot');
                    if (dot) {
                        dot.classList.toggle('bg-white', on);
                        dot.classList.toggle('bg-gray-300', !on);
                    }
                });
            }
            function scrollToDay(dayNum) {
                setActive(dayNum);
                var el = document.getElementById('aj-day-panel-' + dayNum);
                if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            tabs.forEach(function(t) {
                t.addEventListener('click', function(e) {
                    e.preventDefault();
                    var day = t.getAttribute('data-aj-nav-day');
                    if (day) scrollToDay(day);
                });
            });
        })();
    </script>
</section>
@endif
