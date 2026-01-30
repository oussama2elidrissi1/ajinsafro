@props([
    'title' => 'Destination',
    'image' => 'destinations/placeholder.jpg',
    'slug' => null,
])

@php
    $url = $slug ? route('front.search', ['location' => $title]) : '#';
    $imgSrc = str_starts_with($image, 'http') ? $image : asset('front/images/' . $image);
@endphp

<a href="{{ $url }}" class="group block rounded-xl overflow-hidden shadow-md hover:shadow-xl transition-shadow bg-gray-100">
    <div class="aspect-[4/3] relative overflow-hidden">
        <img src="{{ $imgSrc }}" alt="{{ $title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" onerror="this.onerror=null; this.style.background='linear-gradient(135deg,#667eea 0%,#764ba2 100%)'; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22400%22 height=%22300%22%3E%3Crect fill=%22%23667eea%22 width=%22400%22 height=%22300%22/%3E%3C/svg%3E';">
    </div>
    <div class="p-4">
        <h3 class="font-semibold text-gray-900 group-hover:text-brand transition">{{ $title }}</h3>
    </div>
</a>
