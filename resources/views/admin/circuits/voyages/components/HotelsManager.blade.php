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

    <input type="hidden" name="programme_days[__DAY_INDEX__][hotel_id]" value="">
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!window.tourHotelsData) {
        window.tourHotelsData = {};
    }
});

// Appelé quand le drawer s'ouvre (depuis edit.blade.php)
document.addEventListener('day-builder:context-changed', function(e) {
    const detail = e.detail || {};
    const dayIndex = String(detail.dayIndex || '');
    
    const hotelsSelect = document.getElementById('hotels-manager-select');
    const hotelsInput = document.querySelector('input[name^="programme_days["][name$="[hotel_id]"]');
    
    if (!hotelsSelect || !hotelsInput) return;
    
    // Mettre à jour le name de l'input
    hotelsInput.name = 'programme_days[' + dayIndex + '][hotel_id]';
    
    // Charger les hôtels depuis window.tourHotelsData (passés par la vue)
    loadHotelsForManager();
    
    // Charger depuis le gestionnaire d'état (dayItemsManager)
    window.dayItemsManager.loadFromForm(dayIndex);
    
    // Restaurer la sélection : d'abord depuis dayItemsManager, sinon depuis input
    let hotelIdToSelect = window.dayItemsManager.getHotel(dayIndex) || '';
    
    if (hotelIdToSelect) {
        hotelsSelect.value = hotelIdToSelect;
        hotelsInput.value = hotelIdToSelect;
        updateHotelsDetails(hotelIdToSelect);
    } else {
        hotelsSelect.value = '';
        hotelsInput.value = '';
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

// Listener sur le select pour capturer le changement
document.addEventListener('change', function(e) {
    if (e.target.id === 'hotels-manager-select') {
        const hotelId = e.target.value ? parseInt(e.target.value, 10) : null;
        const hotelsInput = document.querySelector('input[name^="programme_days["][name$="[hotel_id]"]');
        const drawer = document.getElementById('day-builder-drawer');
        const dayIndex = drawer ? drawer.getAttribute('data-day-index') : '';
        
        // Mettre à jour le gestionnaire d'état
        if (window.dayItemsManager && dayIndex) {
            window.dayItemsManager.setHotel(dayIndex, hotelId);
        }
        
        updateHotelsDetails(hotelId);
    }
});
</script>
