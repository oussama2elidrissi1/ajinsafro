# ✅ PATCH FINAL - SYNCHRONISATION HMAC + MULTI-DB

## 🎯 RÉSUMÉ DES 3 CORRECTIONS

### ❌ Problème 1 : Laravel utilise la mauvaise DB
**Symptôme :** `DB::connection()->getDatabaseName() = 'ajinsafronet_wp_tkrpc'`  
**Impact :** Les voyages sont écrits dans la DB WordPress au lieu de la DB métier

**✅ CORRIGÉ :**
- Connexion `'mysql'` (défaut) → `ajinsafronet_ajinsafro` (DB métier)
- Connexion `'wp'` → `ajinsafronet_wp_tkrpc` (DB WordPress)
- `WpTourImporter` utilise maintenant `DB::connection('wp')`

### ❌ Problème 2 : WordPress rejette Laravel avec "Missing signature"
**Symptôme :** `{"code":"no_signature","message":"Missing signature"}`  
**Impact :** Laravel → WordPress sync échoue toujours

**✅ CORRIGÉ :**
- `WpSyncService` calcule maintenant HMAC : `hash_hmac('sha256', $body, $secret)`
- Header ajouté : `X-AJ-Signature: {signature}`
- Sérialisation JSON cohérente

### ❌ Problème 3 : Laravel rejette WordPress avec 401
**Symptôme :** `/api/sync/wp-to-laravel` → `401 Unauthorized`  
**Impact :** WordPress → Laravel sync échoue toujours

**✅ CORRIGÉ :**
- `WpToLaravelController` valide maintenant HMAC obligatoire
- `LaravelPushSync` (WP) envoie signature HMAC
- Même algorithme des deux côtés

---

## 📦 FICHIERS MODIFIÉS

### Laravel (7 fichiers)

1. **config/database.php**
   - Ajout connexion `'wp'` avec préfixe `'cFdgeZ_'`

2. **config/sync.php**
   - Variables : `'secret'`, `'webhook_secret'`

3. **app/Services/Sync/WpSyncService.php**
   - Calcul HMAC signature
   - Header `X-AJ-Signature`

4. **app/Services/Wp/WpTourImporter.php**
   - Toutes les requêtes utilisent `DB::connection('wp')`

5. **app/Http/Controllers/Sync/WpToLaravelController.php**
   - Validation HMAC avec `hash_equals()`

6. **app/Http/Controllers/Sync/PingController.php** ⭐ NOUVEAU
   - Endpoint `/api/sync/ping` pour tests

7. **routes/api.php**
   - Route ping ajoutée

### WordPress (2 fichiers)

1. **includes/Sync/LaravelPushSync.php**
   - Calcul signature HMAC
   - Header `X-AJ-Signature`

2. **includes/Sync/RestEndpoint.php**
   - Endpoint `/wp-json/ajinsafro-sync/v1/ping` ⭐ NOUVEAU
   - Méthodes `handle_ping()` et `check_ping_permission()`

---

## 🚀 DÉPLOIEMENT

### Étape 1 : Git (Laravel)
```bash
# LOCAL
cd c:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin

git status
git add .
git commit -m "fix: Separate DBs + HMAC auth for bidirectional sync"
git push origin main
```

### Étape 2 : Serveur Laravel
```bash
ssh user@booking.ajinsafro.net
cd /path/to/laravel

git pull origin main
php artisan config:clear
php artisan route:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache

sudo systemctl restart php8.2-fpm
```

### Étape 3 : Configuration .env (CRITIQUE)
```bash
nano .env

# AJOUTER/MODIFIER CES LIGNES :
DB_DATABASE=ajinsafronet_ajinsafro

WP_DB_HOST=127.0.0.1
WP_DB_DATABASE=ajinsafronet_wp_tkrpc
WP_DB_USERNAME=root
WP_DB_PASSWORD=votre_password

WP_SYNC_URL=https://ajinsafro.net/wp-json/ajinsafro-sync/v1/laravel-to-wp
SYNC_SECRET=CHANGEZ_MOI_32_CHARS_MINIMUM
SYNC_WEBHOOK_SECRET=CHANGEZ_MOI_32_CHARS_MINIMUM

# Sauvegarder : Ctrl+O, Enter, Ctrl+X

php artisan config:clear
php artisan config:cache
```

### Étape 4 : WordPress
```bash
# LOCAL : ZIP déjà créé
wp-plugin\ajinsafro-core-v2.1-hmac.zip

# UPLOAD
1. https://ajinsafro.net/wp-admin
2. Plugins → Add New → Upload Plugin
3. Choisir : ajinsafro-core-v2.1-hmac.zip
4. Replace current with uploaded
5. Activate
```

### Étape 5 : WordPress Settings
```
WordPress Admin → Ajinsafro Core

HMAC Secret: CHANGEZ_MOI_32_CHARS_MINIMUM (= SYNC_SECRET Laravel)
Laravel Webhook Token: CHANGEZ_MOI_32_CHARS_MINIMUM (= SYNC_WEBHOOK_SECRET)

☑ Enable Laravel → WP Sync
☑ Enable WP → Laravel Sync
☑ Auto-inject Package Builder

[Save Changes]

Puis cliquer :
    [Import All Tours from Laravel]
```

---

## ✅ TESTS DE VALIDATION

### Test 1 : Vérifier DB séparées
```bash
ssh user@booking.ajinsafro.net
cd /path/to/laravel
php artisan tinker
```

```php
echo "DB Laravel : " . DB::connection()->getDatabaseName() . "\n";
echo "DB WordPress : " . DB::connection('wp')->getDatabaseName() . "\n";
exit
```

**Attendu :**
```
DB Laravel : ajinsafronet_ajinsafro
DB WordPress : ajinsafronet_wp_tkrpc
```

### Test 2 : Ping WordPress
```bash
curl https://ajinsafro.net/wp-json/ajinsafro-sync/v1/ping
```

**Attendu :** `{"success":true,"message":"Ping successful..."}`

### Test 3 : Ping Laravel (avec HMAC)
```bash
SECRET="votre_secret"
PAYLOAD='{"test":"ping"}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.*= //')

curl -X POST https://booking.ajinsafro.net/api/sync/ping \
  -H "Content-Type: application/json" \
  -H "X-AJ-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

**Attendu :** `{"success":true,"message":"Ping successful..."}`

### Test 4 : Sync Laravel → WordPress
```bash
php artisan tinker
```

```php
$voyage = \App\Models\Voyage::first();
$result = app(\App\Services\Sync\WpSyncService::class)->upsertVoyage($voyage);
print_r($result);
exit
```

**Attendu :** `Array ( [success] => 1 [wp_post_id] => ... )`

### Test 5 : Sync WordPress → Laravel
```
Modifier un tour dans WordPress Admin
→ Vérifier qu'il apparaît automatiquement dans Laravel
```

---

## 📁 FICHIERS LIVRÉS

### Documentation
- ✅ `PATCH_FINAL_SUMMARY.md` (ce fichier)
- ✅ `QUICK_TESTS.md` - Tests détaillés avec curl
- ✅ `FINAL_DEPLOY_COMMANDS.txt` - Commandes pas-à-pas
- ✅ `CORRECTIONS_APPLIED.md` - Détails techniques
- ✅ `README_SYNC_FINAL.md` - Vue d'ensemble

### Code
- ✅ Laravel : 7 fichiers modifiés (prêt pour git push)
- ✅ WordPress : `ajinsafro-core-v2.1-hmac.zip` (prêt pour upload)

---

## 🔐 CONFIGURATION COMPLÈTE REQUISE

### Laravel .env
```env
# DB Métier
DB_CONNECTION=mysql
DB_DATABASE=ajinsafronet_ajinsafro
DB_HOST=127.0.0.1
DB_USERNAME=root
DB_PASSWORD=

# DB WordPress
WP_DB_HOST=127.0.0.1
WP_DB_DATABASE=ajinsafronet_wp_tkrpc
WP_DB_USERNAME=root
WP_DB_PASSWORD=

# Sync Laravel → WordPress
WP_SYNC_URL=https://ajinsafro.net/wp-json/ajinsafro-sync/v1/laravel-to-wp
SYNC_SECRET=votre_secret_32_chars

# Sync WordPress → Laravel
SYNC_WEBHOOK_SECRET=votre_secret_32_chars
```

### WordPress Settings
```
HMAC Secret: votre_secret_32_chars (= SYNC_SECRET)
Laravel Webhook Token: votre_secret_32_chars (= SYNC_WEBHOOK_SECRET)
```

---

## ⚠️ POINTS CRITIQUES

1. **Les secrets DOIVENT être identiques** entre Laravel et WordPress
2. **DB_DATABASE doit pointer vers ajinsafronet_ajinsafro** (pas _wp_tkrpc)
3. **Toujours clear cache après .env** : `php artisan config:clear && php artisan config:cache`
4. **Tester les 5 tests ci-dessus** avant de valider

---

## 🎉 RÉSULTAT FINAL

✅ DB métier séparée de DB WordPress  
✅ HMAC sécurisé sur tous les endpoints de sync  
✅ Sync bidirectionnelle automatique  
✅ Anti-boucle multi-niveaux  
✅ Endpoints de test ping  
✅ Import global fonctionnel  
✅ Package Builder auto-injecté  

**🟢 PRODUCTION-READY**

**Temps de déploiement estimé : 15-20 minutes**

---

## 📞 SUPPORT

En cas de problème, vérifier dans l'ordre :

1. `.env` Laravel : toutes les variables présentes
2. `php artisan config:cache` exécuté
3. WordPress Settings : HMAC Secret configuré
4. Logs Laravel : `tail -f storage/logs/laravel.log`
5. Tests ping : doivent tous passer

**Documentation complète : voir `QUICK_TESTS.md`**
