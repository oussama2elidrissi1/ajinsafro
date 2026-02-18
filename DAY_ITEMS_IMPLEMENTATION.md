# Implémentation: Gestion unifiée Vols/Hôtels/Transferts par Jour

## Vue d'ensemble

Lors du clic sur **"Ajouter un élément"** dans l'onglet Programme (bouton jaune pour un jour X), le drawer "Jour X — Ajouter" affiche **4 onglets**:
- **Activités** : 0..n activités par jour
- **Hôtels** : 0..1 hôtel par jour (select simple)
- **Transferts** : 0..n transferts par jour (multi-checkboxes)
- **Vols** : 0..n vols par jour (multi-checkboxes, depuis le même catalogue)

## Architecture

### Frontend (État unifié)

L'objet **`window.dayItemsManager`** (ajouté à edit.blade.php) centralise l'état:

```javascript
window.dayItemsManager = {
  state: {
    "0": { flights: [], hotel_id: null, transfer_ids: [] },
    "1": { flights: [1, 3], hotel_id: 5, transfer_ids: [2, 4] },
    ...
  },
  getDay(dayIndex),
  setFlights(dayIndex, flightIds[]),
  getFlights(dayIndex),
  setHotel(dayIndex, hotelId),
  getHotel(dayIndex),
  setTransfers(dayIndex, transferIds[]),
  getTransfers(dayIndex),
  syncToForm(dayIndex),    // Écrit dans les inputs hidden
  loadFromForm(dayIndex)    // Lit depuis les inputs hidden
}
```

### Formulaire HTML

Pour chaque jour (dans `.programme-day-card`), trois inputs hidden:

```html
<input type="hidden" name="programme_days[0][flights]" value="1,3">
<input type="hidden" name="programme_days[0][hotel_id]" value="5">
<input type="hidden" name="programme_days[0][transfer_ids]" value="2,4">
```

**Format**:
- `flights`: chaîne CSV "1,3" (ou vide)
- `hotel_id`: nombre unique ou vide
- `transfer_ids`: chaîne CSV "2,4" (ou vide)

### Drawer (Composants)

1. **HotelsManager.blade.php** → `<select id="hotels-manager-select">`
   - Charge les hôtels depuis `window.tourHotelsData`
   - Écoute `day-builder:context-changed` → pré-remplissage
   - Listener sur `change` → met à jour `window.dayItemsManager.setHotel(dayIndex, hotelId)`

2. **TransfersManager.blade.php** → `.form-check` pour chaque transfert
   - Charge depuis `window.tourTransfersData` (arrival + departure)
   - Écoute `day-builder:context-changed` → pré-remplissage
   - Listener sur `change` (checkboxes) → appelle `window.dayItemsManager.setTransfers(dayIndex, ids[])`

3. **FlightsManager.blade.php** → wrapper autour du flight-manager
   - Inclut `_flight_manager.blade.php` (mode='drawer')
   - Écoute `day-builder:context-changed` + `change` → synchronise avec `dayItemsManager.setFlights()`

### Événements

L'événement **`day-builder:context-changed`** est déclenché quand le drawer s'ouvre:

```javascript
document.dispatchEvent(new CustomEvent('day-builder:context-changed', {
  detail: {
    dayIndex: "0",      // Index de position (0-based)
    dayId: "42",        // ID DB (TravelProgramDay.id)
    dayNumber: 1        // Numéro affiché (1-based)
  }
}));
```

## Flux de sauvegarde

1. **Au clic "Enregistrer toutes les modifications"**: formulaire POST
2. **Données envoyées au backend**:
   ```
   programme_days[0][flights] = "1,3"
   programme_days[0][hotel_id] = "5"
   programme_days[0][transfer_ids] = "2,4"
   ```
3. **Backend reçoit** dans `$request->input('programme_days')`:
   ```php
   [
     0 => [
       'flights' => "1,3",        // CSV string
       'hotel_id' => "5",         // string ou null
       'transfer_ids' => "2,4"    // CSV string ou null
     ]
   ]
   ```

## Backend (Attentes)

Le controller (`VoyageController@update`) doit:

1. **Parser les données**:
   ```php
   foreach ($programmeDays as $dayIndex => $dayData) {
       $flightIds = array_filter(array_map('intval', explode(',', $dayData['flights'] ?? '')));
       $hotelId = intval($dayData['hotel_id'] ?? 0) ?: null;
       $transferIds = array_filter(array_map('intval', explode(',', $dayData['transfer_ids'] ?? '')));
       
       $day = $voyage->programDays()->nth($dayIndex);
       $day->update(['hotel_id' => $hotelId]);
       $day->flights()->sync($flightIds);      // Pivot (si existera)
       $day->transfers()->sync($transferIds);  // Pivot (si existera)
   }
   ```

2. **Relations Eloquent attendues**:
   - `TravelProgramDay->flights()` : belongsToMany
   - `TravelProgramDay->transfers()` : belongsToMany
   - `TravelProgramDay->hotel()` : belongsTo (colonne `hotel_id`)

3. **Migrations attendues**:
   - Table `travel_program_day_flights` (pivot)
   - Table `travel_program_day_transfers` (pivot)
   - Colonne `travel_program_days.hotel_id`

## Pas de breaking changes

- Les onglets globaux **Vols / Hôtels / Transferts** (en haut) restent inchangés
- Les données globalus persistent dans `tour_flights`, `tour_hotels`, `tour_transfer_*`
- Le Programme "par jour" est **isolé** dans le formulaire `programme_days[X][...]`
- Les voyages existants sans liaison "par jour" → état vide (graceful degradation)

## Helpers / Fonctions utiles

### Synchroniser manuellement l'état interne vers le formulaire

```javascript
window.dayItemsManager.syncToForm(dayIndex);
```

### Charger depuis le formulaire vers l'état interne

```javascript
window.dayItemsManager.loadFromForm(dayIndex);
```

### Obtenir le compte total des items (activités + vols + hôtel + transferts)

```javascript
const total = window.dayItemsManager.countItems(dayIndex);
```

### Réinitialiser un jour

```javascript
const day = window.dayItemsManager.getDay(dayIndex);
day.flights = [];
day.hotel_id = null;
day.transfer_ids = [];
window.dayItemsManager.syncToForm(dayIndex);
```

## Test / Vérification

Depuis le console du navigateur:

```javascript
// Vérifier l'état global
console.log(window.dayItemsManager.state);

// Obtenir les vols du jour 0
console.log(window.dayItemsManager.getFlights('0'));

// Définir un hôtel au jour 1
window.dayItemsManager.setHotel('1', 5);

// Vérifier le formulaire
document.querySelector('input[name="programme_days[1][hotel_id]"]').value;
// → "5"
```

## Notes d'implémentation

- Les inputs hidden sont présents dans chaque `.programme-day-card` et sont mis à jour automatiquement
- Le gestionnaire d'état survive au basculement entre jours (état persiste en mémoire)
- La pré-remplissage depuis `window.programDayHotelsTransfers` est supportée pour la rétro-compatibilité
- Les managers sont indépendants et peuvent être ouverts/fermés sans perdre l'état

## Limitations & Fallbacks

1. **Si `dayItemsManager` n'existe pas**: les inputs hidden reçoivent les valeurs des managers directement
2. **Si un manager est absent**: le jour concerné n'aura pas d'interface UI pour cette liaison
3. **Si le backend ne supporte pas**: les données seront ignorées (pas d'erreur)

