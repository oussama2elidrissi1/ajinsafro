@props([
    'voyage',
    'nextDeparture' => null,
])

<div class="sticky top-[140px] rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100 bg-gray-50/50">
        @if($voyage->price_from !== null)
            <p class="text-sm text-gray-500">À partir de</p>
            <p class="text-2xl font-bold text-gray-900">
                {{ number_format($voyage->price_from, 0, ',', ' ') }} <span class="text-lg font-normal">{{ $voyage->currency_symbol }}</span>
            </p>
            @if($voyage->old_price && $voyage->old_price > $voyage->price_from)
                <p class="mt-1 text-sm text-gray-500">
                    <span class="line-through">{{ number_format($voyage->old_price, 0, ',', ' ') }} {{ $voyage->currency_symbol }}</span>
                    <span class="text-green-600 font-medium">-{{ $voyage->discount_percent }}%</span>
                </p>
            @endif
        @else
            <p class="text-lg font-semibold text-gray-900">Sur demande</p>
        @endif
    </div>

    <div class="p-5 space-y-4">
        @if($nextDeparture)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Prochain départ</label>
                <p class="text-gray-900">{{ $nextDeparture->start_date->format('d/m/Y') }}</p>
            </div>
        @endif

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Adultes</label>
                <select class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand focus:border-brand" aria-label="Nombre d'adultes">
                    @foreach(range(1, 8) as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Enfants</label>
                <select class="w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 focus:ring-2 focus:ring-brand focus:border-brand" aria-label="Nombre d'enfants">
                    @foreach(range(0, 6) as $n)
                        <option value="{{ $n }}">{{ $n }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <a
            href="{{ route('front.voyages.index') }}#contact"
            class="block w-full text-center py-3 px-4 rounded-lg bg-brand text-white font-semibold hover:bg-brand-dark transition"
        >
            Demander un devis
        </a>
    </div>
</div>
