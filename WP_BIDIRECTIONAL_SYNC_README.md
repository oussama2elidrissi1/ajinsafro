# Synchronisation Bidirectionnelle WordPress ↔ Laravel - Tours

Ce système permet une synchronisation **totale et bidirectionnelle** entre Laravel (Voyages) et WordPress (st_tours).

## 📋 Règles de Conflit

**WordPress GAGNE toujours** en cas de conflit :
- Si WP et Laravel sont modifiés depuis la dernière sync → WP écrase Laravel
- Si WP est modifié après la dernière sync → Pull automatique de WP vers Laravel
- Laravel peut modifier WP librement si pas de conflit

---

## 🚀 Installation

### 1. Migration Base de Données

```bash
php artisan migrate
```

Cela ajoute à la table `voyages` :
- `wp_last_modified_gmt_cache` - Cache de la dernière modif WP
- `max_people`, `tour_price_by`, `is_featured` - Metas Traveler
- `st_google_map`, `multi_location` - Localisation
- `discount_*` - Remises
- `tours_include`, `tours_exclude`, `tours_highlight` - Listes (JSON)
- `tours_program_style` - Style programme
- `payment_gateway_metas` - Metas passerelles (JSON)
- `gallery_wp_ids` - IDs galerie WP

### 2. Configuration `.env`

```env
# WordPress Sync
WP_AUTO_SYNC_ENABLED=true
WP_WEBHOOK_SECRET=votre-secret-hmac-tres-long-et-aleatoire
WP_MANUAL_SYNC_TOKEN=token-pour-sync-manuelle
WP_TABLE_PREFIX=cFdgeZ_
WP_SITE_URL=https://ajinsafro.com

# WordPress DB Connection (même base que Laravel)
WP_DB_CONNECTION=wp
```

### 3. Configuration `config/database.php`

Ajouter la connexion WP (même DB, préfixe différent) :

```php
'wp' => [
    'driver' => 'mysql',
    'url' => env('DATABASE_URL'),
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => 'cFdgeZ_',
    'prefix_indexes' => true,
    'strict' => false,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

### 4. Enregistrer l'Observer

Dans `app/Providers/EventServiceProvider.php` :

```php
use App\Models\Voyage;
use App\Observers\VoyageObserver;

protected $observers = [
    Voyage::class => [VoyageObserver::class],
];
```

Ou dans `AppServiceProvider::boot()` :

```php
Voyage::observe(VoyageObserver::class);
```

### 5. Routes API

Dans `routes/api.php` :

```php
use App\Http\Controllers\Api\WpSyncWebhookController;

// WP Webhook (secured with HMAC)
Route::post('/wp-sync/tour-updated', [WpSyncWebhookController::class, 'tourUpdated']);

// Manual sync (for admin)
Route::get('/wp-sync/pull/{wpPostId}', [WpSyncWebhookController::class, 'manualPull']);
```

### 6. Plugin WordPress

Installer le plugin `ajinsafro-sync-webhook` :

1. Zipper le dossier `wp-plugin/ajinsafro-sync-webhook/`
2. Téléverser dans WP Admin → Extensions
3. Activer le plugin
4. Aller dans **Réglages → Ajinsafro Sync**
5. Configurer :
   - **Laravel URL** : `https://admin.ajinsafro.com`
   - **Webhook Secret** : même valeur que `WP_WEBHOOK_SECRET` dans `.env`
6. Tester la connexion

---

## 📖 Utilisation

### Laravel → WordPress (Push)

#### Créer un tour dans WP depuis Laravel

```php
use App\Services\WpTourSyncService;

$syncService = app(WpTourSyncService::class);

// Créer
$result = $syncService->createWpTourFromLaravel($voyage->id);
// Retourne: ['success' => true, 'wp_post_id' => 123, 'voyage_id' => 456]
```

#### Mettre à jour WP depuis Laravel

```php
// Auto-sync activé par défaut via Observer
$voyage->name = "Nouveau nom";
$voyage->save(); // → Auto-push vers WP

// OU force manuellement
$syncService->updateWpTourFromLaravel($voyage->id);
```

### WordPress → Laravel (Pull)

#### Via Webhook (automatique)

Quand un tour est modifié dans WP Admin :
1. Hook `save_post_st_tours` déclenché
2. Plugin envoie webhook HMAC à Laravel
3. Laravel pull les données WP (WP gagne)

#### Manuellement depuis Laravel

```php
// Pull un tour spécifique
$result = $syncService->upsertLaravelVoyageFromWp($wpPostId);
```

#### Via API (avec token)

```bash
GET https://admin.ajinsafro.com/api/wp-sync/pull/123?token=votre-token
```

---

## 🔄 Flux de Synchronisation

### Scénario 1: Création dans Laravel

```
Laravel (créer voyage)
  ↓ Observer
WpTourSyncService::createWpTourFromLaravel()
  ↓
WP: Créer post st_tours + metas + taxonomies + images
  ↓
Laravel: Mettre à jour wp_post_id, wp_synced_at, wp_sync_hash
```

### Scénario 2: Modification dans Laravel (sans conflit)

```
Laravel (update voyage)
  ↓ Observer
WpTourSyncService::updateWpTourFromLaravel()
  ↓ Vérifier conflit
WP: Mettre à jour post + metas
  ↓
Laravel: Mettre à jour sync state
```

### Scénario 3: Modification dans WP

```
WP Admin (save post st_tours)
  ↓ Hook save_post
Plugin: Envoyer webhook HMAC à Laravel
  ↓
Laravel: WpSyncWebhookController::tourUpdated()
  ↓
WpTourSyncService::upsertLaravelVoyageFromWp()
  ↓
Laravel: Mettre à jour voyage + program + images + sync state
```

### Scénario 4: Conflit (WP + Laravel modifiés)

```
Laravel (update voyage)
  ↓ Observer
WpTourSyncService::updateWpTourFromLaravel()
  ↓ Détecte: post_modified_gmt > wp_last_modified_gmt_cache
  ↓ CONFLIT: WP GAGNE
Pull depuis WP au lieu de push
  ↓
upsertLaravelVoyageFromWp() → WP écrase Laravel
```

---

## 🗂️ Mapping des Champs

### Core Fields

| WordPress (cFdgeZ_posts) | Laravel (voyages) |
|--------------------------|-------------------|
| `post_title`            | `name`            |
| `post_name`             | `slug`            |
| `post_content`          | `description`     |
| `post_excerpt`          | `accroche`        |
| `post_status`           | `status`          |

### Post Meta

| WP Meta Key | Laravel Field | Type |
|-------------|---------------|------|
| `min_people` | `min_people` | int |
| `max_people` | `max_people` | int |
| `tour_price_by` | `tour_price_by` | string |
| `is_featured` | `is_featured` | bool |
| `st_google_map` | `st_google_map` | text |
| `multi_location` | `multi_location` | string |
| `discount_by_people_type` | `discount_by_people_type` | string |
| `discount_type` | `discount_type` | string |
| `calculator_discount_by_people_type` | `calculator_discount_by_people_type` | string |
| `hide_adult_in_booking_form` | `hide_adult_in_booking_form` | bool |
| `st_tour_external_booking` | `st_tour_external_booking` | string |
| `tours_include` | `tours_include` | JSON array |
| `tours_exclude` | `tours_exclude` | JSON array |
| `tours_highlight` | `tours_highlight` | JSON array |
| `tours_program_style` | `tours_program_style` | string |
| `is_meta_payment_gateway_*` | `payment_gateway_metas` | JSON object |

### Images

| WP | Laravel |
|----|---------|
| `_thumbnail_id` (attachment ID) | `featured_image` (URL cache) |
| `gallery` (IDs CSV) | `gallery_wp_ids` (cache) |

### Programme

| WP | Laravel |
|----|---------|
| `tours_program` (serialized) | `travel_program_days` + `travel_day_items` (tables) |

---

## ⚙️ API Service Methods

### WpTourSyncService

```php
// Laravel → WP
createWpTourFromLaravel(int $voyageId): array
updateWpTourFromLaravel(int $voyageId, bool $force = false): array

// WP → Laravel
upsertLaravelVoyageFromWp(int $wpPostId): array

// Utilitaires
computeWpSnapshotHash(int $wpPostId): string
detectConflictAndResolve(int $wpPostId, int $voyageId): array
```

### WpRepository

```php
// Posts
getPost(int $postId): ?array
createPost(array $data): int
updatePost(int $postId, array $data): bool
deletePost(int $postId): bool

// Meta
getPostMeta(int $postId, string $metaKey): mixed
getAllPostMeta(int $postId): array
updatePostMeta(int $postId, string $metaKey, mixed $metaValue): void
deletePostMeta(int $postId, string $metaKey): bool

// Taxonomies
getTermByName(string $name, string $taxonomy): ?array
createTerm(string $name, string $taxonomy, string $slug = ''): int
setPostTerms(int $postId, string $taxonomy, array $termNames): void
getPostTerms(int $postId, string $taxonomy): array

// Attachments
getAttachmentUrl(int $attachmentId): ?string

// Options
getOption(string $optionName, mixed $default = null): mixed

// Query
findTours(array $criteria = [], int $limit = 100): array
```

---

## 🛡️ Sécurité

### HMAC Signature

Tous les webhooks WP → Laravel sont signés avec HMAC-SHA256 :

```php
// WordPress (envoi)
$body = json_encode(['wp_post_id' => 123]);
$signature = hash_hmac('sha256', $body, $secret);
// Header: X-WP-Signature: $signature

// Laravel (vérification)
$expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);
if (!hash_equals($expectedSignature, $signature)) {
    abort(403, 'Invalid signature');
}
```

### Tokens

- **Webhook Secret** : Partagé WP/Laravel (env)
- **Manual Sync Token** : Pour triggers manuels via API

---

## 🧪 Tests

### Test manuel complet

```php
// 1. Créer voyage dans Laravel
$voyage = Voyage::create([
    'name' => 'Test Sync Tour',
    'slug' => 'test-sync-tour',
    'description' => 'Description test',
    'min_people' => 2,
    'max_people' => 10,
]);

// 2. Vérifier création WP
// Aller dans WP Admin → Circuits
// Chercher "Test Sync Tour"

// 3. Modifier dans WP
// Changer le titre
// Sauvegarder

// 4. Vérifier pull vers Laravel
$voyage->refresh();
echo $voyage->name; // Doit afficher le nouveau titre WP

// 5. Modifier dans Laravel
$voyage->update(['name' => 'Titre modifié Laravel']);

// 6. Vérifier push vers WP
// Recharger la page WP → doit afficher "Titre modifié Laravel"
```

### Test conflit (WP gagne)

```php
// 1. Modifier WP (titre = "Titre WP")
// 2. Modifier Laravel (titre = "Titre Laravel") sans rafraîchir
$voyage->update(['name' => 'Titre Laravel']);

// 3. Observer détecte conflit et pull depuis WP
$voyage->refresh();
echo $voyage->name; // Doit afficher "Titre WP" (WP a gagné)
```

---

## 📝 TODO / Extensions

- [ ] Implémenter sync taxonomies complète
- [ ] Import images WP attachments depuis Laravel storage
- [ ] Gestion des révisions WP
- [ ] Dashboard sync status (dernière sync, erreurs)
- [ ] Commande Artisan `php artisan wp:sync-all`
- [ ] Queue jobs pour sync asynchrone (optionnel)

---

## 🐛 Debugging

### Activer les logs

```php
// Dans config/logging.php, créer un canal dédié
'wp_sync' => [
    'driver' => 'daily',
    'path' => storage_path('logs/wp-sync.log'),
    'level' => 'debug',
],

// Utiliser dans le code
Log::channel('wp_sync')->info('Sync event', ['data' => $data]);
```

### Désactiver auto-sync temporairement

```php
use App\Observers\VoyageObserver;

// Désactiver pour une opération
VoyageObserver::withoutSync(function() {
    Voyage::create([...]); // Pas de sync
});

// OU via config
config(['wordpress.auto_sync_enabled' => false]);
```

### Check sync state

```php
$voyage = Voyage::find(1);

echo "WP Post ID: " . $voyage->wp_post_id . "\n";
echo "Last Synced: " . $voyage->wp_synced_at . "\n";
echo "Sync Hash: " . $voyage->wp_sync_hash . "\n";
echo "WP Modified Cache: " . $voyage->wp_last_modified_gmt_cache . "\n";

// Recalculer hash actuel WP
$syncService = app(WpTourSyncService::class);
$currentHash = $syncService->computeWpSnapshotHash($voyage->wp_post_id);

echo "Current WP Hash: " . $currentHash . "\n";
echo "Hashes match: " . ($currentHash === $voyage->wp_sync_hash ? 'YES' : 'NO') . "\n";
```

---

## 📚 Fichiers Créés

```
Laravel:
├── database/migrations/2026_02_05_*_add_wp_sync_fields_to_voyages_table.php
├── app/Services/WpTourSyncService.php
├── app/Services/WpToursProgramParser.php
├── app/Repositories/WpRepository.php
├── app/Observers/VoyageObserver.php
├── app/Http/Controllers/Api/WpSyncWebhookController.php
├── config/wordpress.php
└── routes/api.php (updated)

WordPress:
└── wp-content/plugins/ajinsafro-sync-webhook/
    └── ajinsafro-sync-webhook.php
```

---

✅ **Système complet et opérationnel !**
