# Nouvelle Fonctionnalité : Lieux de Départ et Dates Disponibles

## Vue d'ensemble

Cette mise à jour ajoute deux nouvelles fonctionnalités importantes au système de voyages :

1. **"Starting from" (Lieux de départ)** - Permet de configurer plusieurs lieux de départ avec leurs vols aller
2. **"Travelling on" (Dates disponibles)** - Permet de définir les dates disponibles pour chaque voyage

## Installation et Déploiement

### 1. Exécuter les migrations

Les migrations créent 3 nouvelles tables dans la connexion WordPress :
- `wp_aj_travel_departure_places` - Lieux de départ
- `wp_aj_travel_departure_flights` - Vols aller associés aux lieux
- `wp_aj_travel_dates` - Dates disponibles

```bash
# En ligne de commande
php artisan migrate

# Ou via PowerShell
php artisan migrate
```

### 2. Vérifier les migrations

Les tables suivantes doivent être créées :
```sql
SHOW TABLES LIKE 'wp_aj_travel_%';
```

Vous devriez voir :
- wp_aj_travel_departure_places
- wp_aj_travel_departure_flights  
- wp_aj_travel_dates

## Utilisation - Côté Laravel Admin

### Configuration des Lieux de Départ

1. Aller dans **Admin → Circuits → Tours**
2. Ouvrir un voyage existant ou en créer un nouveau
3. Cliquer sur l'onglet **"Lieux de départ"**
4. Cliquer sur **"Ajouter un lieu de départ"**
5. Remplir :
   - **Nom du lieu** (ex: Casablanca, Paris) - Obligatoire
   - **Code IATA** (ex: CMN, CDG) - Optionnel
   - **Actif** - Coché par défaut

6. **Ajouter au moins un vol aller** pour ce lieu :
   - Compagnie aérienne
   - Numéro de vol
   - Aéroport de départ
   - Aéroport d'arrivée
   - Heure de départ
   - Heure d'arrivée
   - Notes (optionnel)

7. Vous pouvez ajouter plusieurs vols pour un même lieu
8. **Important** : Un lieu sans vol ne sera PAS sauvegardé

### Configuration des Dates Disponibles

1. Dans le même formulaire d'édition du voyage
2. Cliquer sur l'onglet **"Dates disponibles"**
3. Cliquer sur **"Ajouter une date"**
4. Remplir :
   - **Date** (YYYY-MM-DD) - Obligatoire
   - **Places** (nombre de places disponibles) - Optionnel
   - **Prix spécifique** (surcharge/réduction pour cette date) - Optionnel
   - **Actif** - Coché par défaut

5. Ajouter autant de dates que nécessaire
6. Enregistrer le voyage

## Affichage - Côté WordPress (Front)

### Section "Starting from"

Sur la page du tour WordPress (`single-st_tours.php`), dans la barre de recherche :

- Un **select** affiche tous les lieux de départ actifs configurés
- Quand le client sélectionne un lieu, **les vols associés s'affichent automatiquement** sous la barre de recherche
- Les informations de vol incluent :
  * Compagnie aérienne
  * Numéro de vol
  * Aéroport de départ avec heure
  * Aéroport d'arrivée avec heure
  * Notes éventuelles

- Si aucun lieu n'est configuré, le select affiche "No departure places configured"

### Section "Travelling on"

- Un **input date** (calendrier) n'affiche que les dates configurées dans Laravel
- Les dates non disponibles sont désactivées
- Si aucune date n'est configurée, affiche "No dates available"
- Le calendrier peut être amélioré avec jQuery UI datepicker pour une meilleure UX

### Conservation des Choix pour le Booking

Les sélections sont automatiquement :
- Stockées dans des champs cachés du formulaire de booking
- Synchronisées entre la searchbar et le booking box
- Envoyées avec la demande de réservation

Champs envoyés :
- `departure_place_id` - ID du lieu de départ sélectionné
- `date` - Date de voyage sélectionnée (YYYY-MM-DD)
- `adults` - Nombre d'adultes
- `children` - Nombre d'enfants

## Architecture Technique

### Tables Base de Données

#### wp_aj_travel_departure_places
```sql
- id (bigint)
- travel_id (bigint) - ID du tour WP
- name (varchar) - Nom du lieu
- code (varchar) - Code IATA
- is_active (boolean)
- sort_order (int)
- created_at, updated_at
```

#### wp_aj_travel_departure_flights
```sql
- id (bigint)
- departure_place_id (bigint) - FK vers places
- airline (varchar)
- flight_number (varchar)
- from_airport (varchar)
- to_airport (varchar)
- depart_time (time)
- arrive_time (time)
- notes (text)
- sort_order (int)
- created_at, updated_at
```

#### wp_aj_travel_dates
```sql
- id (bigint)
- travel_id (bigint) - ID du tour WP
- date (date)
- is_active (boolean)
- seats (int, nullable)
- price_override (decimal, nullable)
- created_at, updated_at
- UNIQUE (travel_id, date)
```

### Modèles Laravel

- `App\Models\TravelDeparturePlace` - Gère les lieux de départ
- `App\Models\TravelDepartureFlight` - Gère les vols aller
- `App\Models\TravelDate` - Gère les dates disponibles

### Controller Laravel

#### VoyageController
Nouvelles méthodes privées :
- `syncDeparturePlaces($tourId, $request)` - Synchronise lieux et vols
- `syncTravelDates($tourId, $request)` - Synchronise les dates

### Repository WordPress

#### LaravelExtrasRepository
Nouvelles méthodes publiques :
- `getDeparturePlaces($postId)` - Récupère lieux + vols
- `getTravelDates($postId)` - Récupère les dates
- `getAvailableDatesArray($postId)` - Dates en array simple

### Templates WordPress

#### Modifiés :
- `searchbar.php` - Affiche les selects et récupère les données
- `booking-box.php` - Ajout du champ caché `departure_place_id`

## Validation

### Côté Laravel
- Un lieu sans vol n'est pas sauvegardé
- Les dates sont uniques par voyage
- Validation des types de données

### Côté WordPress
- Affichage seulement si des données existent
- Désactivation des dates non disponibles
- Messages clairs si aucune configuration

## Personnalisation CSS

Les styles sont inclus directement dans `searchbar.php` :
- `.aj-flight-details` - Conteneur des détails de vol
- `.aj-flight-card` - Carte d'un vol
- `.aj-search-value--disabled` - État désactivé

Vous pouvez les personnaliser via votre CSS de thème.

## Migration de Données Existantes

Si vous avez des voyages existants :

1. Ils continueront à fonctionner normalement
2. Les nouvelles sections seront simplement vides
3. Configurez progressivement les lieux et dates pour chaque voyage

## Dépannage

### Les lieux ne s'affichent pas
- Vérifier que les migrations ont bien été exécutées
- Vérifier que le `laravel_id` ou `aj_laravel_id` existe dans les post_meta WP
- Vérifier que les lieux ont au moins un vol aller

### Le calendrier n'affiche rien
- Vérifier que des dates ont été configurées pour ce voyage
- Vérifier que les dates sont actives (`is_active = 1`)

### Les vols ne s'affichent pas
- Vérifier dans la console JavaScript pour voir les erreurs
- Vérifier que les données JSON sont correctement encodées
- Vérifier les attributs `data-departure-places` sur le div `.aj-searchbar`

## Améliorations Futures Possibles

1. **Intégration jQuery UI Datepicker** - Pour une meilleure UX du calendrier
2. **Gestion des prix par lieu** - Prix différent selon le lieu de départ
3. **Disponibilité des places** - Afficher places restantes pour chaque date
4. **Multi-vols** - Support des vols avec escales
5. **Notifications** - Alertes quand un lieu ou une date devient indisponible

## Support

Pour toute question ou problème :
1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier la console JavaScript du navigateur
3. Vérifier les erreurs PHP WordPress (WP_DEBUG)

---

**Version** : 1.0.0  
**Date** : 16 Février 2026  
**Auteur** : Équipe Développement Ajinsafro
