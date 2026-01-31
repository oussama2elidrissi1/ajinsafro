@extends('layouts.front')

@section('title', 'Booking start – AjiNsafro')

@section('content')
    <x-front.navbar />

    <main class="min-h-screen bg-gray-50">
        <div class="container mx-auto px-4 py-10 max-w-2xl">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6">Booking start</h1>

            <div class="bg-white rounded-lg shadow border border-gray-200 p-6 space-y-4">
                @if($item)
                    <div>
                        <span class="text-sm text-gray-500">Voyage</span>
                        <p class="font-medium text-gray-900">{{ $item->name }}</p>
                    </div>
                @else
                    <div>
                        <span class="text-sm text-gray-500">Type</span>
                        <p class="font-medium text-gray-900">{{ $type ?: '—' }}</p>
                        @if($slug)
                            <p class="text-sm text-gray-500 mt-1">Slug : {{ e($slug) }}</p>
                        @endif
                    </div>
                @endif

                <div>
                    <span class="text-sm text-gray-500">Date</span>
                    <p class="font-medium text-gray-900">{{ $date ?? '—' }}</p>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">Adultes</span>
                        <p class="font-medium text-gray-900">{{ $adults }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Enfants</span>
                        <p class="font-medium text-gray-900">{{ $children }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">Bébés</span>
                        <p class="font-medium text-gray-900">{{ $infant }}</p>
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-200">
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-brand text-white font-medium rounded-lg hover:bg-brand-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand">
                        Continue
                    </button>
                    <span class="ml-2 text-sm text-gray-500">(paiement à venir)</span>
                </div>
            </div>
        </div>
    </main>
@endsection
