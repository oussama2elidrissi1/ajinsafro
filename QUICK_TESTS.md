# 🧪 Tests Rapides - Synchronisation HMAC

## Configuration requise

**Laravel `.env` :**
```env
DB_DATABASE=ajinsafronet_ajinsafro
WP_DB_DATABASE=ajinsafronet_wp_tkrpc
SYNC_SECRET=votre_secret_32_chars_minimum
SYNC_TOKEN=optionnel_bearer_token
```

**WordPress Settings :**
```
HMAC Secret: votre_secret_32_chars_minimum (même que SYNC_SECRET)
Laravel Webhook Token: votre_secret_32_chars_minimum
```

---

## TEST 1 : Ping WordPress

```bash
curl -i https://ajinsafro.net/wp-json/ajinsafro-sync/v1/ping
```

**Résultat attendu :**
```json
HTTP/2 200

{
  "success": true,
  "message": "Ping successful - WordPress sync endpoint working",
  "timestamp": "2026-02-03T...",
  "source": "wordpress",
  "sync_enabled": true
}
```

---

## TEST 2 : Ping Laravel (avec HMAC)

**Générer la signature :**
```bash
# Définir vos variables
SECRET="votre_secret_32_chars_minimum"
PAYLOAD='{"test":"ping"}'

# Calculer la signature
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | sed 's/^.*= //')

# Envoyer la requête
curl -i -X POST https://booking.ajinsafro.net/api/sync/ping \
  -H "Content-Type: application/json" \
  -H "X-AJ-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

**Résultat attendu :**
```json
HTTP/2 200

{
  "success": true,
  "message": "Ping successful - Laravel sync endpoint working",
  "timestamp": "2026-02-03T...",
  "source": "laravel"
}
```

---

## TEST 3 : Laravel → WordPress (avec HMAC)

**Test automatique via Tinker :**
```bash
ssh user@booking.ajinsafro.net
cd /path/to/laravel
php artisan tinker
```

```php
$voyage = \App\Models\Voyage::first();
$result = app(\App\Services\Sync\WpSyncService::class)->upsertVoyage($voyage);
print_r($result);
exit
```

**Résultat attendu :**
```php
Array
(
    [success] => 1
    [wp_post_id] => 123
    [message] => Synced successfully to WordPress
)
```

---

## TEST 4 : WordPress → Laravel (avec HMAC)

**Modifier un tour dans WordPress Admin, puis vérifier :**
```bash
php artisan tinker
```

```php
$voyage = \App\Models\Voyage::where('name', 'LIKE', '%Test%')->first();
echo "Voyage trouvé : " . $voyage->name . "\n";
echo "WP Post ID : " . $voyage->wp_post_id . "\n";
echo "Synced at : " . $voyage->wp_synced_at . "\n";
exit
```

---

## TEST 5 : Vérifier séparation des DB

```bash
php artisan tinker
```

```php
// DB métier Laravel
echo "DB Laravel : " . DB::connection()->getDatabaseName() . "\n";
echo "Voyages count : " . \App\Models\Voyage::count() . "\n";

// DB WordPress
echo "DB WordPress : " . DB::connection('wp')->getDatabaseName() . "\n";
echo "WP Tours count : " . DB::connection('wp')->table('cFdgeZ_posts')->where('post_type', 'st_tours')->count() . "\n";
exit
```

**Résultat attendu :**
```
DB Laravel : ajinsafronet_ajinsafro
Voyages count : 26
DB WordPress : ajinsafronet_wp_tkrpc
WP Tours count : 26
```

---

## TEST 6 : Import WordPress → Laravel

```bash
php artisan wp:import-tours --all
```

**Résultat attendu :**
```
✅ Created: 26
📊 Total voyages: 26
```

---

## TEST 7 : Tester signature HMAC invalide (doit échouer)

```bash
curl -i -X POST https://booking.ajinsafro.net/api/sync/ping \
  -H "Content-Type: application/json" \
  -H "X-AJ-Signature: invalid_signature_123" \
  -d '{"test":"ping"}'
```

**Résultat attendu :**
```json
HTTP/2 401

{
  "success": false,
  "message": "Invalid HMAC signature"
}
```

---

## TEST 8 : Vérifier logs de sync

**Laravel :**
```bash
tail -f storage/logs/laravel.log | grep -i sync
```

**WordPress :**
```bash
tail -f wp-content/uploads/ajinsafro-sync.log
```

---

## Commandes utiles

### Compter les voyages par DB
```bash
php artisan tinker
```

```php
// DB métier
echo "Voyages (Laravel DB) : " . \App\Models\Voyage::count() . "\n";

// DB WordPress (via connexion wp)
echo "Tours (WP DB) : " . DB::connection('wp')->table('cFdgeZ_posts')->where('post_type', 'st_tours')->where('post_status', 'publish')->count() . "\n";
exit
```

### Forcer une synchronisation
```php
$voyage = \App\Models\Voyage::find(1);
$service = app(\App\Services\Sync\WpSyncService::class);
$result = $service->upsertVoyage($voyage);
print_r($result);
exit
```

### Vérifier la configuration
```php
echo "SYNC_SECRET : " . (config('sync.secret') ? 'Configuré' : 'MANQUANT') . "\n";
echo "WP_SYNC_URL : " . config('sync.wp_sync_url') . "\n";
echo "WP_DB : " . config('database.connections.wp.database') . "\n";
exit
```

---

## Troubleshooting

### Erreur : Missing signature

**Cause :** Header `X-AJ-Signature` manquant ou vide

**Solution :** Vérifier que le service envoie bien le header avec la signature HMAC

---

### Erreur : Invalid signature

**Cause :** Les secrets ne correspondent pas entre Laravel et WordPress

**Solution :**
1. Vérifier `.env` Laravel : `SYNC_SECRET=...`
2. Vérifier Settings WordPress : HMAC Secret = même valeur
3. Vérifier l'encodage JSON (doit être `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`)

---

### Erreur : Wrong database

**Cause :** Laravel utilise la DB WordPress au lieu de la DB métier

**Solution :**
1. Vérifier `.env` : `DB_DATABASE=ajinsafronet_ajinsafro`
2. Vérifier `.env` : `WP_DB_DATABASE=ajinsafronet_wp_tkrpc`
3. Clear config : `php artisan config:clear`
4. Vérifier : `php artisan tinker` puis `DB::connection()->getDatabaseName()`

---

## Checklist finale

- [ ] Laravel `.env` : `SYNC_SECRET` configuré
- [ ] Laravel `.env` : `DB_DATABASE` = ajinsafronet_ajinsafro
- [ ] Laravel `.env` : `WP_DB_DATABASE` = ajinsafronet_wp_tkrpc
- [ ] WordPress Settings : HMAC Secret = même que SYNC_SECRET
- [ ] TEST 1 : Ping WordPress OK
- [ ] TEST 2 : Ping Laravel OK
- [ ] TEST 3 : Laravel → WordPress OK
- [ ] TEST 4 : WordPress → Laravel OK
- [ ] TEST 5 : DB séparées OK
- [ ] TEST 7 : Signature invalide rejetée OK

**Tous les tests doivent passer avant mise en production.**
