@extends('layouts.front')

@section('title', 'AjiNsafro.ma – Let the journey begin')

@section('content')
    <x-front.navbar />

    <main>
        <x-front.hero-search :activeTab="'Hotel'" />

        <section class="bg-white py-12 md:py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-2xl md:text-3xl font-bold text-center text-gray-900 mb-8 md:mb-12">Top destinations</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-6xl mx-auto">
                    @foreach($destinations ?? [] as $destination)
                        <x-front.destination-card
                            :title="$destination['title']"
                            :image="$destination['image']"
                            :slug="$destination['slug'] ?? null"
                        />
                    @endforeach
                </div>
                @if(empty($destinations))
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 max-w-6xl mx-auto">
                        @foreach([['title' => 'Dubai', 'image' => 'destinations/dubai.jpg'], ['title' => 'Paris', 'image' => 'destinations/paris.jpg'], ['title' => 'Tokyo', 'image' => 'destinations/tokyo.jpg'], ['title' => 'New York', 'image' => 'destinations/newyork.jpg']] as $d)
                            <x-front.destination-card :title="$d['title']" :image="$d['image']" />
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-gray-300 py-8">
        <div class="container mx-auto px-4 text-center text-sm">
            &copy; {{ date('Y') }} AjiNsafro.ma. All rights reserved.
        </div>
    </footer>
@endsection
