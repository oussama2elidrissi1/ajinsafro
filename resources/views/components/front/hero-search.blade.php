@props([
    'activeTab' => 'Hotel',
    'location' => '',
    'checkIn' => null,
    'checkOut' => null,
    'guests' => '1 guest, 1 room',
])


@php
    $checkIn = $checkIn ?? now()->format('Y-m-d');
    $checkOut = $checkOut ?? now()->addDay()->format('Y-m-d');
    $tabs = ['Hotel', 'Tours', 'Activity', 'Rental', 'Cars Rental', 'Car Transfer'];
    $heroType = \App\Models\Setting::getValue('hero_type', 'image');
    $heroImagePath = \App\Models\Setting::getValue('hero_image');
    $heroVideoPath = \App\Models\Setting::getValue('hero_video');
    $heroImageUrl = $heroImagePath ? \App\Models\Setting::storageUrl($heroImagePath) : asset('front/images/hero.jpg');
    $heroVideoUrl = $heroVideoPath ? \App\Models\Setting::storageUrl($heroVideoPath) : null;
    $heroOverlayOpacity = (float) (\App\Models\Setting::getValue('hero_overlay_opacity', '0.45'));
    $heroOverlayOpacity = max(0.5, $heroOverlayOpacity);
    $heroTitle = \App\Models\Setting::getValue('hero_title', 'Let the journey begin');
    $heroSubtitle = \App\Models\Setting::getValue('hero_subtitle', 'Get the best prices on 2,000,000+ properties, worldwide');
@endphp

<section class="relative h-[58vh] md:h-[62vh] min-h-[420px] max-h-[680px] flex flex-col items-center justify-center bg-gray-800">
    {{-- Hero background: image OR video (cover) --}}
    @if($heroType === 'video' && $heroVideoUrl)
        @php
            $videoType = 'video/mp4';
            if ($heroVideoPath && str_ends_with(strtolower($heroVideoPath), '.webm')) {
                $videoType = 'video/webm';
            } elseif ($heroVideoPath && str_ends_with(strtolower($heroVideoPath), '.ogg')) {
                $videoType = 'video/ogg';
            }
        @endphp
        <video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline aria-hidden="true">
            <source src="{{ $heroVideoUrl }}" type="{{ $videoType }}">
        </video>
    @else
        <div class="absolute inset-0 bg-cover bg-center bg-no-repeat" style="background-image: url('{{ $heroImageUrl }}');"></div>
    @endif
    <div class="absolute inset-0" style="background: rgba(0,0,0,{{ $heroOverlayOpacity }});"></div>

    <div class="relative z-10 w-full container mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-2">{{ $heroTitle }}</h1>
        <p class="text-sm md:text-base text-white/95 mb-4 max-w-2xl mx-auto">{{ $heroSubtitle }}</p>

        {{-- Search tabs (compact) --}}
        <div class="flex flex-wrap justify-center gap-1.5 mb-4">
            @foreach($tabs as $tab)
                <button type="button" data-tab="{{ $tab }}" class="tab-btn px-2.5 py-1.5 rounded-lg text-xs md:text-sm font-medium transition {{ $activeTab === $tab ? 'bg-white text-gray-900 shadow' : 'bg-black/30 text-white hover:bg-black/40' }}">
                    {{ $tab }}
                </button>
            @endforeach
        </div>

        {{-- Search form: GET to /search (max ~1040px, normalized height) --}}
        <form action="{{ route('front.search') }}" method="get" class="max-w-[1040px] mx-auto">
            <input type="hidden" name="type" value="{{ $activeTab }}">
            <div class="bg-white rounded-xl shadow-xl overflow-hidden flex flex-col md:flex-row">
                <div class="flex-1 flex flex-col sm:flex-row divide-y sm:divide-y-0 sm:divide-x divide-gray-200">
                    <label class="flex-1 flex items-center gap-2 px-3 py-2.5 min-w-0">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <input type="text" name="location" value="{{ old('location', $location) }}" placeholder="Where are you going?" class="flex-1 min-w-0 border-0 p-0 text-gray-900 text-sm placeholder-gray-500 focus:ring-0">
                    </label>
                    <label class="flex-1 flex items-center gap-2 px-3 py-2.5 min-w-0">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <input type="date" name="check_in" value="{{ old('check_in', $checkIn) }}" class="flex-1 min-w-0 border-0 p-0 text-gray-900 text-sm focus:ring-0">
                    </label>
                    <label class="flex-1 flex items-center gap-2 px-3 py-2.5 min-w-0">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <input type="date" name="check_out" value="{{ old('check_out', $checkOut) }}" class="flex-1 min-w-0 border-0 p-0 text-gray-900 text-sm focus:ring-0">
                    </label>
                    <label class="flex-1 flex items-center gap-2 px-3 py-2.5 min-w-0">
                        <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <input type="text" name="guests" value="{{ old('guests', $guests) }}" placeholder="1 guest, 1 room" class="flex-1 min-w-0 border-0 p-0 text-gray-900 text-sm placeholder-gray-500 focus:ring-0">
                    </label>
                </div>
                <div class="shrink-0">
                    <button type="submit" class="w-full md:w-[150px] h-[42px] md:h-auto py-2.5 md:py-3 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-b-xl md:rounded-b-none md:rounded-r-xl flex items-center justify-center gap-1.5 transition">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Search
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

@push('scripts')
<script>
(function() {
    var form = document.querySelector('form[action="{{ route('front.search') }}"]');
    if (!form) return;
    var typeInput = form.querySelector('input[name="type"]');
    var tabs = document.querySelectorAll('.tab-btn');
    tabs.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tab = this.getAttribute('data-tab');
            tabs.forEach(function(b) { b.classList.remove('bg-white', 'text-gray-900', 'shadow'); b.classList.add('bg-black/30', 'text-white'); });
            this.classList.remove('bg-black/30', 'text-white'); this.classList.add('bg-white', 'text-gray-900', 'shadow');
            if (typeInput) typeInput.value = tab;
        });
    });
})();
</script>
@endpush
