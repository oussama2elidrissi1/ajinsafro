<div id="day-builder-hotels-manager">
    <p class="text-muted small mb-3">Sélectionnez l'hôtel pour ce jour (0..1 hôtel par jour)</p>
    
    <div class="mb-3">
        <label for="hotels-manager-select" class="form-label">Hôtel</label>
        <select id="hotels-manager-select" class="form-select" data-type="hotels">
            <option value="">— Aucun hôtel</option>
        </select>
    </div>

    <div id="hotels-manager-details" style="display: none;">
        <div class="card bg-light">
            <div class="card-body">
                <div class="mb-2">
                    <strong class="d-block">Nom :</strong>
                    <span id="hotels-hotel-name">—</span>
                </div>
                <div class="mb-2">
                    <strong class="d-block">Adresse :</strong>
                    <span id="hotels-hotel-address">—</span>
                </div>
                <div class="mb-2">
                    <strong class="d-block">Type chambre :</strong>
                    <span id="hotels-hotel-room-type">—</span>
                </div>
                <div class="mb-2">
                    <strong class="d-block">Plan repas :</strong>
                    <span id="hotels-hotel-meal-plan">—</span>
                </div>
            </div>
        </div>
    </div>

    <button type="button" class="btn btn-primary mt-3" id="hotels-manager-confirm-btn">
        <i class="bx bx-check"></i> Confirmer / Ajouter au jour
    </button>
    {{-- La valeur est écrite dans la carte du jour via dayItemsManager.syncToForm() au clic Confirmer --}}
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!window.tourHotelsData) {
        window.tourHotelsData = {};
    }
});

// Appelé quand le drawer s'ouvre (même event que Vols : day-builder:context-changed)
document.addEventListener('day-builder:context-changed', function(e) {
    const detail = e.detail || {};
    const dayIndex = String(detail.dayIndex || '');
    
    const hotelsSelect = document.getElementById('hotels-manager-select');
    if (!hotelsSelect) return;
    
    // Charger les hôtels depuis window.tourHotelsData (passés par la vue)
    loadHotelsForManager();
    
    // Charger l'état depuis la carte du jour (inputs hidden dans .programme-day-card)
    if (window.dayItemsManager) {
        window.dayItemsManager.loadFromForm(dayIndex);
    }
    
    // Restaurer la sélection depuis dayItemsManager (pré-rempli au chargement ou après sauvegarde)
    var hotelIdToSelect = (window.dayItemsManager && window.dayItemsManager.getHotel(dayIndex)) || '';
    
    if (hotelIdToSelect) {
        hotelsSelect.value = hotelIdToSelect;
        updateHotelsDetails(hotelIdToSelect);
    } else {
        hotelsSelect.value = '';
        document.getElementById('hotels-manager-details').style.display = 'none';
    }
});

function loadHotelsForManager() {
    const hotelsSelect = document.getElementById('hotels-manager-select');
    if (!hotelsSelect || !window.tourHotelsData) return;
    
    // Garder la première option vide
    while (hotelsSelect.options.length > 1) {
        hotelsSelect.remove(1);
    }
    
    // Ajouter les hôtels
    Object.values(window.tourHotelsData).forEach(hotel => {
        const opt = document.createElement('option');
        opt.value = hotel.id;
        opt.textContent = hotel.hotel_name || 'Hôtel (ID: ' + hotel.id + ')';
        hotelsSelect.appendChild(opt);
    });
}

function updateHotelsDetails(hotelId) {
    if (!hotelId || !window.tourHotelsData || !window.tourHotelsData[hotelId]) {
        document.getElementById('hotels-manager-details').style.display = 'none';
        return;
    }
    
    const hotel = window.tourHotelsData[hotelId];
    document.getElementById('hotels-hotel-name').textContent = hotel.hotel_name || '—';
    document.getElementById('hotels-hotel-address').textContent = hotel.address || '—';
    document.getElementById('hotels-hotel-room-type').textContent = hotel.room_type || '—';
    document.getElementById('hotels-hotel-meal-plan').textContent = hotel.meal_plan || '—';
    document.getElementById('hotels-manager-details').style.display = '';
}

// Select : mise à jour de l'aperçu uniquement (appliquer au jour au clic Confirmer)
document.addEventListener('change', function(e) {
    if (e.target.id === 'hotels-manager-select') {
        var hotelId = e.target.value ? parseInt(e.target.value, 10) : null;
        updateHotelsDetails(hotelId);
    }
});

// Confirmer : affecte l'hôtel au jour courant, sync, met à jour le résumé, ferme le drawer
document.addEventListener('click', function(e) {
    if (e.target.id !== 'hotels-manager-confirm-btn' && !e.target.closest('#hotels-manager-confirm-btn')) return;
    var drawer = document.getElementById('day-builder-drawer');
    var dayIndex = drawer ? drawer.getAttribute('data-day-index') : '';
    var hotelsSelect = document.getElementById('hotels-manager-select');
    if (!window.dayItemsManager || dayIndex === '' || !hotelsSelect) return;
    var hotelId = hotelsSelect.value ? parseInt(hotelsSelect.value, 10) : null;
    window.dayItemsManager.setHotel(dayIndex, hotelId);
    window.dayItemsManager.syncToForm(dayIndex);
    document.dispatchEvent(new CustomEvent('day-builder:item-count-changed', { detail: { dayIndex: dayIndex } }));
    if (drawer && typeof bootstrap !== 'undefined') {
        var offcanvas = bootstrap.Offcanvas.getInstance(drawer);
        if (offcanvas) offcanvas.hide();
    }
});
</script>
