<footer class="pt-20 pb-10 border-t border-gray-100 relative overflow-hidden min-h-[320px]">
    <div class="max-w-[1400px] mx-auto px-6 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-12">
            <div>
                <h4 class="text-[#0083c4] font-bold text-sm mb-6 uppercase tracking-wider">En savoir plus</h4>
                <ul class="flex flex-col gap-3 text-sm">
                    <li><a href="{{ url('/') }}" class="text-gray-600 hover:text-[#0083c4] transition-colors">Accueil</a></li>
                    <li><a href="{{ url('/devenir-partenaire') }}" class="text-gray-600 hover:text-[#0083c4] transition-colors">Devenir partenaire</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-[#0083c4] font-bold text-sm mb-6 uppercase tracking-wider">Portail partenaire</h4>
                <ul class="flex flex-col gap-3 text-sm">
                    <li><a href="{{ route('partner.dashboard') }}" class="text-gray-600 hover:text-[#0083c4] transition-colors">Dashboard</a></li>
                    <li><a href="{{ route('partner.catalogue.index') }}" class="text-gray-600 hover:text-[#0083c4] transition-colors">Catalogue voyages</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-[#0083c4] font-bold text-sm mb-6 uppercase tracking-wider">Mentions</h4>
                <div class="bg-white/40 backdrop-blur-sm p-4 rounded-xl border border-gray-200/50 text-gray-500 text-[11px] leading-relaxed">
                    <p class="font-bold text-[#0e3a5a] mb-1">AjinSafro Recreation</p>
                    <p>Portail partenaire — accès sécurisé</p>
                </div>
            </div>
            <div>
                <h4 class="text-[#0083c4] font-bold text-sm mb-6 uppercase tracking-wider">Support</h4>
                <p class="text-gray-500 text-[11px] mb-4">Besoin d’aide ? Contactez le siège via la messagerie interne.</p>
                <a href="{{ route('partner.messages.index') }}" class="inline-flex items-center justify-center bg-[#0083c4] hover:bg-[#0e3a5a] text-white text-[11px] font-bold px-6 py-3 rounded-xl transition-colors">
                    Ouvrir la messagerie
                </a>
            </div>
        </div>
        <div class="border-t border-gray-200/50 pt-8 text-center">
            <p class="text-gray-400 text-[10px]">© {{ date('Y') }} AjinSafro. Tous droits réservés.</p>
        </div>
    </div>
</footer>

