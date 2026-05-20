
<div id="ajin-modal-departure" class="ajin-cal-modal fixed inset-0 z-[200] hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
        <div class="p-5 border-b border-gray-100 flex justify-between items-start">
            <h3 class="font-bold text-[#0e3a5a] text-lg">Détail du départ</h3>
            <button type="button" data-close-cal-modal class="text-gray-400 hover:text-red-500 text-xl leading-none" aria-label="Fermer">&times;</button>
        </div>
        <div class="p-5" id="ajin-modal-departure-body"></div>
        <div class="p-5 border-t border-gray-100 hidden flex flex-wrap gap-2 justify-end" id="ajin-modal-departure-footer">
            <a href="#" id="ajin-btn-dep-consulter" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-bold text-gray-700 hover:bg-gray-50">
                <i class="fas fa-external-link-alt"></i> Fiche voyage
            </a>
            <a href="#" id="ajin-btn-dep-reserver" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#0083c4] text-white text-sm font-bold hover:opacity-95">
                <i class="fas fa-calendar-check"></i> Réserver
            </a>
        </div>
    </div>
</div>


<div id="ajin-modal-reservation" class="ajin-cal-modal fixed inset-0 z-[200] hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto relative">
        <div class="p-5 border-b border-gray-100 flex justify-between items-start">
            <h3 class="font-bold text-[#0e3a5a] text-lg">Réservation</h3>
            <button type="button" data-close-cal-modal class="text-gray-400 hover:text-red-500 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5" id="ajin-modal-reservation-body"></div>
        <div class="p-5 border-t border-gray-100 flex justify-end">
            <a href="#" id="ajin-btn-res-edit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#0083c4] text-white text-sm font-bold hover:opacity-95">
                <i class="fas fa-pen"></i> Ouvrir le dossier
            </a>
        </div>
    </div>
</div>


<div id="ajin-modal-new" class="ajin-cal-modal fixed inset-0 z-[200] hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[90vh] overflow-y-auto relative">
        <div class="p-5 border-b border-gray-100 flex justify-between items-start">
            <div>
                <h3 class="font-bold text-[#0e3a5a] text-lg">Nouvelle réservation</h3>
                <p id="ajin-modal-new-sub" class="text-[11px] text-[#f37a1f] font-bold uppercase tracking-wider mt-1"></p>
            </div>
            <button type="button" data-close-cal-modal class="text-gray-400 hover:text-red-500 text-xl leading-none">&times;</button>
        </div>
        <div class="p-5" id="ajin-modal-new-links"></div>
    </div>
</div>

<?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservations\calendrier\partials\modals.blade.php ENDPATH**/ ?>