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
Tu es dans mon projet Laravel 10 (booking.ajinsafro.net) + plugin WP ajinsafro-core.
Objectif: rendre la synchro BIDIRECTIONNELLE fonctionnelle + import WP→Laravel + tests curl.
Contraintes: uniquement modifications de code (local) + git push Laravel + upload zip plugin WP. Pas de modif serveur.

PROBLÈMES ACTUELS À CORRIGER (preuves):
- Laravel: DB::connection()->getDatabaseName() renvoie "ajinsafronet_wp_tkrpc" parfois → confusion DB.
- Laravel: DB connection [wp] not configured.
- Commande php artisan wp:import-tours --all échoue: "Database connection [wp] not configured" + "Class App\Console\Commands\Str not found".
- Curl POST https://ajinsafro.net/wp-json/ajinsafro-sync/v1/laravel-to-wp renvoie 401 no_signature Missing signature → WP exige HMAC signature.
- Curl POST https://booking.ajinsafro.net/api/sync/ping renvoie 404 → endpoint ping absent.
- Je veux des endpoints stables + un script de test simple.

À FAIRE (livrables):
A) Laravel - Configurer une connexion DB 'wp'
1) Modifier config/database.php pour ajouter une connexion 'wp' (mysql) alimentée par env:
   WP_DB_HOST, WP_DB_PORT, WP_DB_DATABASE, WP_DB_USERNAME, WP_DB_PASSWORD.
2) Ne pas casser la connexion default 'mysql' (Laravel métier), qui doit utiliser DB_*.
3) Ajouter un test artisan/tinker doc dans README: DB::connection('wp')->getDatabaseName() doit renvoyer ajinsafronet_wp_tkrpc.

B) Laravel - Fix WpImportTours Command
1) Dans app/Console/Commands/WpImportTours.php corriger l'import: utiliser Illuminate\Support\Str (pas App\Console\Commands\Str).
2) La commande doit utiliser DB::connection('wp') pour lire cFdgeZ_posts où post_type='st_tours' et post_status='publish'.
3) Pour chaque post:
   - upsert dans table voyages (DB default) via champ wp_post_id.
   - slug = post_name, name = post_title.
   - status = 'actif' si post_status='publish' sinon 'brouillon' (ou similaire).
   - si déjà existant, update seulement si hash a changé.
4) Log/console output clair: Created / Updated / Skipped / Errors.

C) Laravel - Endpoint Sync WP→Laravel + Ping
1) Confirmer/ajouter la route POST /api/sync/wp-to-laravel (existe déjà dans certains essais).
2) Ajouter un endpoint GET /api/sync/ping qui retourne JSON {success:true, source:'laravel', timestamp:...}
3) Ajouter un middleware de vérification signature HMAC pour /api/sync/* :
   - Header: X-AJ-Signature
   - Payload brut (raw body) signé en HMAC-SHA256 avec secret env SYNC_SECRET.
   - Comparer avec hash_equals.
   - Si absent/invalid => 401 JSON.
   - IMPORTANT: doit marcher même si Content-Type=application/json.
4) La route POST /api/sync/wp-to-laravel doit accepter payload minimal:
   { action: "updated"|"deleted", entity_type:"tour", wp_post_id:int, slug, title, content, ... }
   et doit upsert/delete dans table voyages.
5) Ajouter protection anti-boucle:
   - Si requête vient de WP, marquer SyncContext source='wp' pour empêcher VoyageObserver de repusher vers WP.
   - Stocker wp_synced_at + wp_sync_hash côté voyages.

D) Laravel - Push Laravel→WP (WpSyncService)
1) Adapter WpSyncService pour envoyer la signature HMAC attendue par WP:
   - Header: X-AJ-Signature = hash_hmac('sha256', raw_json, SYNC_SECRET)
   - (Optionnel) Authorization Bearer SYNC_TOKEN si plugin le supporte, mais la signature est obligatoire.
2) Ajouter une méthode pingWp() qui appelle GET /wp-json/ajinsafro-sync/v1/ping et log success/fail.
3) S’assurer que VoyageObserver push sur create/update/delete (sauf si SyncContext source='wp').
4) Quand WP répond avec wp_post_id, enregistrer voyages.wp_post_id si null.

E) WordPress Plugin - Clarifier header attendu + accepter Bearer (optionnel)
1) Dans ajinsafro-core RestEndpoint (laravel-to-wp), confirmer le header exigé:
   - Il renvoie "Missing signature" => donc il attend X-AJ-Signature.
2) Faire en sorte que le message d’erreur soit explicite:
   - si signature manquante => 401 code no_signature
   - si signature invalide => 401 code invalid_signature
3) (Optionnel) accepter aussi Authorization Bearer en plus, mais seulement si signature OK.

F) Documentation + Tests
1) Ajouter un fichier docs/SyncTests.md avec commandes exactes bash:
   - Test WP ping
   - Test Laravel ping
   - Test POST WP->Laravel avec signature calculée
   - Test POST Laravel->WP avec signature calculée
2) Fournir un exemple bash complet:
   SECRET="..."
   PAYLOAD='{"action":"ping"}'
   SIG=$(php -r 'echo hash_hmac("sha256", $argv[1], $argv[2]);' "$PAYLOAD" "$SECRET")
   curl -i -X POST ... -H "X-AJ-Signature: $SIG" -d "$PAYLOAD"
3) Mettre aussi l’exemple PowerShell si possible (optionnel).

FIN: fais les modifications minimales, code propre, pas de refactor inutile. Mets à jour routes/api.php + middleware + config/database.php + WpImportTours.php + WpSyncService + plugin RestEndpoint.
Ensuite donne moi:
- liste fichiers modifiés
- commandes de test exactes
- quelles variables .env doivent être présentes (DB_*, WP_DB_*, SYNC_SECRET, SYNC_TOKEN).

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

- Laravel `.env` : `SYNC_SECRET` configuré
- Laravel `.env` : `DB_DATABASE` = ajinsafronet_ajinsafro
- Laravel `.env` : `WP_DB_DATABASE` = ajinsafronet_wp_tkrpc
- WordPress Settings : HMAC Secret = même que SYNC_SECRET
- TEST 1 : Ping WordPress OK
- TEST 2 : Ping Laravel OK
- TEST 3 : Laravel → WordPress OK
- TEST 4 : WordPress → Laravel OK
- TEST 5 : DB séparées OK
- TEST 7 : Signature invalide rejetée OK

**Tous les tests doivent passer avant mise en production.**