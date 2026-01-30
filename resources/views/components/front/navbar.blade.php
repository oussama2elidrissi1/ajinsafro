@php
    $topbarPhone = \App\Models\Setting::getValue('topbar_phone');
    $topbarEmail = \App\Models\Setting::getValue('topbar_email');
    $socialFacebook = \App\Models\Setting::getValue('social_facebook');
    $socialTwitter = \App\Models\Setting::getValue('social_twitter');
    $socialInstagram = \App\Models\Setting::getValue('social_instagram');
    $socialYoutube = \App\Models\Setting::getValue('social_youtube');
    $brandName = \App\Models\Setting::getValue('brand_name');
    $brandLogo = \App\Models\Setting::getValue('brand_logo');
    $brandLogoUrl = $brandLogo ? \App\Models\Setting::storageUrl($brandLogo) : null;
@endphp
{{-- Dark topbar + semi-transparent header (TravelerWP-like) --}}
<div class="fixed top-0 left-0 right-0 z-50">
    {{-- Dark topbar (only if at least one contact or social) --}}
    @if($topbarPhone || $topbarEmail || $socialFacebook || $socialTwitter || $socialInstagram || $socialYoutube)
    <div class="bg-topbar text-white py-2">
        <div class="container mx-auto px-4 flex flex-wrap items-center justify-between gap-2 text-sm">
            <div class="flex flex-wrap items-center gap-4 md:gap-6">
                @if($topbarPhone)
                <a href="tel:{{ preg_replace('/\s+/', '', $topbarPhone) }}" class="flex items-center gap-1.5 hover:text-gray-200">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                    <span>{{ $topbarPhone }}</span>
                </a>
                @endif
                @if($topbarEmail)
                <a href="mailto:{{ $topbarEmail }}" class="flex items-center gap-1.5 hover:text-gray-200">
                    <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>
                    <span>{{ $topbarEmail }}</span>
                </a>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @if($socialFacebook)<a href="{{ $socialFacebook }}" target="_blank" rel="noopener noreferrer" class="hover:text-gray-200" aria-label="Facebook"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>@endif
                @if($socialTwitter)<a href="{{ $socialTwitter }}" target="_blank" rel="noopener noreferrer" class="hover:text-gray-200" aria-label="Twitter"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg></a>@endif
                @if($socialInstagram)<a href="{{ $socialInstagram }}" target="_blank" rel="noopener noreferrer" class="hover:text-gray-200" aria-label="Instagram"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></a>@endif
                @if($socialYoutube)<a href="{{ $socialYoutube }}" target="_blank" rel="noopener noreferrer" class="hover:text-gray-200" aria-label="YouTube"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>@endif
            </div>
        </div>
    </div>
    @endif

    {{-- Semi-transparent header overlaying hero --}}
    <header class="bg-white/90 backdrop-blur-sm border-b border-gray-200/50">
        <div class="container mx-auto px-4 py-3 flex flex-wrap items-center justify-between gap-4">
            {{-- Logo left (from settings) – responsive height, max width aligned with navbar --}}
            <a href="{{ route('front.home') }}" class="flex items-center gap-2 shrink-0 min-h-[2.5rem] md:min-h-[2.75rem]">
                @if($brandLogoUrl)
                    <img src="{{ $brandLogoUrl }}" alt="{{ $brandName }}" class="h-8 w-auto max-w-[100px] sm:h-9 sm:max-w-[120px] md:h-10 md:max-w-[160px] object-contain object-left">
                @else
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-sky-100 text-brand shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                @endif
            </a>

            {{-- Mobile menu button --}}
            <button type="button" class="lg:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100" aria-label="Open menu" onclick="document.getElementById('front-nav').classList.toggle('hidden')">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            {{-- Nav center (mobile: toggled; desktop: always visible) --}}
            <nav id="front-nav" class="hidden lg:flex flex-col lg:flex-row items-center gap-1 w-full lg:w-auto order-last lg:order-none py-4 lg:py-0 border-t lg:border-t-0 border-gray-200" aria-label="Main">
                <a href="{{ route('front.home') }}" class="px-3 py-2 rounded-md text-gray-700 font-medium hover:bg-gray-100">Home</a>
                <a href="#" class="px-3 py-2 rounded-md text-gray-700 font-medium hover:bg-gray-100 flex items-center gap-0.5">Hotel <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></a>
                <a href="#" class="px-3 py-2 rounded-md text-gray-700 font-medium hover:bg-gray-100 flex items-center gap-0.5">Tour <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></a>
                <a href="#" class="px-3 py-2 rounded-md text-gray-700 font-medium hover:bg-gray-100 flex items-center gap-0.5">Activity <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></a>
                <a href="#" class="px-3 py-2 rounded-md text-gray-700 font-medium hover:bg-gray-100 flex items-center gap-0.5">Rental <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></a>
                <a href="#" class="px-3 py-2 rounded-md text-gray-700 font-medium hover:bg-gray-100 flex items-center gap-0.5">Car <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></a>
                <a href="#" class="px-3 py-2 rounded-md text-gray-700 font-medium hover:bg-gray-100 flex items-center gap-0.5">Pages <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg></a>
            </nav>

            {{-- Actions right --}}
            <div class="flex items-center gap-2 md:gap-4">
                <div class="relative hidden sm:block">
                    <button type="button" class="flex items-center gap-1 px-3 py-2 rounded-md text-gray-700 hover:bg-gray-100" aria-expanded="false" aria-haspopup="true">
                        <span>EUR</span>
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                    </button>
                </div>
                <a href="#" class="p-2 rounded-md text-gray-600 hover:bg-gray-100" aria-label="Cart">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </a>
                <a href="#" class="p-2 rounded-md text-gray-600 hover:bg-gray-100" aria-label="Account">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </a>
                <a href="#" class="inline-flex items-center px-4 py-2 rounded-lg bg-brand text-white font-medium hover:bg-brand-dark transition">Become a host</a>
            </div>
        </div>
    </header>
</div>

{{-- Spacer so content is not under fixed navbar --}}
<div class="h-[120px] md:h-[116px]"></div>
