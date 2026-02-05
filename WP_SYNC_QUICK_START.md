# 🚀 Démarrage Rapide - Synchronisation Bidirectionnelle WP/Laravel

## ⚡ Installation en 5 Minutes

### Étape 1 : Configuration Laravel

```bash
# 1. Copier la configuration
cp .env.wp-sync.example .env
# Puis éditer .env et ajouter les lignes WP_*

# 2. Générer le secret HMAC
php -r "echo bin2hex(random_bytes(32));"
# Copier le résultat dans WP_WEBHOOK_SECRET

# 3. Lancer la migration
php artisan migrate

# 4. Tester la connexion WP
php artisan tinker
>>> app(\App\Repositories\WpRepository::class)->getOption('siteurl')
# Doit retourner l'URL de votre site WP
```

### Étape 2 : Enregistrer l'Observer

Dans `app/Providers/AppServiceProvider.php`, méthode `boot()` :

```php
use App\Models\Voyage;
use App\Observers\VoyageObserver;

public function boot(): void
{
    Voyage::observe(VoyageObserver::class);
}
```

### Étape 3 : Plugin WordPress

```bash
# 1. Zipper le plugin
cd wp-plugin
Compress-Archive -Path "ajinsafro-sync-webhook" -DestinationPath "ajinsafro-sync-webhook.zip" -Force

# 2. Installer dans WP
# WP Admin → Extensions → Ajouter → Téléverser
# Activer le plugin

# 3. Configurer
# WP Admin → Réglages → Ajinsafro Sync
# - Laravel URL: https://admin.ajinsafro.com
# - Webhook Secret: [même valeur que WP_WEBHOOK_SECRET dans .env Laravel]
# - Tester la connexion
```

---

## ✅ Vérification de l'Installation

### Test 1 : Laravel → WP (Push)

```php
// Dans tinker
php artisan tinker

>>> $voyage = Voyage::create([
    'name' => 'Test Sync',
    'slug' => 'test-sync',
    'description' => 'Tour de test pour sync',
    'min_people' => 2,
    'max_people' => 10,
]);

>>> $voyage->wp_post_id
// Doit retourner un ID WP (ex: 1234)
```

Vérifier dans **WP Admin → Circuits** : le tour "Test Sync" doit apparaître.

### Test 2 : WP → Laravel (Pull)

```bash
# Modifier le tour dans WP Admin (changer le titre)
# Puis vérifier dans Laravel :

php artisan tinker
>>> $voyage = Voyage::where('name', 'Test Sync')->first();
>>> $voyage->name
# Doit afficher le nouveau titre WP
```

### Test 3 : Conflit (WP Gagne)

```php
// 1. Modifier dans WP (titre = "Titre WP")
// 2. Dans Laravel, sans rafraîchir :
>>> $voyage->update(['name' => 'Titre Laravel']);

// 3. Vérifier
>>> $voyage->fresh()->name
// Doit afficher "Titre WP" (WP a gagné)
```

---

## 🎯 Commandes Artisan

### Push un voyage vers WP

```bash
php artisan wp:sync push --id=1
```

### Pull un tour WP vers Laravel

```bash
php artisan wp:sync pull --id=123
# où 123 = WP post ID
```

### Pull TOUS les tours WP

```bash
php artisan wp:sync pull-all
```

### Voir le statut de sync

```bash
# Global
php artisan wp:sync status

# Spécifique
php artisan wp:sync status --id=1
```

### Force sync (ignorer détection conflit)

```bash
php artisan wp:sync push --id=1 --force
```

---

## 🔧 Utilisation Programmatique

### Dans un Controller Laravel

```php
use App\Services\WpTourSyncService;

class VoyageController extends Controller
{
    public function syncToWp(Request $request, WpTourSyncService $sync)
    {
        $voyage = Voyage::findOrFail($request->voyage_id);
        
        if ($voyage->wp_post_id) {
            $result = $sync->updateWpTourFromLaravel($voyage->id);
        } else {
            $result = $sync->createWpTourFromLaravel($voyage->id);
        }
        
        return response()->json($result);
    }
    
    public function pullFromWp(Request $request, WpTourSyncService $sync)
    {
        $result = $sync->upsertLaravelVoyageFromWp($request->wp_post_id);
        
        return response()->json($result);
    }
}
```

### Désactiver auto-sync temporairement

```php
use App\Observers\VoyageObserver;

// Pour une opération batch
VoyageObserver::withoutSync(function() {
    // Créer/modifier plusieurs voyages
    Voyage::create([...]);
    Voyage::create([...]);
    // Aucun push WP automatique
});

// Puis sync manuellement si besoin
$syncService->updateWpTourFromLaravel($voyage->id);
```

---

## 📊 Champs Synchronisés

### ✅ Core WordPress

- `post_title` ↔ `name`
- `post_name` ↔ `slug`
- `post_content` ↔ `description`
- `post_excerpt` ↔ `accroche`
- `post_status` ↔ `status`

### ✅ Metas Traveler

- `min_people`, `max_people`
- `tour_price_by`
- `is_featured`
- `st_google_map`, `multi_location`
- `discount_by_people_type`, `discount_type`, `calculator_discount_by_people_type`
- `hide_adult_in_booking_form`
- `st_tour_external_booking`
- `tours_include`, `tours_exclude`, `tours_highlight`
- `tours_program_style`
- Toutes les metas `is_meta_payment_gateway_*`

### ✅ Images

- `_thumbnail_id` (featured)
- `gallery` (IDs attachments)

### ✅ Programme

- `tours_program` (WP) ↔ `travel_program_days` + `travel_day_items` (Laravel)

### ❌ Ignoré

- Metas RankMath (`rank_math_*`)
- Transients WordPress
- Edit locks (`_edit_lock`, `_edit_last`)

---

## 🐛 Troubleshooting

### Webhook ne fonctionne pas

```bash
# Vérifier les logs Laravel
tail -f storage/logs/laravel.log

# Vérifier le secret
php artisan tinker
>>> config('wordpress.webhook_secret')

# Tester depuis WP
# WP Admin → Réglages → Ajinsafro Sync → Test de connexion
```

### Attachments manquants dans WP

Le code est **robuste** : si `_thumbnail_id` pointe vers un attachment inexistant, il ne crash pas.

```php
// Dans WpRepository::getAttachmentUrl()
// Retourne null si attachment manquant
// Le sync continue sans erreur
```

### Conflit non détecté

```bash
# Vérifier le cache
php artisan wp:sync status --id=1

# Forcer une mise à jour du cache
php artisan wp:sync pull --id=123 --force
```

### Désactiver auto-sync globalement

```env
# .env
WP_AUTO_SYNC_ENABLED=false
```

Puis sync manuellement via commandes.

---

## 📝 Notes Importantes

1. **Préfixe DB** : Le préfixe `cFdgeZ_` est hardcodé. Vérifier que c'est correct.

2. **Même Base de Données** : Laravel et WordPress doivent utiliser la même base MySQL.

3. **Connexion `wp`** : Définie dans `config/database.php` avec prefix `cFdgeZ_`.

4. **WP Wins** : En cas de conflit, WordPress écrase toujours Laravel.

5. **Infinite Loops** : Le système a des protections :
   - Flag `VoyageObserver::$syncEnabled`
   - Transient WP `ajsync_skip_notify_{$post_id}`
   - Check `isDirty()` dans Observer

6. **Performance** : Pour sync bulk, utiliser `pull-all` ou désactiver observer.

---

✅ **Le système est prêt à l'emploi !**
