# Unification Lieux de départ + Vols — Résumé et plan

## 1. Aujourd’hui : où c’est stocké et comment

### Lieux de départ (tab « Lieux de départ »)
- **Tables**  
  - `aj_travel_departure_places` (connexion `wp`) : `id`, `travel_id`, `name`, `code`, `is_active`, `sort_order`, `timestamps`.  
  - `aj_travel_departure_flights` (connexion `wp`) : `id`, `departure_place_id`, `airline`, `flight_number`, `from_airport`, `to_airport`, `depart_time`, `arrive_time`, `notes`, `sort_order`, `timestamps`.
- **Modèles** : `TravelDeparturePlace`, `TravelDepartureFlight`.
- **Controller** : `VoyageController::syncDeparturePlaces($tourId, $request)` sur update.  
  Payload : `departure_places[$i][name]`, `[code]`, `[is_active]`, `[flights][$j][airline|flight_number|from_airport|to_airport|depart_time|arrive_time|notes]`.  
  Comportement : supprime tous les lieux + vols du tour, recrée lieux et vols à partir du formulaire.
- **Utilisation** :  
  - Formulaire « Lieux de départ » (édition).  
  - Onglet « Départ & Vol » : select « Lieu de départ » et « Vol » (liste des vols des lieux).  
  - Plugin WP (searchbar) : liste « Starting from » = lieux ayant au moins un vol dans `aj_travel_departure_flights`.

### Vols (tab « Départ & Vol »)
- **Tables**  
  - Laravel : `voyage_flight_options` (connexion défaut) : `id`, `voyage_id`, `type` (outbound|return|segment), `day_number`, `from_city`, `to_city`, `depart_at`, `arrive_at`, `airline_id`, `flight_number`, `cabin`, `baggage_cabin_kg`, `baggage_checkin_kg`, etc.  
  - WP (sync) : `aj_tour_flights` (connexion `wp`) : `tour_id`, `flight_type` (outbound|inbound|segment), `day_number`, `sort_order`, `laravel_option_id`, `from_city`, `to_city`, `depart_date`, `depart_time`, `arrive_date`, `arrive_time`, `baggage_cabin_kg`, `baggage_checkin_kg`, `notes`, etc.
- **Modèles** : `VoyageFlightOption` (Laravel), `TourFlight` (WP, lecture).
- **Controller** : sur update, `voyageFlightOptionService->syncOptions($voyageId, $request->input('flight_options'), $lastDayNumber)` puis `syncOptionsToWp()`.  
  Payload : `flight_options[$i][id|type|day_number|airline_id|from_city|to_city|departure_date|flight_number|baggage_*|is_tentative|…]`.
- **Utilisation** :  
  - Programme (get_days), cartes vol front, plugin WP lit `aj_tour_flights` pour le programme.

### Problème
- Deux stockages de « vols » :  
  - `aj_travel_departure_flights` (par lieu, concept « vols aller depuis ce lieu »).  
  - `voyage_flight_options` → `aj_tour_flights` (vols du programme).  
- Données dupliquées et risque d’incohérence.

---

## 2. Source de vérité unique (cible)

- **Lieux** : `aj_travel_departure_places` uniquement (name, code, is_active, sort_order). Plus de table « vols par lieu » dédiée.
- **Vols** : une seule source = `voyage_flight_options` (Laravel) + sync vers `aj_tour_flights` (WP).  
  - Lien au lieu : `departure_place_id` (nullable) sur `voyage_flight_options` et `aj_tour_flights`.
- Les vols Aller/Retour (et optionnellement segments) sont liés à un lieu via `departure_place_id`.  
  La searchbar WP « Starting from » dérive les vols par lieu depuis `aj_tour_flights` (`departure_place_id`).

---

## 3. Plan technique (minimal, non cassant)

1. **Migrations**  
   - Ajouter `departure_place_id` (nullable, unsignedBigInteger) à `voyage_flight_options`.  
   - Ajouter `departure_place_id` (nullable) à `aj_tour_flights` (connexion wp).

2. **Modèle + service**  
   - `VoyageFlightOption` : `departure_place_id` dans `fillable`, pas de contrainte FK (table lieu en wp).  
   - `VoyageFlightOptionService::syncOptions()` : lire et enregistrer `departure_place_id`.  
   - `VoyageFlightOptionService::syncOptionsToWp()` : écrire `departure_place_id` dans `aj_tour_flights` si la colonne existe.

3. **Onglet Vols**  
   - En haut : bloc « Lieux de départ » (liste + ajouter/éditer/supprimer, name, code, is_active uniquement).  
   - Dans chaque carte vol Aller/Retour : select « Lieu de départ » (options = lieux du tour).  
   - Même formulaire envoie `departure_places` (lieux seuls) + `flight_options` (avec `departure_place_id`).

4. **Sauvegarde**  
   - `syncDeparturePlaces()` : ne synchronise que les **lieux** (create/update/delete dans `aj_travel_departure_places`). Ne plus créer ni supprimer de lignes dans `aj_travel_departure_flights`.  
   - Les vols sont uniquement gérés via `flight_options` → `voyage_flight_options` → `aj_tour_flights`.

5. **Onglet « Lieux de départ »**  
   - Affichage **lecture seule** : liste des lieux + pour chaque lieu les « Vols associés » lus depuis `aj_tour_flights` (ou via Laravel) où `departure_place_id = place.id`.  
   - Lien ou bouton « Gérer dans l’onglet Vols » qui change d’onglet vers « Départ & Vol ».

6. **Plugin WP (searchbar)**  
   - Pour chaque lieu actif, récupérer les vols depuis `aj_tour_flights` (`tour_id = post_id`, `departure_place_id = place.id`) au lieu de `aj_travel_departure_flights`.  
   - Conserver le même format d’affichage « Starting from » (lieux ayant au moins un vol = au moins une ligne dans `aj_tour_flights` pour ce lieu).

7. **Compatibilité**  
   - Pas de migration de données depuis `aj_travel_departure_flights` vers `aj_tour_flights` dans cette étape (optionnel plus tard).  
   - Les anciennes lignes dans `aj_travel_departure_flights` restent en base mais ne sont plus écrites ni utilisées pour l’affichage une fois le plugin adapté.

---

## 4. Fichiers impactés (résumé)

- Migrations : nouvelle migration `voyage_flight_options` + `aj_tour_flights` (departure_place_id).  
- `App\Models\VoyageFlightOption` : fillable + éventuel accesseur.  
- `App\Services\VoyageFlightOptionService` : syncOptions, syncOptionsToWp.  
- `App\Http\Controllers\Admin\VoyageController` : syncDeparturePlaces (places only).  
- Vues : `edit.blade.php`, `_flight_manager.blade.php`, `_flight_option_card.blade.php`, partial « Lieux de départ » (lecture seule).  
- Plugin WP : `templates/tour/partials/searchbar.php` (lecture vols depuis `aj_tour_flights` par `departure_place_id`).
