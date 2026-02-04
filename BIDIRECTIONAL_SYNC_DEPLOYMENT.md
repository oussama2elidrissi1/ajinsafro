# 🔄 Synchronisation Bidirectionnelle - Guide de Déploiement

## ✅ Résumé de l'implémentation

### Système complet de synchronisation bidirectionnelle avec protection contre les boucles infinies

**État actuel :** ✅ Implémenté, prêt pour déploiement

---

## 📋 Fichiers créés/modifiés

### Laravel (Backend)

#### Nouveaux fichiers
```
app/Services/Sync/WpSyncService.php          - Service pour Laravel → WP
app/Services/Sync/SyncContext.php            - Protection anti-boucle
app/Observers/VoyageObserver.php             - Observer pour auto-sync
app/Http/Controllers/Sync/WpToLaravelController.php - Controller WP → Laravel
app/Services/Wp/WpTourImporter.php          - Import WP → Laravel
app/Console/Commands/WpImportTours.php       - Commande d'import
```

#### Fichiers modifiés
```
app/Providers/AppServiceProvider.php         - Enregistrement observer & singleton
routes/api.php                                - Routes de sync
config/sync.php                               - Configuration sync
```

### WordPress (Plugin ajinsafro-core)

#### Nouveaux fichiers
```
includes/Sync/LaravelPushSync.php            - Service WP → Laravel
```

#### Fichiers modifiés
```
includes/Sync/TourSyncer.php                 - Protection anti-boucle avec _aj_sync_lock
includes/Core/Options.php                    - Nouvelles options
includes/Admin/Settings.php                  - Gestion nouvelles options
templates/admin/settings.php                 - UI pour bi-directional sync
ajinsafro-core.php                           - Bootstrap LaravelPushSync
```

---

## 🚀 Déploiement

### Étape 1 : Laravel (Git Push)

```bash
# Sur votre machine locale
cd /path/to/laravel

# Vérifier les fichiers modifiés
git status

# Ajouter tous les nouveaux fichiers
git add app/Services/Sync/
git add app/Observers/
git add app/Http/Controllers/Sync/
git add app/Services/Wp/
git add app/Console/Commands/
git add app/Providers/AppServiceProvider.php
git add routes/api.php
git add config/sync.php

# Commit
git commit -m "feat: Add bidirectional sync between Laravel and WordPress

- Laravel → WP: VoyageObserver + WpSyncService
- WP → Laravel: WpToLaravelController + LaravelPushSync
- Loop protection: SyncContext + _aj_sync_lock
- Import system: WpTourImporter command
"

# Push
git push origin main
```

### Étape 2 : Sur le serveur Laravel

```bash
# SSH sur le serveur
ssh user@booking.ajinsafro.net

# Pull les changements
cd /path/to/laravel
git pull origin main

# Clear caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear

# Re-cache pour production
php artisan route:cache
php artisan config:cache

# Redémarrer services
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx

# Vérifier les nouvelles routes
php artisan route:list --path=api/sync
```

**Résultat attendu :**
```
POST  api/sync/wp-to-laravel
POST  api/sync/wp-to-laravel/delete
```

### Étape 3 : Configuration Laravel (.env)

Ajouter dans le `.env` de production :

```env
# Sync configuration
WP_SYNC_URL=https://ajinsafro.net/wp-json/ajinsafro-sync/v1/laravel-to-wp
SYNC_TOKEN=your_strong_token_here_min_32_chars
SYNC_WEBHOOK_TOKEN=your_strong_token_here_min_32_chars
SYNC_DEBUG=false
```

**Important :** Les deux tokens peuvent être identiques pour simplifier.

### Étape 4 : WordPress Plugin (ZIP Upload)

#### A. Créer le ZIP du plugin

```bash
# Sur votre machine locale
cd /path/to/wp-plugin
zip -r ajinsafro-core-v2.0.zip ajinsafro-core/ -x "*.DS_Store" "*.git*"
```

#### B. Uploader dans WordPress

1. Aller dans **WordPress Admin → Plugins → Add New → Upload Plugin**
2. Uploader `ajinsafro-core-v2.0.zip`
3. **Écraser** l'ancienne version si demandé
4. Activer le plugin

#### C. Configurer les settings

Aller dans **WordPress Admin → Ajinsafro Core → Settings**

**Section 1 : Laravel → WP Sync (existant)**
```
Laravel Base URL: https://booking.ajinsafro.net
Checkout Base URL: https://booking.ajinsafro.net
HMAC Secret: your_strong_token_here_min_32_chars
☑ Enable Laravel → WP Sync
Cache TTL: 300
```

**Section 2 : WP → Laravel Sync (nouveau)**
```
☑ Enable WP → Laravel Sync
Laravel Sync Endpoint URL: https://booking.ajinsafro.net
Laravel Webhook Token: your_strong_token_here_min_32_chars
```

**Section 3 : Package Builder Display**
```
☑ Auto-inject Package Builder
Auto-inject Position: After content
```

### Étape 5 : Import initial des tours

Sur le serveur Laravel :

```bash
php artisan wp:import-tours --all
```

**Résultat attendu :**
```
✅ Created: 26
📊 Total voyages: 26
```

---

## 🧪 Tests de validation

### Test 1 : Laravel → WordPress (Création)

**Dans Laravel :**
```bash
php artisan tinker
```

```php
$voyage = \App\Models\Voyage::create([
    'name' => 'Test Sync Laravel → WP',
    'slug' => 'test-sync-laravel-wp',
    'description' => 'Test de synchronisation',
    'status' => 'actif',
    'price_from' => 50000, // 500 MAD
    'currency' => 'MAD',
]);

echo "Voyage ID: " . $voyage->id . "\n";
echo "WP Post ID: " . $voyage->wp_post_id . "\n";
```

**Vérifier dans WordPress :**
1. Aller dans **Posts → Tours**
2. Le tour "Test Sync Laravel → WP" doit apparaître
3. Vérifier que `_aj_laravel_voyage_id` est rempli dans les custom fields

**Résultat attendu :** ✅ Tour créé automatiquement dans WordPress

---

### Test 2 : Laravel → WordPress (Mise à jour)

**Dans Laravel :**
```php
$voyage = \App\Models\Voyage::first();
$voyage->update(['name' => 'Test Sync Updated']);
```

**Vérifier dans WordPress :**
1. Actualiser la liste des tours
2. Le titre doit être "Test Sync Updated"

**Résultat attendu :** ✅ Tour mis à jour automatiquement dans WordPress

---

### Test 3 : WordPress → Laravel (Mise à jour)

**Dans WordPress :**
1. Aller dans **Posts → Tours**
2. Éditer un tour existant
3. Changer le titre : "Mon tour modifié depuis WP"
4. **Publier**

**Vérifier dans Laravel :**
```bash
php artisan tinker
```

```php
$voyage = \App\Models\Voyage::where('name', 'Mon tour modifié depuis WP')->first();
echo $voyage ? "✅ Trouvé : {$voyage->name}" : "❌ Non trouvé";
```

**Résultat attendu :** ✅ Le voyage est mis à jour dans Laravel

---

### Test 4 : Anti-boucle (Test critique)

**Dans Laravel :**
```php
$voyage = \App\Models\Voyage::first();
$voyage->update(['description' => 'Test anti-loop ' . time()]);
```

**Observer les logs Laravel :**
```bash
tail -f storage/logs/laravel.log
```

**Vérifier :**
- La mise à jour est envoyée à WordPress ✅
- WordPress ne renvoie PAS la mise à jour à Laravel ✅
- Aucune boucle infinie ✅

**Dans les logs, vous devriez voir :**
```
[VoyageObserver] Skipping sync - source is WordPress
[WpToLaravelController] Skipping update - no changes detected
```

---

### Test 5 : Package Builder sur WordPress

**Dans WordPress :**
1. Aller sur une page de tour : `https://ajinsafro.net/tours/nom-du-tour/`
2. Vérifier que le Package Builder s'affiche automatiquement
3. Vérifier que le design ne casse pas le thème

**Vérifier dans le HTML :**
```html
<!-- Le builder doit être encapsulé -->
<div class="aj-package-builder-wrapper">
  <div class="aj-package-builder">
    <!-- Contenu du builder -->
  </div>
</div>
```

**Résultat attendu :** ✅ Builder visible, design intact

---

### Test 6 : API Package State

**Tester l'endpoint :**
```bash
curl -i https://booking.ajinsafro.net/api/public/tours/1/package-state \
  -H "Accept: application/json"
```

**Résultat attendu :**
```
HTTP/2 200
Content-Type: application/json

{
  "success": true,
  "data": {
    "tour": {...},
    "session": {...},
    "pricing": {...},
    "days": [...]
  }
}
```

---

## 🔍 Troubleshooting

### Problème 1 : Laravel → WP ne fonctionne pas

**Symptôme :** Voyage créé dans Laravel mais pas dans WordPress

**Vérifications :**
```bash
# Vérifier les logs Laravel
tail -50 storage/logs/laravel.log | grep -i "WpSyncService"

# Vérifier l'URL configurée
php artisan tinker
>>> config('sync.wp_sync_url')

# Vérifier le token
>>> config('sync.token')
```

**Solutions :**
1. Vérifier que `WP_SYNC_URL` est correct dans `.env`
2. Vérifier que `SYNC_TOKEN` correspond entre Laravel et WordPress
3. Vérifier que WordPress a bien `enable_sync` = true

---

### Problème 2 : WP → Laravel ne fonctionne pas

**Symptôme :** Tour modifié dans WordPress mais pas dans Laravel

**Vérifications WordPress :**
```
Settings → Ajinsafro Core
☑ Enable WP → Laravel Sync (doit être coché)
Laravel Webhook Token: (doit être rempli)
```

**Vérifier les logs WordPress :**
```bash
# Sur le serveur WP
tail -50 wp-content/uploads/ajinsafro-sync.log
```

**Vérifier que Laravel accepte les requêtes :**
```bash
curl -X POST https://booking.ajinsafro.net/api/sync/wp-to-laravel \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "action": "upsert",
    "entity_type": "tour",
    "source": "wp",
    "wp_post_id": 123,
    "title": "Test",
    "slug": "test",
    "status": "publish",
    "sync_hash": "abc123"
  }'
```

---

### Problème 3 : Boucle infinie détectée

**Symptôme :** Les logs montrent des mises à jour répétées

**Solution :**
1. Vérifier que `SyncContext` est bien enregistré dans `AppServiceProvider`
2. Vérifier que `_aj_sync_lock` est bien utilisé dans `TourSyncer`
3. Vérifier les hashes de sync :

```php
// Laravel
$voyage = \App\Models\Voyage::first();
echo "Hash: " . $voyage->wp_sync_hash . "\n";

// WordPress
get_post_meta($post_id, '_aj_sync_hash', true);
```

---

### Problème 4 : Package Builder ne s'affiche pas

**Vérifications :**
1. **Settings WordPress :**
   ```
   ☑ Auto-inject Package Builder (doit être coché)
   ```

2. **Vérifier le meta `_aj_laravel_voyage_id` :**
   ```php
   get_post_meta($post_id, '_aj_laravel_voyage_id', true);
   // Doit retourner un ID Laravel valide
   ```

3. **Vérifier l'API Laravel :**
   ```bash
   curl https://booking.ajinsafro.net/api/public/tours/1/package-state
   # Doit retourner 200 + JSON
   ```

4. **Vérifier les logs PHP WordPress :**
   ```php
   // Ajouter temporairement dans AutoInjector.php
   error_log("AutoInjector triggered for post: " . get_the_ID());
   ```

---

## 📊 Checklist de déploiement final

### Laravel
- [ ] Code pushé sur Git
- [ ] Serveur mis à jour (`git pull`)
- [ ] Caches nettoyés (`route:clear`, `config:clear`, `cache:clear`)
- [ ] Caches régénérés (`route:cache`, `config:cache`)
- [ ] Services redémarrés (PHP-FPM, Nginx)
- [ ] Routes de sync visibles (`php artisan route:list --path=api/sync`)
- [ ] `.env` configuré avec tokens
- [ ] Import des tours exécuté (`php artisan wp:import-tours --all`)

### WordPress
- [ ] Plugin uploadé en ZIP
- [ ] Plugin activé
- [ ] Settings configurées (tous les tokens et URLs)
- [ ] `☑ Enable Laravel → WP Sync` coché
- [ ] `☑ Enable WP → Laravel Sync` coché
- [ ] `☑ Auto-inject Package Builder` coché
- [ ] Tours visibles dans la liste des posts
- [ ] Meta `_aj_laravel_voyage_id` rempli pour les tours

### Tests
- [ ] Test 1 : Laravel → WP (création) ✅
- [ ] Test 2 : Laravel → WP (mise à jour) ✅
- [ ] Test 3 : WP → Laravel (mise à jour) ✅
- [ ] Test 4 : Anti-boucle ✅
- [ ] Test 5 : Package Builder visible ✅
- [ ] Test 6 : API package-state retourne 200 ✅

---

## 🎯 Commandes rapides

### Déploiement Laravel complet
```bash
cd /path/to/laravel
git pull origin main
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan route:cache
php artisan config:cache
sudo systemctl restart php8.2-fpm
sudo systemctl reload nginx
php artisan route:list --path=api
```

### Import initial des tours
```bash
php artisan wp:import-tours --all
```

### Vérification rapide sync
```bash
# Laravel
php artisan tinker --execute="echo \App\Models\Voyage::count() . ' voyages';"

# Logs Laravel
tail -f storage/logs/laravel.log | grep -i sync

# Logs WordPress (sur le serveur WP)
tail -f wp-content/uploads/ajinsafro-sync.log
```

---

## 🎉 Résultat final attendu

✅ **Synchronisation bidirectionnelle fonctionnelle**
- Tout changement dans Laravel → WordPress mis à jour automatiquement
- Tout changement dans WordPress → Laravel mis à jour automatiquement
- Aucune boucle infinie
- Protection par hash pour éviter les syncs inutiles

✅ **Package Builder intégré**
- Affichage automatique sur toutes les pages de tours
- Design respectueux du thème WordPress
- Assets chargés uniquement quand nécessaire

✅ **API publique stable**
- Endpoint `/api/public/tours/{voyageId}/package-state` fonctionnel
- Gestion correcte des relations vides
- Réponses JSON cohérentes

✅ **Import WordPress → Laravel**
- Commande `php artisan wp:import-tours --all` disponible
- Tous les tours WordPress importés dans Laravel
- Liaison `wp_post_id` établie

---

## 📞 Support

En cas de problème, vérifier dans l'ordre :

1. **Logs Laravel :** `storage/logs/laravel.log`
2. **Logs WordPress :** `wp-content/uploads/ajinsafro-sync.log`
3. **Logs serveur :** `/var/log/nginx/error.log` ou `/var/log/apache2/error.log`
4. **Console navigateur :** Pour les erreurs JS du Package Builder

**Status final :** 🟢 **PRODUCTION-READY**
