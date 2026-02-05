# Guide d'Implémentation - Synchronisation Bidirectionnelle WP/Laravel

## 📦 Fichiers Créés

### Laravel
```
app/
├── Services/
│   ├── WpTourSyncService.php         # Service principal de sync
│   └── WpToursProgramParser.php      # Parser tours_program
├── Repositories/
│   └── WpRepository.php               # Accès DB WordPress
├── Observers/
│   └── VoyageObserver.php             # Auto-sync sur save
├── Http/Controllers/Api/
│   └── WpSyncWebhookController.php    # Endpoint webhook WP
└── Console/Commands/
    └── WpSyncCommand.php              # Commandes artisan

config/
└── wordpress.php                      # Configuration WP sync

database/migrations/
└── 2026_02_05_*_add_wp_sync_fields_to_voyages_table.php

routes/
└── api.php                            # Routes webhook ajoutées
```

### WordPress
```
wp-content/plugins/
└── ajinsafro-sync-webhook/
    └── ajinsafro-sync-webhook.php    # Hook save_post → notify Laravel
```

---

## 🔧 Configuration Complète

### 1. Ajouter dans `.env`

```env
WP_AUTO_SYNC_ENABLED=true
WP_WEBHOOK_SECRET=a1b2c3d4e5f6... # 64 caractères aléatoires
WP_MANUAL_SYNC_TOKEN=votre-token-secret
WP_TABLE_PREFIX=cFdgeZ_
WP_SITE_URL=https://ajinsafro.com
WP_DB_CONNECTION=wp
```

### 2. Vérifier `config/database.php`

Vous devez avoir une connexion `wp` qui pointe vers la même base avec le bon préfixe :

```php
'wp' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => 'cFdgeZ_',
    'strict' => false,
    'engine' => null,
],
```

### 3. Enregistrer l'Observer

**Option A** : Via `EventServiceProvider`

```php
// app/Providers/EventServiceProvider.php

use App\Models\Voyage;
use App\Observers\VoyageObserver;

protected $observers = [
    Voyage::class => [VoyageObserver::class],
];
```

**Option B** : Via `AppServiceProvider`

```php
// app/Providers/AppServiceProvider.php

use App\Models\Voyage;
use App\Observers\VoyageObserver;

public function boot(): void
{
    Voyage::observe(VoyageObserver::class);
}
```

### 4. Lancer la Migration

```bash
php artisan migrate
```

Cela ajoute les colonnes nécessaires à la table `voyages`.

---

## 🎯 Intégration dans l'Admin Laravel

### Afficher le statut de sync dans la liste des voyages

Dans `resources/views/admin/circuits/voyages/index.blade.php` :

```blade
<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>WP Post ID</th>
            <th>Dernière Sync</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($voyages as $voyage)
        <tr>
            <td>{{ $voyage->name }}</td>
            <td>
                @if($voyage->wp_post_id)
                    <a href="{{ config('wordpress.site_url') }}/wp-admin/post.php?post={{ $voyage->wp_post_id }}&action=edit" 
                       target="_blank" 
                       class="badge bg-success">
                        #{{ $voyage->wp_post_id }}
                    </a>
                @else
                    <span class="badge bg-secondary">Non lié</span>
                @endif
            </td>
            <td>
                @if($voyage->wp_synced_at)
                    <small>{{ $voyage->wp_synced_at->diffForHumans() }}</small>
                @else
                    <span class="text-muted">Jamais</span>
                @endif
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="syncToWp({{ $voyage->id }})">
                    Push vers WP
                </button>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

<script>
function syncToWp(voyageId) {
    if (!confirm('Synchroniser ce voyage vers WordPress ?')) return;
    
    fetch(`/admin/voyages/${voyageId}/sync-wp`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ Synchronisé avec WP post #' + data.wp_post_id);
            location.reload();
        } else {
            alert('✗ Erreur: ' + data.error);
        }
    });
}
</script>
```

### Ajouter la route de sync manuelle

Dans `routes/web.php` (admin) :

```php
Route::post('/admin/voyages/{voyage}/sync-wp', function(Voyage $voyage) {
    $sync = app(\App\Services\WpTourSyncService::class);
    
    try {
        if ($voyage->wp_post_id) {
            $result = $sync->updateWpTourFromLaravel($voyage->id);
        } else {
            $result = $sync->createWpTourFromLaravel($voyage->id);
        }
        
        return response()->json($result);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
})->middleware('auth');
```

### Bouton "Pull depuis WP" dans le formulaire d'édition

Dans `resources/views/admin/circuits/voyages/edit.blade.php` :

```blade
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h5>Synchronisation WordPress</h5>
        @if($voyage->wp_post_id)
            <a href="{{ config('wordpress.site_url') }}/wp-admin/post.php?post={{ $voyage->wp_post_id }}&action=edit" 
               target="_blank" 
               class="btn btn-sm btn-info">
                Voir dans WP
            </a>
        @endif
    </div>
    <div class="card-body">
        @if($voyage->wp_post_id)
            <div class="row">
                <div class="col-md-6">
                    <p><strong>WP Post ID:</strong> {{ $voyage->wp_post_id }}</p>
                    <p><strong>Dernière sync:</strong> 
                        @if($voyage->wp_synced_at)
                            {{ $voyage->wp_synced_at->format('d/m/Y H:i') }}
                            <small class="text-muted">({{ $voyage->wp_synced_at->diffForHumans() }})</small>
                        @else
                            <span class="text-muted">Jamais</span>
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-primary" onclick="pushToWp()">
                        <i class="bx bx-upload"></i> Push vers WP
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="pullFromWp()">
                        <i class="bx bx-download"></i> Pull depuis WP
                    </button>
                </div>
            </div>
        @else
            <p class="text-warning">Ce voyage n'est pas encore lié à WordPress.</p>
            <button type="button" class="btn btn-success" onclick="createInWp()">
                <i class="bx bx-plus"></i> Créer dans WordPress
            </button>
        @endif
    </div>
</div>

<script>
function pushToWp() {
    if (!confirm('Pousser ce voyage vers WordPress ? Cela écrasera les données WP.')) return;
    
    fetch(`/admin/voyages/{{ $voyage->id }}/sync-wp`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('✓ Synchronisé !');
            location.reload();
        } else {
            alert('✗ Erreur: ' + data.error);
        }
    });
}

function pullFromWp() {
    if (!confirm('Importer depuis WordPress ? Cela écrasera les modifications locales.')) return;
    
    fetch(`/api/wp-sync/pull/{{ $voyage->wp_post_id }}?token={{ config('wordpress.manual_sync_token') }}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('✓ Importé depuis WP !');
                location.reload();
            } else {
                alert('✗ Erreur: ' + data.error);
            }
        });
}

function createInWp() {
    pushToWp(); // Same as push for new
}
</script>
```

---

## 🧪 Tests à Faire Après Installation

### Test 1 : Création automatique

```bash
# 1. Créer un voyage dans Laravel admin
# 2. Vérifier dans WP admin que le tour apparaît
# 3. Vérifier que voyages.wp_post_id est renseigné
```

### Test 2 : Modification Laravel → WP

```bash
# 1. Modifier le nom d'un voyage dans Laravel
# 2. Vérifier que le titre WP est mis à jour
```

### Test 3 : Modification WP → Laravel

```bash
# 1. Modifier le titre dans WP admin
# 2. Sauvegarder
# 3. Vérifier dans Laravel que voyages.name est mis à jour
# 4. Vérifier les logs: storage/logs/laravel.log
```

### Test 4 : Conflit (WP gagne)

```bash
# 1. Modifier dans WP (titre = "WP Title")
# 2. Modifier dans Laravel (titre = "Laravel Title")
# 3. Sauvegarder Laravel
# 4. Observer doit détecter le conflit et pull depuis WP
# 5. Vérifier que le titre final = "WP Title"
```

### Test 5 : Programme

```bash
# 1. Créer des program days dans Laravel
# 2. Sync vers WP
# 3. Vérifier dans WP admin que tours_program est renseigné
# 4. Modifier tours_program dans WP
# 5. Vérifier que travel_program_days est mis à jour dans Laravel
```

---

## 🎨 Améliorations Futures

### Dashboard de Synchronisation

Créer une page dédiée `/admin/wp-sync` pour monitorer :
- Nombre de tours synchronisés
- Dernières syncs
- Erreurs de sync
- Bouton "Sync All" manuel
- Graphique d'activité

### Queue Jobs (Async)

Pour améliorer les performances, mettre la sync en queue :

```php
// Dans VoyageObserver
dispatch(new SyncVoyageToWpJob($voyage->id));
```

### Webhooks Additionnels

- WP tour deleted → Soft delete Laravel
- WP attachment updated → Re-cache images
- Taxonomies changed → Pull taxonomies

### Rollback en cas d'erreur

Stocker snapshots avant sync pour pouvoir rollback :

```php
// Avant sync
$backup = [
    'voyage' => $voyage->toArray(),
    'program_days' => $voyage->programDays->toArray(),
];

// Si échec
if ($syncFailed) {
    $voyage->update($backup['voyage']);
    // Restore program...
}
```

---

## 🔐 Sécurité en Production

### 1. Générer des secrets forts

```bash
# Secret HMAC (64 caractères hex)
php -r "echo bin2hex(random_bytes(32));"

# Token manual sync (32 caractères hex)
php -r "echo bin2hex(random_bytes(16));"
```

### 2. Restreindre l'accès API

```php
// Dans routes/api.php, ajouter middleware IP whitelist
Route::prefix('wp-sync')->middleware('ip.whitelist:wp')->group(function () {
    // ...
});

// Créer middleware
php artisan make:middleware IpWhitelist
```

### 3. Rate Limiting

```php
Route::post('/wp-sync/tour-updated', [WpSyncWebhookController::class, 'tourUpdated'])
    ->middleware('throttle:60,1'); // 60 requêtes/minute max
```

### 4. Logs de Sécurité

Monitorer les tentatives de sync non autorisées :

```php
// Dans WpSyncWebhookController
if (!$this->verifySignature($request)) {
    Log::channel('security')->warning('Invalid WP sync signature', [
        'ip' => $request->ip(),
        'user_agent' => $request->userAgent(),
    ]);
    return response()->json(['error' => 'Invalid signature'], 403);
}
```

---

## 📊 Monitoring

### Créer un Dashboard Sync

```php
// Route
Route::get('/admin/wp-sync-dashboard', [WpSyncDashboardController::class, 'index']);

// Controller
public function index()
{
    $stats = [
        'total_voyages' => Voyage::count(),
        'linked_to_wp' => Voyage::whereNotNull('wp_post_id')->count(),
        'synced_today' => Voyage::whereDate('wp_synced_at', today())->count(),
        'never_synced' => Voyage::whereNull('wp_synced_at')->count(),
        'conflicts_detected' => 0, // À implémenter
    ];
    
    $recent_syncs = Voyage::whereNotNull('wp_synced_at')
        ->orderBy('wp_synced_at', 'desc')
        ->limit(20)
        ->get();
    
    return view('admin.wp-sync-dashboard', compact('stats', 'recent_syncs'));
}
```

---

## 🧩 Intégration avec Controller Voyage Existant

### Dans `VoyageController@store`

```php
public function store(Request $request)
{
    $validated = $request->validate([...]);
    
    $voyage = Voyage::create($validated);
    
    // L'observer se déclenche automatiquement et crée dans WP
    // Pas besoin d'appel manuel
    
    return redirect()->route('admin.voyages.edit', $voyage)
        ->with('success', 'Voyage créé et synchronisé avec WordPress');
}
```

### Dans `VoyageController@update`

```php
public function update(Request $request, Voyage $voyage)
{
    $validated = $request->validate([...]);
    
    $voyage->update($validated);
    
    // L'observer se déclenche automatiquement et met à jour WP
    
    // Vérifier si sync réussie
    $voyage->refresh();
    
    if ($voyage->wp_synced_at && $voyage->wp_synced_at->isToday()) {
        $message = 'Voyage mis à jour et synchronisé avec WP';
    } else {
        $message = 'Voyage mis à jour (sync WP en attente)';
    }
    
    return redirect()->back()->with('success', $message);
}
```

### Afficher alerte si conflit détecté

```php
public function edit(Voyage $voyage, WpTourSyncService $sync)
{
    $conflict = false;
    
    if ($voyage->wp_post_id) {
        try {
            $wpPost = app(\App\Repositories\WpRepository::class)->getPost($voyage->wp_post_id);
            
            if ($wpPost && $voyage->wp_last_modified_gmt_cache) {
                $wpModified = \Carbon\Carbon::parse($wpPost['post_modified_gmt']);
                $cached = \Carbon\Carbon::parse($voyage->wp_last_modified_gmt_cache);
                
                if ($wpModified->greaterThan($cached)) {
                    $conflict = true;
                }
            }
        } catch (\Exception $e) {
            // Ignore
        }
    }
    
    return view('admin.circuits.voyages.edit', compact('voyage', 'conflict'));
}
```

Dans la vue :

```blade
@if($conflict)
<div class="alert alert-warning">
    <i class="bx bx-error"></i>
    <strong>Attention :</strong> Ce voyage a été modifié dans WordPress après la dernière synchronisation.
    <button class="btn btn-sm btn-warning" onclick="pullFromWp()">
        Importer les modifications WP
    </button>
</div>
@endif
```

---

## 🔄 Workflow Recommandé

### Scénario 1 : Nouveau Tour

1. Créer dans **Laravel Admin** (interface riche)
2. Auto-sync vers WP (Observer)
3. Le tour apparaît dans WP avec toutes les metas
4. Modification possible dans les 2 interfaces

### Scénario 2 : Import Tours Existants WP

```bash
# Une seule fois : importer tous les tours WP
php artisan wp:sync pull-all
```

### Scénario 3 : Édition Quotidienne

- **Principalement dans Laravel** (interface plus riche)
- Auto-sync vers WP à chaque save
- Si modification WP (client/externe) → Pull automatique via webhook

### Scénario 4 : Maintenance / Batch

```bash
# Désactiver auto-sync
WP_AUTO_SYNC_ENABLED=false

# Faire les modifs batch
# ...

# Réactiver
WP_AUTO_SYNC_ENABLED=true

# Sync manuellement
php artisan wp:sync push --id=...
```

---

## 🚨 Cas d'Erreur Courants

### Erreur : "Table cFdgeZ_posts doesn't exist"

➡️ Vérifier `config/database.php` connexion `wp` et le prefix.

### Erreur : "Invalid signature"

➡️ Vérifier que `WP_WEBHOOK_SECRET` est identique dans :
- Laravel `.env`
- WP plugin settings

### Erreur : "Voyage not found"

➡️ Le tour a peut-être été supprimé. Vérifier :

```bash
php artisan wp:sync status --id=123
```

### Auto-sync ne se déclenche pas

➡️ Vérifier que l'observer est enregistré :

```bash
php artisan tinker
>>> Voyage::getObservableEvents()
>>> Voyage::observe(App\Observers\VoyageObserver::class);
```

---

✅ **Système complet et documenté !**
