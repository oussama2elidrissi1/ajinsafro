<nav class="bg-white shadow-sm w-full sticky top-0 z-40 border-b border-gray-100">
    <div class="max-w-[1400px] mx-auto px-4 h-20 flex items-center justify-between">
        <div class="flex items-center">
            <a href="{{ route('partner.dashboard') }}" class="block hover:opacity-90 transition-opacity">
                <img
                    src="{{ URL::asset('build/images/logo-dark.png') }}"
                    alt="Ajinsafro"
                    class="h-8 md:h-10 w-auto object-contain"
                    onerror="this.onerror=null; this.src='https://placehold.co/250x60/0e3a5a/FFF?text=Ajinsafro';"
                >
            </a>
        </div>
        <div class="flex items-center space-x-2 sm:space-x-4">
            <button id="mobile-menu-btn" class="lg:hidden p-2 text-gray-600 hover:text-[#0083c4] hover:bg-gray-100 rounded-lg transition-colors" type="button">
                <span class="sr-only">Ouvrir le menu</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
        </div>
    </div>
</nav>

<div id="mobile-menu" class="fixed inset-0 bg-black/60 z-[100] opacity-0 pointer-events-none transition-opacity duration-300">
    <div id="mobile-menu-drawer" class="fixed top-0 right-0 h-full w-[280px] bg-white shadow-2xl transition-transform duration-300 translate-x-full overflow-y-auto p-6">
        <div class="flex justify-between items-center mb-8">
            <img src="{{ URL::asset('build/images/logo-dark.png') }}" alt="Ajinsafro" class="h-8 w-auto object-contain">
            <button id="close-menu-btn" class="text-2xl text-gray-500 hover:text-[#0083c4]" type="button" aria-label="Fermer">
                ✕
            </button>
        </div>
        <div class="flex flex-col space-y-2 text-sm font-semibold text-gray-700">
            <a href="{{ route('partner.dashboard') }}" class="hover:text-[#0083c4] py-2 border-b border-gray-50">Tableau de bord</a>
            <a href="{{ route('partner.reservations.index') }}" class="hover:text-[#0083c4] py-2 border-b border-gray-50">Réservations</a>
            <a href="{{ route('partner.clients.index') }}" class="hover:text-[#0083c4] py-2 border-b border-gray-50">Clients</a>
            <a href="{{ route('partner.catalogue.index') }}" class="hover:text-[#0083c4] py-2 border-b border-gray-50">Catalogue voyages</a>
            <a href="{{ route('partner.messages.index') }}" class="hover:text-[#0083c4] py-2 border-b border-gray-50">Messagerie interne</a>
            <a href="{{ route('partner.invoices.index') }}" class="hover:text-[#0083c4] py-2 border-b border-gray-50">Factures & Devis</a>
            <a href="{{ route('partner.profile.show') }}" class="hover:text-[#0083c4] py-2 border-b border-gray-50">Profil</a>
        </div>
    </div>
</div>

