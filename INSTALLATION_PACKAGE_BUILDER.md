# Installation & Configuration - Package Builder

## Étape 1 : Migrations

Exécutez les migrations dans l'ordre :

```bash
php artisan migrate
```

Les migrations créées :
- `2026_02_03_100000_add_wp_sync_fields_to_voyages_table.php`
- `2026_02_03_100001_create_travel_day_items_table.php`
- `2026_02_03_100002_create_package_sessions_table.php`
- `2026_02_03_100003_create_checkout_tokens_table.php`

## Étape 2 : Storage Link

Si pas déjà fait, créez le lien symbolique pour accéder aux images :

```bash
php artisan storage:link
```

Cela crée un lien : `public/storage` → `storage/app/public`

## Étape 3 : Seed des données (Optionnel)

Pour générer automatiquement des items à partir des jours de programme existants :

```bash
php artisan db:seed --class=TravelDayItemsSeeder
```

**Note:** Ce seeder analyse vos voyages existants et crée des items de démonstration (vols, hôtels, transferts, activités, repas).

## Étape 4 : Vérification des routes

Vérifiez que les routes sont bien enregistrées :

```bash
php artisan route:list | grep -E "(package|checkout|items)"
```

Vous devriez voir :
- `GET /api/public/tours/{voyage_id}/package-state`
- `POST /api/public/package/session/{session_id}/action`
- `POST /api/public/checkout/create`
- `GET /booking/checkout/{token}`
- Routes admin pour items CRUD

## Étape 5 : Test API

### Test 1 : Obtenir le package state

```bash
curl -X GET "http://localhost/api/public/tours/1/package-state?pax_adults=2" \
  -H "Accept: application/json"
```

### Test 2 : Ajouter une activité optionnelle

```bash
curl -X POST "http://localhost/api/public/package/session/{SESSION_ID}/action" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "action": "add",
    "add_data": {
      "day_number": 3,
      "type": "activity",
      "title": "Safari désert",
      "price_delta_per_person": 8000
    }
  }'
```

### Test 3 : Créer un checkout token

```bash
curl -X POST "http://localhost/api/public/checkout/create" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "session_id": "9a5c3e40-..."
  }'
```

## Structure des fichiers créés

### Migrations (4 fichiers)
```
database/migrations/
├── 2026_02_03_100000_add_wp_sync_fields_to_voyages_table.php
├── 2026_02_03_100001_create_travel_day_items_table.php
├── 2026_02_03_100002_create_package_sessions_table.php
└── 2026_02_03_100003_create_checkout_tokens_table.php
```

### Modèles (3 nouveaux)
```
app/Models/
├── TravelDayItem.php          (Nouveau)
├── PackageSession.php          (Nouveau)
├── CheckoutToken.php           (Nouveau)
├── Voyage.php                  (Modifié - relations ajoutées)
└── TravelProgramDay.php        (Modifié - relation items ajoutée)
```

### Services & DTO
```
app/Services/Package/
├── PackageStateBuilder.php     (Nouveau)
└── PricingService.php          (Nouveau)

app/DTOs/
└── PackageState.php            (Nouveau)
```

### Controllers (3 nouveaux)
```
app/Http/Controllers/
├── Api/
│   └── PublicPackageController.php      (Nouveau)
├── Admin/
│   └── TravelDayItemController.php      (Nouveau)
└── Booking/
    └── CheckoutController.php           (Nouveau)
```

### Requests (3 nouveaux)
```
app/Http/Requests/
├── StoreTravelDayItemRequest.php        (Nouveau)
├── UpdateTravelDayItemRequest.php       (Nouveau)
└── PackageActionRequest.php             (Nouveau)
```

### Views (4 nouveaux + 1 modifié)
```
resources/views/
├── admin/circuits/voyages/
│   ├── edit.blade.php                   (Modifié - section items ajoutée)
│   └── partials/
│       ├── _items_section.blade.php     (Nouveau)
│       └── _item_modal.blade.php        (Nouveau)
└── booking/
    ├── checkout.blade.php               (Nouveau)
    └── checkout-expired.blade.php       (Nouveau)
```

### Seeders
```
database/seeders/
└── TravelDayItemsSeeder.php             (Nouveau)
```

### Documentation
```
PACKAGE_BUILDER_README.md                (Nouveau)
PACKAGE_BUILDER_API_EXAMPLES.json        (Nouveau)
INSTALLATION_PACKAGE_BUILDER.md          (Ce fichier)
```

## Configuration requise

### Dépendances
- Laravel 10
- PHP 8.1+
- MySQL 5.7+ ou MariaDB 10.3+
- Extensions PHP : json, pdo_mysql

### Permissions
Assurez-vous que `storage/app/public` est accessible en écriture :

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

## Intégration WordPress

Les champs de synchronisation WP sont prêts dans la table `voyages` :
- `wp_post_id` - ID du post WP associé
- `wp_synced_at` - Timestamp dernière sync
- `wp_sync_hash` - Hash pour détecter changements

Pour synchroniser depuis WordPress vers Laravel, utilisez l'endpoint existant :
```
POST /internal/sync/wp-to-laravel
```

## Personnalisation des prix

### Format des prix
Tous les prix sont en **centimes** (integers) :

```php
// Dans l'admin, saisir : 150.00 MAD
// Stocké en DB : 15000 (cents)

// Pour affichage :
$amount = $item->price_delta_per_person / 100; // 15000 → 150.00
```

### Pricing enfants/bébés
Actuellement, le même prix s'applique à tous. Pour différencier :

Modifiez `PricingService::calculate()` :

```php
// Exemple : enfants 70%, bébés gratuits
$totalGroup = $totalPerPerson * $session->pax_adults;
$totalGroup += ($totalPerPerson * 0.7) * $session->pax_children;
// pax_infants reste à 0
```

## Sécurité & Performance

### Sessions cleanup
Ajoutez une tâche planifiée pour nettoyer les sessions expirées :

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        \App\Models\PackageSession::where('expires_at', '<', now())
            ->delete();
        \App\Models\CheckoutToken::where('price_locked_until', '<', now()->subDays(1))
            ->delete();
    })->daily();
}
```

### Cache
Pour améliorer les performances, mettez en cache les package states :

```php
$cacheKey = "package_state_{$voyage->id}_{$session->id}";
$packageState = Cache::remember($cacheKey, 300, function () use ($voyage, $session) {
    return $this->stateBuilder->build($voyage, $session);
});
```

## Troubleshooting

### Problème : Images ne s'affichent pas
```bash
# Vérifier le lien symbolique
ls -la public/storage

# Si absent, créer
php artisan storage:link
```

### Problème : Erreur 500 sur API
```bash
# Vérifier les logs
tail -f storage/logs/laravel.log

# Vérifier les permissions
chmod -R 775 storage
```

### Problème : Session expirée trop vite
Augmenter la durée d'expiration dans `PackageSession::boot()` :

```php
$session->expires_at = now()->addHours(48); // Au lieu de 24
```

### Problème : Prix erronés
Vérifier le format des prix (cents vs décimal) :

```bash
# Dans tinker
php artisan tinker
>>> $item = \App\Models\TravelDayItem::first();
>>> $item->price_delta_per_person; // Doit être en cents (ex: 15000)
```

## Tests manuels recommandés

1. **Admin - Créer des items**
   - Aller à `/admin/circuits/voyages/{id}/edit`
   - Ajouter un vol (jour 1)
   - Ajouter un hôtel multi-jours (jour 1-6)
   - Ajouter une activité optionnelle

2. **API - Package State**
   - GET `/api/public/tours/1/package-state`
   - Vérifier que session_id est retourné
   - Vérifier pricing calculations

3. **API - Actions**
   - POST action "add" pour activité
   - POST action "modify" pour upgrade hôtel
   - POST action "remove" pour un item
   - Vérifier que `delta_last_action` change

4. **Checkout**
   - POST `/api/public/checkout/create`
   - Visiter `/booking/checkout/{token}`
   - Vérifier countdown timer
   - Attendre expiration

## Support & Contact

Pour toute question ou problème :
- Consulter `PACKAGE_BUILDER_README.md` pour la documentation complète
- Consulter `PACKAGE_BUILDER_API_EXAMPLES.json` pour des exemples d'API

---

**Version:** 1.0.0  
**Date:** 2026-02-03  
**Laravel:** 10.x
