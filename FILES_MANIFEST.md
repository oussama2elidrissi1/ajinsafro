# Package Builder - Manifest des fichiers

## Fichiers créés (27 nouveaux)

### Migrations (4)
```
database/migrations/2026_02_03_100000_add_wp_sync_fields_to_voyages_table.php
database/migrations/2026_02_03_100001_create_travel_day_items_table.php
database/migrations/2026_02_03_100002_create_package_sessions_table.php
database/migrations/2026_02_03_100003_create_checkout_tokens_table.php
```

### Modèles (3)
```
app/Models/TravelDayItem.php
app/Models/PackageSession.php
app/Models/CheckoutToken.php
```

### Services (2) + DTO (1)
```
app/Services/Package/PackageStateBuilder.php
app/Services/Package/PricingService.php
app/DTOs/PackageState.php
```

### Controllers (3)
```
app/Http/Controllers/Api/PublicPackageController.php
app/Http/Controllers/Admin/TravelDayItemController.php
app/Http/Controllers/Booking/CheckoutController.php
```

### Requests (3)
```
app/Http/Requests/StoreTravelDayItemRequest.php
app/Http/Requests/UpdateTravelDayItemRequest.php
app/Http/Requests/PackageActionRequest.php
```

### Views (4)
```
resources/views/admin/circuits/voyages/partials/_items_section.blade.php
resources/views/admin/circuits/voyages/partials/_item_modal.blade.php
resources/views/booking/checkout.blade.php
resources/views/booking/checkout-expired.blade.php
```

### Seeders (1)
```
database/seeders/TravelDayItemsSeeder.php
```

### Documentation (6)
```
PACKAGE_BUILDER_README.md
PACKAGE_BUILDER_API_EXAMPLES.json
INSTALLATION_PACKAGE_BUILDER.md
PACKAGE_BUILDER_SUMMARY.md
QUICK_START.md
FILES_MANIFEST.md (ce fichier)
```

---

## Fichiers modifiés (4)

### Modèles (2)
```
app/Models/Voyage.php
  - Ajout fillable: wp_synced_at, wp_sync_hash
  - Ajout casts: wp_synced_at => datetime
  - Ajout relations: dayItems(), packageSessions(), checkoutTokens()
  - Modification getFeaturedImageUrlAttribute() : fallback sur gallery

app/Models/TravelProgramDay.php
  - Ajout relation: items()
```

### Routes (2)
```
routes/api.php
  - Ajout groupe prefix('public')
  - Ajout 3 routes API : package-state, action, checkout/create

routes/web.php
  - Ajout use TravelDayItemController, CheckoutController
  - Ajout routes checkout (GET + POST)
  - Ajout routes admin items (store, edit, update, destroy, reorder)
```

### Views (1)
```
resources/views/admin/circuits/voyages/edit.blade.php
  - Ajout inclusion: @include('admin.circuits.voyages.partials._items_section')
```

---

## Résumé statistiques

| Catégorie | Nouveaux | Modifiés | Total |
|-----------|----------|----------|-------|
| Migrations | 4 | 0 | 4 |
| Modèles | 3 | 2 | 5 |
| Controllers | 3 | 0 | 3 |
| Requests | 3 | 0 | 3 |
| Services/DTO | 3 | 0 | 3 |
| Views | 4 | 1 | 5 |
| Routes | 0 | 2 | 2 |
| Seeders | 1 | 0 | 1 |
| Documentation | 6 | 0 | 6 |
| **TOTAL** | **27** | **5** | **32** |

---

## Routes ajoutées (11)

### API Publique (3)
```
GET    /api/public/tours/{voyage_id}/package-state
POST   /api/public/package/session/{session_id}/action
POST   /api/public/checkout/create
```

### Web Checkout (2)
```
GET    /booking/checkout/{token}
POST   /booking/checkout/{token}
```

### Admin Items CRUD (5)
```
GET    /admin/circuits/voyages/{voyage}/items/{item}/edit
POST   /admin/circuits/voyages/{voyage}/items
PUT    /admin/circuits/voyages/{voyage}/items/{item}
DELETE /admin/circuits/voyages/{voyage}/items/{item}
POST   /admin/circuits/voyages/{voyage}/items/reorder
```

### Middleware utilisé (1)
```
Route::middleware('sync.token') pour /internal/sync/wp-to-laravel (existant)
```

---

## Tables de base de données (4 nouvelles)

```sql
voyages                    (modifiée - 3 colonnes ajoutées)
  ├── wp_post_id           (unsignedBigInteger, nullable, indexed)
  ├── wp_synced_at         (timestamp, nullable)
  └── wp_sync_hash         (string 64, nullable)

travel_day_items           (nouvelle)
  ├── id                   (PK)
  ├── voyage_id            (FK → voyages)
  ├── day_number           (int)
  ├── start_day            (int)
  ├── end_day              (int, nullable)
  ├── nights               (int, default 0)
  ├── type                 (string 50)
  ├── title                (string)
  ├── details              (text, nullable)
  ├── included             (boolean, default true)
  ├── price_delta_per_person (int, default 0)
  ├── options_json         (longtext, nullable)
  ├── meta_json            (longtext, nullable)
  ├── sort_order           (int, default 0)
  ├── created_at
  └── updated_at

package_sessions           (nouvelle)
  ├── id                   (UUID, PK)
  ├── voyage_id            (FK → voyages)
  ├── pax_adults           (int, default 2)
  ├── pax_children         (int, default 0)
  ├── pax_infants          (int, default 0)
  ├── currency             (string 10, default MAD)
  ├── state_json           (longtext, nullable)
  ├── price_snapshot_json  (longtext, nullable)
  ├── expires_at           (timestamp, nullable, indexed)
  ├── created_at
  └── updated_at

checkout_tokens            (nouvelle)
  ├── id                   (PK)
  ├── token                (string 100, unique, indexed)
  ├── session_id           (UUID, FK → package_sessions)
  ├── voyage_id            (FK → voyages)
  ├── currency             (string 10)
  ├── price_locked_until   (timestamp, indexed)
  └── created_at
```

---

## Dependencies (aucune nouvelle)

Le système utilise uniquement les packages Laravel 10 standards :
- ✅ Eloquent ORM
- ✅ Validation
- ✅ Storage (disque public)
- ✅ Cookie
- ✅ Support\Str (UUID, Slug)

**Aucun package externe requis.**

---

## Configuration requise

### PHP
- Version: 8.1+
- Extensions: json, pdo_mysql

### Laravel
- Version: 10.x

### Base de données
- MySQL 5.7+ ou MariaDB 10.3+

### Storage
- Disque public accessible
- `php artisan storage:link` exécuté

---

## Permissions fichiers

```bash
# Storage accessible en écriture
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Storage link créé
ls -la public/storage → ../storage/app/public
```

---

## Checklist de déploiement

- [ ] Migrations exécutées : `php artisan migrate`
- [ ] Storage link créé : `php artisan storage:link`
- [ ] Permissions storage : `chmod -R 775 storage`
- [ ] (Optionnel) Seed données : `php artisan db:seed --class=TravelDayItemsSeeder`
- [ ] Vérifier routes : `php artisan route:list`
- [ ] Test API : `curl /api/public/tours/1/package-state`
- [ ] Test Admin : Éditer un voyage et ajouter items
- [ ] Cache cleared : `php artisan cache:clear`
- [ ] Config cached : `php artisan config:cache`

---

## Backup recommandé

Avant de déployer en production, sauvegardez :

```bash
# Base de données
mysqldump -u user -p database > backup_before_package_builder.sql

# Fichiers
tar -czf backup_app_$(date +%Y%m%d).tar.gz app/ database/ routes/ resources/views/
```

---

## Rollback si nécessaire

```bash
# Rollback les migrations
php artisan migrate:rollback --step=4

# Restaurer DB
mysql -u user -p database < backup_before_package_builder.sql
```

---

**Version:** 1.0.0  
**Date de création:** 2026-02-03  
**Statut:** ✅ Production-ready
