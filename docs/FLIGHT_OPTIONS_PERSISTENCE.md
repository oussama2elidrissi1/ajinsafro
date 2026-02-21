# Persistance Lieu de départ et Date de départ (Vols)

## Problème

Après sauvegarde du voyage, les champs **Lieu de départ** et **Date de départ** dans l’onglet Vols (cartes Vol Aller / Vol Retour) ne restaient pas enregistrés au rechargement de la page.

## Chaîne analysée

| Étape | Élément | Statut |
|--------|--------|--------|
| 1. Formulaire | `name="flight_options[{{ $index }}][departure_place_id]"` et `flight_options[{{ $index }}][departure_date]"` | OK (noms corrects) |
| 2. Soumission | Bouton « Enregistrer » de la carte soumet bien le formulaire principal `#edit-voyage-form` | OK (corrigé précédemment) |
| 3. Request | Controller utilise `$request->input('flight_options')` (pas `validated()`) | OK |
| 4. Validation | `UpdateWpTourRequest` : règles `flight_options.*.departure_place_id` et `flight_options.*.departure_date` | OK |
| 5. Service | `VoyageFlightOptionService::syncOptions()` mappe vers `departure_place_id` et `depart_at` | OK |
| 6. Modèle | `VoyageFlightOption` : `departure_place_id` et `depart_at` dans `$fillable`, cast `depart_at` => datetime | OK |
| 7. DB | Table `voyage_flight_options` : colonnes `departure_place_id`, `depart_at` (migration appliquée) | À vérifier en env |

## Cause racine identifiée

1. **Condition `$filled` trop stricte**  
   Une ligne était considérée « à enregistrer » seulement si elle avait au moins un parmi : `airline_id`, `from_city`, `to_city`, `departure_date`, `departure_datetime`, `flight_number`.  
   **Conséquence** : si l’utilisateur ne remplissait que le **Lieu de départ** (sans date ni ville), la ligne était ignorée et jamais sauvegardée. De plus, une valeur `departure_place_id = 0` (« Aucun ») n’était pas convertie en `null` en base.

2. **Valeur 0 pour « Aucun »**  
   Le select envoie `value=""` pour « — Aucun — ». En PHP `(int)""` donne `0`. Stocker `0` en base pour « aucun lieu » au lieu de `null` peut poser problème (FK, affichage).

## Corrections appliquées

### 1. `app/Services/VoyageFlightOptionService.php`

- **`$filled`** : prise en compte de `departure_place_id` (valeur numérique > 0) pour qu’une ligne avec uniquement un lieu de départ soit bien sauvegardée.
- **`departure_place_id`** : si la valeur reçue est `0` ou vide, on enregistre `null` en base.

### 2. `app/Http/Controllers/Admin/VoyageController.php`

- **Log debug** : au début du traitement des `flight_options`, log du payload (nombre d’entrées + première entrée) pour vérifier en cas de doute que le backend reçoit bien `departure_place_id` et `departure_date`.  
  Fichier de log : `storage/logs/laravel.log` (niveau `debug`).

## Vérifications côté vous

1. **Migrations**  
   Exécuter les migrations qui ajoutent `departure_place_id` et éventuellement les colonnes de date/heure sur `voyage_flight_options` et `aj_tour_flights` si ce n’est pas déjà fait.

2. **Sauvegarde**  
   - Remplir **Lieu de départ** et **Date de départ** sur une carte Vol Aller.  
   - Cliquer sur **Enregistrer** (carte) ou **Enregistrer toutes les modifications** (bas de page).  
   - Recharger la page : le lieu et la date doivent rester renseignés.

3. **Logs**  
   Si le problème persiste, activer le niveau `debug` dans `config/logging.php` (channel utilisé par défaut) et consulter `storage/logs/laravel.log` après une sauvegarde. La ligne `VoyageController@update flight_options payload` doit montrer `first` avec `departure_place_id` et `departure_date` présents si le formulaire envoie bien les champs.

## Fichiers modifiés

- `app/Services/VoyageFlightOptionService.php` : condition `$filled` + normalisation `departure_place_id` (0 → null).
- `app/Http/Controllers/Admin/VoyageController.php` : log debug du payload `flight_options`.
