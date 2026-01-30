@props([
    'day',
    'isFirst' => false,
])

@php
    $open = $isFirst;
@endphp
<details class="group border border-gray-200 rounded-lg overflow-hidden" {{ $open ? 'open' : '' }}>
    <summary class="flex items-center justify-between gap-4 px-4 py-4 cursor-pointer list-none bg-white hover:bg-gray-50 transition-colors [&::-webkit-details-marker]:hidden">
        <span class="font-semibold text-gray-900">
            Jour {{ $day->day_number }} – {{ e($day->title) }}
        </span>
        <span class="flex items-center gap-2 shrink-0">
            @if($day->city)
                <span class="text-sm text-gray-500">{{ e($day->city) }}</span>
            @endif
            @if($day->day_label_badge)
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                    {{ e($day->day_label_badge) }}
                </span>
            @endif
            <svg class="w-5 h-5 text-gray-500 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </span>
    </summary>
    <div class="border-t border-gray-200">
        <div class="px-4 py-4 bg-gray-50/50 prose prose-sm max-w-none text-gray-700">
            @if($day->content_html)
                {!! $day->content_html !!}
            @elseif($day->description)
                {!! nl2br(e($day->description)) !!}
            @endif
            @if($day->hasMealBreakfast() || $day->hasMealLunch() || $day->hasMealDinner())
                <p class="mt-3 text-sm text-gray-600">
                    Repas :
                    @if($day->hasMealBreakfast()) <span class="inline-block mr-2">Petit-déjeuner</span> @endif
                    @if($day->hasMealLunch()) <span class="inline-block mr-2">Déjeuner</span> @endif
                    @if($day->hasMealDinner()) <span class="inline-block">Dîner</span> @endif
                </p>
            @endif
        </div>
    </div>
</details>
