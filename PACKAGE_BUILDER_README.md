# Package Builder - Documentation

## Vue d'ensemble

Le **Package Builder** est un système dynamique de construction de packages de voyage style MakeMyTrip, permettant aux clients de personnaliser leur voyage en ajoutant, supprimant ou modifiant des items (vols, hôtels, activités, etc.).

## Architecture

### Tables de données

1. **voyages** - Table principale des voyages (existante + nouveaux champs WP)
   - `wp_post_id` - ID du post WordPress associé
   - `wp_synced_at` - Date de dernière synchronisation
   - `wp_sync_hash` - Hash pour détecter les changements

2. **travel_day_items** - Items du package par jour
   - Types: `flight`, `hotel_stay`, `transfer`, `activity`, `meal`, `addon`
   - Support multi-jours (start_day, end_day, nights)
   - Prix delta par personne (en centimes)
   - Options alternatives (JSON)

3. **package_sessions** - Sessions de configuration client
   - UUID comme identifiant
   - Nombre de voyageurs (adults/children/infants)
   - État des modifications (removed/added/modified items)
   - Expiration 24h

4. **checkout_tokens** - Tokens de checkout avec prix verrouillé
   - Token unique `chk_xxxxx`
   - Prix bloqué pour 15 minutes
   - Lien vers la session

## API Publique

### 1. Obtenir l'état du package

**Endpoint:** `GET /api/public/tours/{voyage_id}/package-state`

**Paramètres (query):**
- `session_id` (optionnel) - UUID de session existante
- `pax_adults` (optionnel, défaut: 2) - Nombre d'adultes
- `pax_children` (optionnel, défaut: 0) - Nombre d'enfants
- `pax_infants` (optionnel, défaut: 0) - Nombre de bébés
- `currency` (optionnel) - Devise (MAD par défaut)

**Réponse JSON:**

```json
{
  "success": true,
  "data": {
    "tour": {
      "id": 1,
      "name": "Circuit Découverte Dubai",
      "slug": "circuit-decouverte-dubai",
      "destination": "Dubai, UAE",
      "duration_text": "7 jours / 6 nuits",
      "total_days": 7,
      "total_nights": 6,
      "featured_image": "https://example.com/storage/travels/featured/image.jpg",
      "gallery": [
        {
          "id": 1,
          "url": "https://example.com/storage/travels/gallery/img1.jpg",
          "sort_order": 0
        }
      ]
    },
    "session": {
      "id": "9a5c3e40-...",
      "pax_adults": 2,
      "pax_children": 0,
      "pax_infants": 0,
      "total_pax": 2,
      "currency": "MAD",
      "expires_at": "2026-02-04T10:30:00+00:00",
      "state": {
        "removed_items": [],
        "added_items": [],
        "modified_items": {}
      }
    },
    "included_counters": {
      "flight": {
        "included": 2,
        "optional": 0,
        "selected": 2
      },
      "hotel_stay": {
        "included": 1,
        "optional": 0,
        "selected": 1
      },
      "transfer": {
        "included": 4,
        "optional": 0,
        "selected": 4
      },
      "activity": {
        "included": 5,
        "optional": 2,
        "selected": 5
      },
      "meal": {
        "included": 14,
        "optional": 0,
        "selected": 14
      },
      "addon": {
        "included": 0,
        "optional": 3,
        "selected": 0
      }
    },
    "pricing": {
      "base_per_person": 850000,
      "options_per_person": 0,
      "total_per_person": 850000,
      "total_group": 1700000,
      "breakdown": {
        "base": 850000,
        "included_items": [],
        "optional_selected": []
      },
      "delta_last_action": 0,
      "currency": "MAD",
      "pax_adults": 2,
      "pax_children": 0,
      "pax_infants": 0
    },
    "days": [
      {
        "day_number": 1,
        "title": "Arrivée à Dubai",
        "city": "Dubai",
        "day_type": "arrivee",
        "day_label": "inclus",
        "nights": 1,
        "meals": {
          "breakfast": false,
          "lunch": false,
          "dinner": true
        },
        "description": "Accueil à l'aéroport et transfert à l'hôtel",
        "content_html": "<p>Accueil chaleureux...</p>",
        "items": [
          {
            "id": 1,
            "type": "flight",
            "type_label": "Vol",
            "title": "Vol international Paris - Dubai",
            "details": "Vol direct avec bagages inclus",
            "included": true,
            "selected": true,
            "price_delta_per_person": 0,
            "formatted_price": "Inclus",
            "start_day": 1,
            "end_day": null,
            "nights": 0,
            "is_multi_day": false,
            "duration_days": 1,
            "meta": {
              "departure_time": "08:00",
              "arrival_time": "17:30",
              "airline": "Emirates"
            },
            "sort_order": 0
          },
          {
            "id": 2,
            "type": "transfer",
            "type_label": "Transfert",
            "title": "Transfert aéroport - hôtel",
            "details": "Véhicule privé climatisé",
            "included": true,
            "selected": true,
            "price_delta_per_person": 0,
            "formatted_price": "Inclus",
            "start_day": 1,
            "end_day": null,
            "nights": 0,
            "is_multi_day": false,
            "duration_days": 1,
            "meta": null,
            "sort_order": 1
          },
          {
            "id": 3,
            "type": "hotel_stay",
            "type_label": "Hébergement",
            "title": "Hôtel 4* - Downtown Dubai",
            "details": "Chambre double avec petit-déjeuner",
            "included": true,
            "selected": true,
            "price_delta_per_person": 0,
            "formatted_price": "Inclus",
            "start_day": 1,
            "end_day": 6,
            "nights": 6,
            "is_multi_day": true,
            "duration_days": 6,
            "meta": {
              "hotel_name": "Rove Downtown",
              "room_type": "Standard Double"
            },
            "sort_order": 2,
            "options": {
              "upgrade_5star": {
                "title": "Upgrade hôtel 5* luxe",
                "price_delta": 25000
              },
              "single_room": {
                "title": "Supplément chambre individuelle",
                "price_delta": 12000
              }
            },
            "active_option": null
          }
        ]
      }
    ],
    "catalog": {
      "available_flights": [],
      "available_hotels": [],
      "available_activities": [],
      "available_transfers": []
    }
  }
}
```

### 2. Effectuer une action sur le package

**Endpoint:** `POST /api/public/package/session/{session_id}/action`

**Paramètres (body JSON):**

**Action: ADD** (Ajouter un item optionnel)
```json
{
  "action": "add",
  "add_data": {
    "day_number": 3,
    "type": "activity",
    "title": "Safari dans le désert",
    "price_delta_per_person": 8000
  }
}
```

**Action: REMOVE** (Retirer un item)
```json
{
  "action": "remove",
  "item_id": 15
}
```

**Action: MODIFY** (Modifier/upgrade un item)
```json
{
  "action": "modify",
  "item_id": 3,
  "new_option": {
    "title": "Upgrade hôtel 5* luxe",
    "price_delta": 25000
  }
}
```

**Réponse:**
```json
{
  "success": true,
  "message": "Action effectuée avec succès.",
  "data": {
    // Same structure as package-state with updated values
    "pricing": {
      "delta_last_action": 8000,
      // ... rest of pricing
    }
  }
}
```

### 3. Créer un checkout token

**Endpoint:** `POST /api/public/checkout/create`

**Paramètres (body JSON):**
```json
{
  "session_id": "9a5c3e40-..."
}
```

**Réponse:**
```json
{
  "success": true,
  "data": {
    "checkout_token": "chk_a7b3c9d1e2f4...",
    "redirect_url": "https://example.com/booking/checkout/chk_a7b3c9d1e2f4...",
    "expires_at": "2026-02-03T11:00:00+00:00",
    "remaining_seconds": 900
  }
}
```

## Interface Admin

### Gestion des items

Dans l'édition d'un voyage (`/admin/circuits/voyages/{id}/edit`), une nouvelle section "Package Builder - Items par jour" est disponible.

**Fonctionnalités:**
- Accordéon par jour de programme
- Liste des items avec type, titre, prix
- Boutons Modifier/Supprimer
- Bouton "Ajouter un item" par jour
- Modal de création/édition avec tous les champs

**Champs du formulaire item:**
- Jour principal (day_number)
- Type (dropdown)
- Titre
- Détails
- Jours début/fin (pour hotel_stay)
- Nombre de nuits
- Inclus (checkbox)
- Prix delta par personne
- Options alternatives (JSON)
- Metadata (JSON)

### Routes Admin

```
POST   /admin/circuits/voyages/{voyage}/items                    - Créer un item
GET    /admin/circuits/voyages/{voyage}/items/{item}/edit        - Récupérer données item (AJAX)
PUT    /admin/circuits/voyages/{voyage}/items/{item}             - Mettre à jour un item
DELETE /admin/circuits/voyages/{voyage}/items/{item}             - Supprimer un item
POST   /admin/circuits/voyages/{voyage}/items/reorder            - Réordonner les items
```

## Page Checkout

**Route:** `/booking/checkout/{token}`

**Affiche:**
- Informations du voyage
- Nombre de voyageurs
- Programme avec items inclus
- Détail des prix (breakdown)
- Timer de verrouillage du prix (15 min)
- Formulaire de confirmation

**Traitement:** `POST /booking/checkout/{token}` (à implémenter côté paiement)

## Installation & Migration

```bash
# 1. Lancer les migrations
php artisan migrate

# 2. Créer le lien symbolique pour storage (si pas déjà fait)
php artisan storage:link

# 3. (Optionnel) Seed items à partir des jours existants
php artisan db:seed --class=TravelDayItemsSeeder
```

## Utilisation WordPress

Les champs `wp_post_id`, `wp_synced_at`, `wp_sync_hash` dans la table `voyages` permettent la synchronisation avec WordPress.

**Workflow de sync:**
1. WordPress envoie les données via API Laravel
2. Laravel crée/met à jour le voyage + stocke wp_post_id
3. wp_sync_hash = hash des données pour détecter changements
4. WordPress récupère les données de package via API publique

## Prix (Format)

Tous les prix sont stockés en **centimes** (integers) :
- 850000 = 8500.00 MAD
- Évite les problèmes de précision float
- Facilite les calculs

**Conversion:**
```php
// Cents -> Display
$amount = $cents / 100; // 850000 -> 8500.00

// Display -> Cents
$cents = (int) round($amount * 100); // 8500.00 -> 850000
```

## Sécurité

- Sessions expiration : 24h automatique
- Checkout tokens expiration : 15 min
- Validation stricte des actions (FormRequest)
- Protection CSRF sur formulaires
- Validation des relations (voyage_id, session_id)

## Tests suggérés

1. **Créer un voyage avec programme**
2. **Ajouter des items via admin** (vols, hôtels, activités)
3. **Appeler GET /api/public/tours/{id}/package-state**
4. **Effectuer des actions** (add/remove/modify)
5. **Créer un checkout token**
6. **Visiter la page checkout**
7. **Vérifier expiration du timer**

## Personnalisation future

- Pricing enfants/bébés différencié
- Catalog dynamique (disponibilités réelles)
- Multi-devise avec taux de change
- Intégration paiement (Stripe, PayPal)
- Notifications email
- PDF de confirmation

---

**Contact:** Développeur Laravel - 2026
