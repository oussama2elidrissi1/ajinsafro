# Installation du Plugin WordPress Ajinsafro Core

## 📦 Plugin WordPress créé !

Le plugin complet est dans : `wp-plugin/ajinsafro-core/`

## 🚀 Installation rapide

### 1. Copier le plugin vers WordPress

```bash
# Depuis le dossier Laravel Admin
cp -r wp-plugin/ajinsafro-core /path/to/wordpress/wp-content/plugins/

# Ou via FTP/SFTP, copier le dossier ajinsafro-core dans wp-content/plugins/
```

### 2. Activer le plugin

1. Connectez-vous à WordPress Admin
2. Allez à **Extensions > Extensions installées**
3. Trouvez "Ajinsafro Core"
4. Cliquez **Activer**

### 3. Configurer le plugin

1. Dans WordPress Admin, allez à **Ajinsafro Core** (nouveau menu)
2. Remplissez les paramètres :

```
Laravel Base URL: https://booking.ajinsafro.net
Checkout Base URL: https://booking.ajinsafro.net
HMAC Secret: VotreSecretPartagé123
Enable Sync: ✓ Coché
Cache TTL: 300
```

3. Cliquez **Enregistrer les paramètres**

### 4. Ajouter le shortcode

Dans votre thème TravelerWP child, éditez `single-st_tours.php` et ajoutez :

```php
<?php
// Après le contenu du tour, ajoutez:
if (function_exists('do_shortcode')) {
    echo do_shortcode('[aj_package_builder]');
}
?>
```

---

## 📝 Configuration Laravel (pour sync)

### Créer un service de synchronisation

Créez : `app/Services/WordPress/TourSyncService.php`

```php
<?php

namespace App\Services\WordPress;

use App\Models\Voyage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TourSyncService
{
    private string $wpEndpoint;
    private string $hmacSecret;

    public function __construct()
    {
        $this->wpEndpoint = rtrim(config('wordpress.sync_endpoint'), '/');
        $this->hmacSecret = config('wordpress.hmac_secret');
    }

    public function syncTourToWordPress(Voyage $voyage): array
    {
        $payload = $this->buildPayload($voyage);
        $body = json_encode($payload);
        $signature = hash_hmac('sha256', $body, $this->hmacSecret);

        $response = Http::timeout(30)
            ->withHeaders([
                'X-AJ-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])
            ->post($this->wpEndpoint, $payload);

        if ($response->successful()) {
            $data = $response->json();
            
            // Update wp_post_id in Laravel
            $voyage->update([
                'wp_post_id' => $data['data']['post_id'] ?? null,
                'wp_synced_at' => now(),
                'wp_sync_hash' => md5($body),
            ]);

            Log::info("Tour synced to WordPress", [
                'voyage_id' => $voyage->id,
                'wp_post_id' => $data['data']['post_id'] ?? null,
            ]);

            return $data;
        }

        Log::error("WordPress sync failed", [
            'voyage_id' => $voyage->id,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        throw new \Exception("WordPress sync failed: " . $response->body());
    }

    private function buildPayload(Voyage $voyage): array
    {
        $voyage->load(['images', 'programDays']);

        return [
            'action' => 'upsert',
            'entity_type' => 'tour',
            'laravel_id' => $voyage->id,
            'slug' => $voyage->slug,
            'title' => $voyage->name,
            'content_html' => $voyage->description ?? '',
            'address' => $voyage->destination ?? '',
            'duration_day' => $voyage->duration_text ?? '',
            'adult_price' => $voyage->price_from ?? 0,
            'child_price' => 0,
            'is_featured' => 'off',
            'is_sale_schedule' => 'off',
            'discount_type' => '',
            'images' => [
                'featured' => $voyage->featured_image_url,
                'gallery' => $voyage->images->map(fn($img) => $img->url)->toArray(),
            ],
        ];
    }
}
```

### Configuration (config/wordpress.php)

```php
<?php

return [
    'sync_endpoint' => env('WP_SYNC_ENDPOINT', 'https://ajinsafro.net/wp-json/ajinsafro-sync/v1/laravel-to-wp'),
    'hmac_secret' => env('WP_HMAC_SECRET', ''),
];
```

### .env Laravel

```env
WP_SYNC_ENDPOINT=https://ajinsafro.net/wp-json/ajinsafro-sync/v1/laravel-to-wp
WP_HMAC_SECRET=VotreSecretPartagé123
```

### Exemple d'utilisation

```php
// Dans un controller ou command
use App\Services\WordPress\TourSyncService;

$syncService = app(TourSyncService::class);

$voyage = Voyage::find(1);
$result = $syncService->syncTourToWordPress($voyage);

// Ou via événement
use App\Events\VoyageUpdated;

event(new VoyageUpdated($voyage));

// Listener: app/Listeners/SyncVoyageToWordPress.php
```

---

## ✅ Test du plugin

### 1. Test shortcode

1. Allez sur un tour WordPress : `https://ajinsafro.net/tours/sejour-dubai/`
2. Vous devriez voir le Package Builder
3. Vérifiez que les jours s'affichent
4. Testez le switch entre jours
5. Vérifiez le pricing

### 2. Test sync

```bash
# Depuis Laravel
php artisan tinker

>>> $voyage = \App\Models\Voyage::first();
>>> $sync = app(\App\Services\WordPress\TourSyncService::class);
>>> $result = $sync->syncTourToWordPress($voyage);
```

Vérifiez dans WordPress :
- Le tour est créé/mis à jour
- Les images sont importées
- Le meta `_aj_laravel_voyage_id` est défini

### 3. Test checkout

1. Sur un tour WordPress, cliquez **Book Now**
2. Vous devriez être redirigé vers : `https://booking.ajinsafro.net/booking/checkout/{token}`
3. Le timer de 15 minutes devrait s'afficher

---

## 📂 Structure du plugin

```
ajinsafro-core/
├── ajinsafro-core.php          # Plugin principal
├── README.md                    # Documentation complète
├── includes/                    # Classes PHP
│   ├── Admin/Settings.php
│   ├── Ajax/Handler.php
│   ├── Core/Assets.php
│   ├── Core/Options.php
│   ├── Frontend/Shortcode.php
│   ├── Sync/RestEndpoint.php
│   └── Sync/TourSyncer.php
├── assets/                      # CSS et JS
│   ├── css/
│   │   ├── admin.css
│   │   └── package-builder.css
│   └── js/
│       └── package-builder.js
└── templates/                   # Templates PHP
    ├── admin/settings.php
    └── frontend/package-builder.php
```

---

## 🔧 Troubleshooting

### Plugin non visible dans le menu
- Vérifier la version PHP (8.0+ requis)
- Vérifier les permissions du dossier plugins
- Consulter debug.log WordPress

### Shortcode ne fonctionne pas
- Vérifier que vous êtes sur une page tour (`st_tours`)
- Vérifier que l'URL Laravel est configurée
- Consulter la console JavaScript du navigateur

### Sync échoue
- Vérifier que le HMAC secret est identique sur Laravel et WordPress
- Vérifier que "Enable Sync" est coché dans WordPress
- Consulter le log : `wp-content/uploads/ajinsafro-sync.log`

### Images ne s'importent pas
- Vérifier les permissions du dossier uploads WordPress
- Vérifier que les URLs d'images sont accessibles
- Augmenter memory_limit PHP (256M recommandé)

---

## 📞 Support

Pour toute question, consulter la documentation complète dans `ajinsafro-core/README.md`

---

**Plugin Version:** 1.0.0  
**Créé le:** 2026-02-03  
**Compatible avec:** WordPress 6.0+, PHP 8.0+, TravelerWP
