# 📚 API Reference - Synchronisation WordPress/Laravel

## Table des Matières
- [Services](#services)
- [Repositories](#repositories)
- [Observers](#observers)
- [Commands](#commands)
- [HTTP Endpoints](#http-endpoints)
- [Configuration](#configuration)

---

## Services

### WpTourSyncService

**Namespace**: `App\Services\WpTourSyncService`

#### Méthodes Principales

##### `createWpTourFromLaravel(int $voyageId): array`

Crée un nouveau tour WordPress depuis un voyage Laravel.

**Paramètres**:
- `$voyageId` (int): ID du voyage Laravel

**Retourne**: `array`
```php
[
    'success' => true,
    'wp_post_id' => 1234,
    'action' => 'created',
    'voyage_id' => 1
]
```

**Exemple**:
```php
$syncService = app(WpTourSyncService::class);
$result = $syncService->createWpTourFromLaravel(1);

if ($result['success']) {
    echo "Tour créé dans WP avec ID: " . $result['wp_post_id'];
}
```

---

##### `updateWpTourFromLaravel(int $voyageId, bool $force = false): array`

Met à jour un tour WordPress existant depuis Laravel.

**Paramètres**:
- `$voyageId` (int): ID du voyage Laravel
- `$force` (bool): Si `true`, force la mise à jour même en cas de conflit

**Retourne**: `array`

**Gestion des conflits**:
- Si WP a été modifié depuis la dernière sync ET Laravel aussi → WP gagne (pull automatique)
- Utiliser `$force = true` pour forcer le push Laravel → WP

**Exemple**:
```php
// Update normal (détecte conflits)
$result = $syncService->updateWpTourFromLaravel(1);

// Force update (ignore conflits)
$result = $syncService->updateWpTourFromLaravel(1, force: true);
```

---

##### `upsertLaravelVoyageFromWp(int $wpPostId): array`

Crée ou met à jour un voyage Laravel depuis un tour WordPress.

**Paramètres**:
- `$wpPostId` (int): ID du post WordPress (`cFdgeZ_posts.ID`)

**Retourne**: `array`
```php
[
    'success' => true,
    'action' => 'created', // or 'updated'
    'voyage_id' => 1,
    'wp_post_id' => 1234
]
```

**Exemple**:
```php
// Pull depuis WP post ID 1234
$result = $syncService->upsertLaravelVoyageFromWp(1234);

if ($result['action'] === 'created') {
    echo "Nouveau voyage créé: " . $result['voyage_id'];
}
```

---

##### `computeWpSnapshotHash(int $wpPostId): string`

Calcule un hash du snapshot complet d'un tour WordPress (post + metas + taxonomies + images).

**Paramètres**:
- `$wpPostId` (int): ID du post WordPress

**Retourne**: `string` (hash SHA-256)

**Utilité**: Détection de modifications WP sans comparer champ par champ.

**Exemple**:
```php
$hash = $syncService->computeWpSnapshotHash(1234);
// "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
```

---

##### `hasWpConflict(Voyage $voyage, int $wpPostId): bool`

Détecte si WP a été modifié depuis la dernière sync.

**Paramètres**:
- `$voyage` (Voyage): Instance du modèle Laravel
- `$wpPostId` (int): ID du post WordPress

**Retourne**: `bool`

**Exemple**:
```php
$voyage = Voyage::find(1);
if ($syncService->hasWpConflict($voyage, $voyage->wp_post_id)) {
    echo "⚠ Conflit détecté: WP a changé!";
}
```

---

### WpToursProgramParser

**Namespace**: `App\Services\WpToursProgramParser`

#### Méthodes Principales

##### `parseWpProgramToLaravel(mixed $wpProgram): array`

Parse la meta WordPress `tours_program` en structure Laravel.

**Formats supportés**:
- PHP serialized array
- JSON string
- Plain text avec headers "Day 1:", "Day 2:"

**Paramètres**:
- `$wpProgram` (mixed): Valeur de la meta `tours_program`

**Retourne**: `array`
```php
[
    [
        'day_number' => 1,
        'title' => 'Day 1: Arrival',
        'description' => 'Check-in at hotel',
        'items' => [...]
    ],
    ...
]
```

**Exemple**:
```php
$parser = app(WpToursProgramParser::class);
$wpMeta = $wpRepo->getPostMeta(1234, 'tours_program');
$program = $parser->parseWpProgramToLaravel($wpMeta);

foreach ($program as $day) {
    echo "Day {$day['day_number']}: {$day['title']}\n";
}
```

---

##### `generateWpProgramFromLaravel(Collection $programDays): string`

Génère une meta `tours_program` WordPress depuis des modèles Laravel.

**Paramètres**:
- `$programDays` (Collection): Collection de `TravelProgramDay` avec relation `dayItems` chargée

**Retourne**: `string` (PHP serialized)

**Exemple**:
```php
$voyage = Voyage::with('programDays.dayItems')->find(1);
$wpProgram = $parser->generateWpProgramFromLaravel($voyage->programDays);

// Sauvegarder dans WP
$wpRepo->updatePostMeta($voyage->wp_post_id, 'tours_program', $wpProgram);
```

---

## Repositories

### WpRepository

**Namespace**: `App\Repositories\WpRepository`

#### Méthodes Posts

##### `getPost(int $postId): ?array`

Récupère un post WordPress par ID.

**Paramètres**:
- `$postId` (int): ID du post

**Retourne**: `array|null`

**Exemple**:
```php
$wpRepo = app(WpRepository::class);
$post = $wpRepo->getPost(1234);

echo $post['post_title'];
echo $post['post_status'];
```

---

##### `createPost(array $data): int`

Crée un nouveau post WordPress.

**Paramètres**:
- `$data` (array): Données du post
  - `post_title` (required)
  - `post_name` (slug)
  - `post_content`
  - `post_excerpt`
  - `post_status` (publish, draft...)
  - `post_type` (default: st_tours)
  - `post_author` (default: 1)

**Retourne**: `int` (ID du nouveau post)

**Exemple**:
```php
$postId = $wpRepo->createPost([
    'post_title' => 'New Tour',
    'post_name' => 'new-tour',
    'post_content' => 'Description...',
    'post_status' => 'publish',
    'post_type' => 'st_tours',
]);
```

---

##### `updatePost(int $postId, array $data): void`

Met à jour un post WordPress existant.

**Exemple**:
```php
$wpRepo->updatePost(1234, [
    'post_title' => 'Updated Title',
    'post_status' => 'publish',
]);
```

---

##### `deletePost(int $postId): void`

Supprime un post WordPress (soft delete si possible).

**Exemple**:
```php
$wpRepo->deletePost(1234);
```

---

#### Méthodes Post Meta

##### `getPostMeta(int $postId, string $metaKey): mixed`

Récupère une meta WordPress (unserialize automatique si nécessaire).

**Exemple**:
```php
$minPeople = $wpRepo->getPostMeta(1234, 'min_people');
$gallery = $wpRepo->getPostMeta(1234, 'gallery'); // "123,456,789"
```

---

##### `getAllPostMeta(int $postId): array`

Récupère toutes les metas d'un post.

**Retourne**: `array` associatif `[meta_key => meta_value]`

**Exemple**:
```php
$allMetas = $wpRepo->getAllPostMeta(1234);

foreach ($allMetas as $key => $value) {
    echo "$key = $value\n";
}
```

---

##### `updatePostMeta(int $postId, string $metaKey, mixed $metaValue): void`

Met à jour (ou crée) une meta WordPress.

**Serialization automatique** pour arrays/objects.

**Exemple**:
```php
// Simple value
$wpRepo->updatePostMeta(1234, 'min_people', 2);

// Array (sera serialized)
$wpRepo->updatePostMeta(1234, 'tours_include', ['WiFi', 'Breakfast']);

// Gallery IDs
$wpRepo->updatePostMeta(1234, 'gallery', '123,456,789');
```

---

##### `deletePostMeta(int $postId, string $metaKey): void`

Supprime une meta WordPress.

**Exemple**:
```php
$wpRepo->deletePostMeta(1234, 'old_meta_key');
```

---

#### Méthodes Taxonomies

##### `getTermByName(string $name, string $taxonomy): ?array`

Trouve un term par nom et taxonomie.

**Exemple**:
```php
$term = $wpRepo->getTermByName('Adventure', 'st_tour_type');

if ($term) {
    echo $term['term_id'];
    echo $term['slug'];
}
```

---

##### `createTerm(string $name, string $taxonomy, string $slug = ''): int`

Crée un nouveau term WordPress.

**Exemple**:
```php
$termId = $wpRepo->createTerm('Safari', 'st_tour_type', 'safari');
```

---

##### `setPostTerms(int $postId, array $termIds, string $taxonomy): void`

Assigne des terms à un post (remplace les existants).

**Exemple**:
```php
$wpRepo->setPostTerms(1234, [5, 12, 23], 'st_tour_type');
```

---

##### `getPostTerms(int $postId, string $taxonomy): array`

Récupère tous les terms d'un post pour une taxonomie.

**Retourne**: Array de terms
```php
[
    ['term_id' => 5, 'name' => 'Adventure', 'slug' => 'adventure'],
    ['term_id' => 12, 'name' => 'Safari', 'slug' => 'safari'],
]
```

**Exemple**:
```php
$types = $wpRepo->getPostTerms(1234, 'st_tour_type');
foreach ($types as $type) {
    echo $type['name'] . "\n";
}
```

---

#### Méthodes Attachments

##### `getAttachmentUrl(int $attachmentId): ?string`

Récupère l'URL complète d'un attachment WordPress.

**Robuste**: Retourne `null` si attachment manquant (ne crash pas).

**Exemple**:
```php
$url = $wpRepo->getAttachmentUrl(123);

if ($url) {
    echo "<img src='$url' />";
} else {
    echo "Image manquante";
}
```

---

#### Méthodes Options

##### `getOption(string $key): mixed`

Récupère une option WordPress.

**Exemple**:
```php
$siteUrl = $wpRepo->getOption('siteurl');
$blogName = $wpRepo->getOption('blogname');
```

---

#### Méthodes Query

##### `findTours(array $args = [], int $limit = 100): array`

Recherche des tours WordPress avec critères.

**Paramètres**:
- `$args` (array): Filtres
  - `post_status` (string|array): Ex: 'publish', ['publish', 'draft']
  - `post_name` (string): Slug exact
- `$limit` (int): Nombre max de résultats

**Retourne**: Array de posts

**Exemple**:
```php
// Tous les tours publiés
$tours = $wpRepo->findTours(['post_status' => 'publish'], 50);

// Tour par slug
$tour = $wpRepo->findTours(['post_name' => 'safari-kenya']);
```

---

## Observers

### VoyageObserver

**Namespace**: `App\Observers\VoyageObserver`

#### Événements Observés

- `created`: Déclenche `createWpTourFromLaravel()`
- `updated`: Déclenche `updateWpTourFromLaravel()` (avec vérifications anti-loop)

#### Méthodes Statiques

##### `VoyageObserver::withoutSync(Closure $callback): mixed`

Exécute du code sans déclencher l'auto-sync.

**Utilité**: Batch operations, import WP→Laravel

**Exemple**:
```php
use App\Observers\VoyageObserver;

VoyageObserver::withoutSync(function() {
    foreach ($data as $item) {
        Voyage::create($item); // Pas de sync auto
    }
});

// Puis sync manuellement
php artisan wp:sync push --id=...
```

---

##### `VoyageObserver::enableSync(): void`

Réactive l'auto-sync.

---

##### `VoyageObserver::disableSync(): void`

Désactive l'auto-sync.

---

## Commands

### wp:sync

**Namespace**: `App\Console\Commands\WpSyncCommand`

#### Usage

```bash
php artisan wp:sync {action} [options]
```

#### Actions Disponibles

##### `push`

Pousse un voyage Laravel vers WordPress.

**Options**:
- `--id=` (required): ID du voyage Laravel
- `--force`: Force le push (ignore conflits)

**Exemples**:
```bash
# Push normal
php artisan wp:sync push --id=1

# Force push (WP sera overwrité)
php artisan wp:sync push --id=1 --force
```

---

##### `pull`

Tire un tour WordPress vers Laravel.

**Options**:
- `--id=` (required): ID du post WordPress

**Exemples**:
```bash
# Pull depuis WP post ID 1234
php artisan wp:sync pull --id=1234
```

---

##### `pull-all`

Importe tous les tours WordPress publiés dans Laravel.

**Options**: Aucune

**Exemples**:
```bash
php artisan wp:sync pull-all

# Output:
# Pulling all published tours from WordPress...
# =====================================
# [=====>                             ] 15/100
# ...
# Summary:
# Created: 10
# Updated: 5
# Errors: 0
```

---

##### `status`

Affiche l'état de synchronisation.

**Options**:
- `--id=` (optional): ID du voyage Laravel pour status détaillé

**Exemples**:
```bash
# Status global
php artisan wp:sync status

# Output:
# +-------------------------+-------+
# | Metric                  | Count |
# +-------------------------+-------+
# | Total Voyages           | 156   |
# | Linked to WP            | 150   |
# | Ever Synced             | 142   |
# | Not Linked              | 6     |
# | Recent Sync (24h)       | 23    |
# +-------------------------+-------+

# Status spécifique
php artisan wp:sync status --id=1

# Output:
# Voyage #1: Safari Kenya
# ================================
# Laravel Status: active
# Laravel Updated: 2026-02-04 10:23:15
# WP Post ID: 1234
# WP Last Modified: 2026-02-04 09:15:00
# Last Synced: 2026-02-04 10:23:15
# Sync Hash: e3b0c44298fc1c149afbf4c...
# Conflict: No
```

---

## HTTP Endpoints

### POST /api/wp-sync/tour-updated

Endpoint webhook pour recevoir les notifications WordPress.

**Authentification**: HMAC-SHA256 signature (header `X-WP-Signature`)

**Payload**:
```json
{
    "wp_post_id": 1234,
    "action": "updated",
    "timestamp": 1707048195
}
```

**Response Success**:
```json
{
    "success": true,
    "result": {
        "action": "updated",
        "voyage_id": 1,
        "wp_post_id": 1234
    }
}
```

**Response Error**:
```json
{
    "success": false,
    "error": "Invalid signature"
}
```

**Exemple cURL**:
```bash
# Note: La signature doit être calculée
curl -X POST https://admin.ajinsafro.com/api/wp-sync/tour-updated \
  -H "Content-Type: application/json" \
  -H "X-WP-Signature: signature-hmac-sha256" \
  -d '{"wp_post_id": 1234, "action": "updated"}'
```

---

### GET /api/wp-sync/pull/{wpPostId}

Endpoint manuel pour tirer un tour WP vers Laravel.

**Authentification**: Token dans query string `?token=...`

**URL**:
```
GET /api/wp-sync/pull/1234?token=your-manual-sync-token
```

**Response**:
```json
{
    "success": true,
    "result": {
        "action": "updated",
        "voyage_id": 1,
        "wp_post_id": 1234
    }
}
```

**Exemple**:
```bash
curl "https://admin.ajinsafro.com/api/wp-sync/pull/1234?token=abc123def456"
```

---

## Configuration

### `config/wordpress.php`

#### Options Principales

```php
return [
    // Auto-sync activé
    'auto_sync_enabled' => env('WP_AUTO_SYNC_ENABLED', true),
    
    // Secrets
    'webhook_secret' => env('WP_WEBHOOK_SECRET'),
    'manual_sync_token' => env('WP_MANUAL_SYNC_TOKEN'),
    
    // Database
    'database_connection' => env('WP_DB_CONNECTION', 'wp'),
    'table_prefix' => env('WP_TABLE_PREFIX', 'cFdgeZ_'),
    
    // WP Site
    'site_url' => env('WP_SITE_URL', 'https://ajinsafro.com'),
    
    // Conflict resolution
    'conflict_resolution' => 'wp_wins', // WordPress gagne toujours
    
    // Options de sync
    'sync_featured_images' => true,
    'sync_gallery' => true,
    'sync_taxonomies' => true,
    'sync_program' => true,
    
    // Post type
    'tour_post_type' => 'st_tours',
    
    // Taxonomies à synchroniser
    'taxonomies' => [
        'st_tour_type',
        'durations',
        'language',
        'languages',
    ],
    
    // Metas à ignorer
    'ignored_meta_keys' => [
        'rank_math_internal_links_processed',
        '_edit_lock',
        '_edit_last',
    ],
];
```

#### Accès dans le code

```php
// Check si auto-sync activé
if (config('wordpress.auto_sync_enabled')) {
    // ...
}

// Récupérer webhook secret
$secret = config('wordpress.webhook_secret');

// Récupérer prefix
$prefix = config('wordpress.table_prefix'); // "cFdgeZ_"

// Liste taxonomies
$taxonomies = config('wordpress.taxonomies');
```

---

## Exemples d'Utilisation Complets

### Scénario 1: Création Voyage + Auto-Sync

```php
use App\Models\Voyage;

// Créer dans Laravel
$voyage = Voyage::create([
    'name' => 'Safari Kenya 7 Days',
    'slug' => 'safari-kenya-7-days',
    'description' => 'Amazing wildlife experience...',
    'min_people' => 2,
    'max_people' => 12,
    'is_featured' => true,
]);

// Observer auto-sync → WP post créé automatiquement
// Vérifier
echo $voyage->wp_post_id; // Ex: 1234
```

---

### Scénario 2: Import Bulk depuis WP

```php
use App\Services\WpTourSyncService;
use App\Repositories\WpRepository;
use App\Observers\VoyageObserver;

$syncService = app(WpTourSyncService::class);
$wpRepo = app(WpRepository::class);

// Désactiver auto-sync pour performance
VoyageObserver::withoutSync(function() use ($syncService, $wpRepo) {
    $wpTours = $wpRepo->findTours(['post_status' => 'publish'], 500);
    
    foreach ($wpTours as $wpTour) {
        try {
            $result = $syncService->upsertLaravelVoyageFromWp($wpTour['ID']);
            echo "✓ " . $result['action'] . " voyage #" . $result['voyage_id'] . "\n";
        } catch (\Exception $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
        }
    }
});
```

---

### Scénario 3: Gestion Conflit Manuel

```php
$voyage = Voyage::find(1);
$syncService = app(WpTourSyncService::class);

// Vérifier conflit
if ($syncService->hasWpConflict($voyage, $voyage->wp_post_id)) {
    // Option 1: Laisser WP gagner (pull)
    $syncService->upsertLaravelVoyageFromWp($voyage->wp_post_id);
    
    // Option 2: Forcer Laravel à gagner (force push)
    // $syncService->updateWpTourFromLaravel($voyage->id, force: true);
} else {
    // Pas de conflit, sync normal
    $syncService->updateWpTourFromLaravel($voyage->id);
}
```

---

### Scénario 4: Sync Programme Itinéraire

```php
use App\Models\TravelProgramDay;
use App\Models\TravelDayItem;

$voyage = Voyage::find(1);

// Créer programme dans Laravel
TravelProgramDay::create([
    'voyage_id' => $voyage->id,
    'day_number' => 1,
    'title' => 'Arrival in Nairobi',
    'description' => 'Meet and greet at airport...',
]);

TravelProgramDay::create([
    'voyage_id' => $voyage->id,
    'day_number' => 2,
    'title' => 'Game Drive in Maasai Mara',
    'description' => 'Full day safari...',
]);

// Sync vers WP (tours_program sera généré)
$syncService->updateWpTourFromLaravel($voyage->id);

// Vérifier dans WP
$wpProgram = $wpRepo->getPostMeta($voyage->wp_post_id, 'tours_program');
// Programme serialized envoyé à WP
```

---

## Debugging

### Activer Logs Détaillés

```php
// Dans config/logging.php, créer channel dédié
'channels' => [
    'wp-sync' => [
        'driver' => 'daily',
        'path' => storage_path('logs/wp-sync.log'),
        'level' => 'debug',
        'days' => 14,
    ],
],

// Utiliser dans code
Log::channel('wp-sync')->debug('Syncing voyage', ['id' => $voyageId]);
```

### Vérifier État Sync

```php
$voyage = Voyage::find(1);

// Dernière sync
echo $voyage->wp_synced_at; // 2026-02-04 10:23:15

// Hash snapshot
echo $voyage->wp_sync_hash; // e3b0c44...

// Cache modif WP
echo $voyage->wp_last_modified_gmt_cache; // 2026-02-04 09:15:00

// Comparer avec WP réel
$wpPost = $wpRepo->getPost($voyage->wp_post_id);
echo $wpPost['post_modified_gmt']; // Si différent → WP a changé
```

---

## Support

**Documentation**:
- `WP_BIDIRECTIONAL_SYNC_README.md` - Guide complet
- `WP_SYNC_QUICK_START.md` - Démarrage rapide
- `WP_SYNC_IMPLEMENTATION_GUIDE.md` - Intégration admin
- `WP_SYNC_FINAL_CHECKLIST.md` - Checklist validation

**Tests**:
```bash
php artisan test --filter WpSyncTest
```

**Logs**:
```bash
tail -f storage/logs/laravel.log | grep "WP sync"
```

---

*Dernière mise à jour: 2026-02-05*
