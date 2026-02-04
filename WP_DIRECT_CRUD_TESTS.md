# Tests CRUD Direct WordPress - Tours (st_tours)

## Configuration

Assurez-vous que votre `.env` contient :
```env
WP_DB_HOST=127.0.0.1
WP_DB_PORT=3306
WP_DB_DATABASE=ajinsafronet_wp_tkrpc
WP_DB_USERNAME=root
WP_DB_PASSWORD=votre_password
```

---

## TEST A : Vérifier connexion et count tours

```bash
php artisan tinker --execute="dd(DB::connection('wp')->getDatabaseName());"
```

**Résultat attendu :** `"ajinsafronet_wp_tkrpc"`

```bash
php artisan tinker --execute="dd(\App\Models\Wp\WpPost::tours()->count());"
```

**Résultat attendu :** `26` (ou le nombre exact de tours)

---

## TEST B : Lire tour 14432 et ses metas

```bash
php artisan tinker
```

Puis dans tinker :
```php
$post = \App\Models\Wp\WpPost::find(14432);
echo "ID: " . $post->ID . "\n";
echo "Titre: " . $post->post_title . "\n";
echo "Slug: " . $post->post_name . "\n";
echo "Prix adulte: " . $post->getMeta('adult_price') . "\n";
echo "Durée: " . $post->getMeta('duration_day') . "\n";
echo "Destination: " . $post->getMeta('address') . "\n";
exit
```

**Résultat attendu :**
```
ID: 14432
Titre: Séjour Dubaï 7 jours (6 nuits)
Slug: sejour-dubai-7-jours-6-nuits
Prix adulte: 5000
Durée: 7 jours / 6 nuits
Destination: Dubaï, EAU
```

---

## TEST C : Créer un nouveau tour depuis Laravel

### Via UI Laravel

1. Aller sur : `http://localhost/admin/wordpress/tours`
2. Cliquer sur "Créer un tour"
3. Remplir :
   - Titre : `Test CRUD Direct {{ time() }}`
   - Destination : `Test Destination`
   - Durée : `5 jours / 4 nuits`
   - Prix adulte : `3000`
   - Status : `Publié`
4. Cliquer "Créer le tour"

### Vérification

```bash
php artisan tinker
```

```php
$post = \App\Models\Wp\WpPost::tours()->orderByDesc('ID')->first();
echo "Dernier tour créé:\n";
echo "ID: " . $post->ID . "\n";
echo "Titre: " . $post->post_title . "\n";
echo "Slug: " . $post->post_name . "\n";
echo "Status: " . $post->post_status . "\n";
exit
```

**Vérifier aussi dans WordPress Admin** : https://ajinsafro.net/wp-admin
→ Articles → Tours → Le nouveau tour doit apparaître

---

## TEST D : Modifier tour 14432 depuis Laravel

### Via UI Laravel

1. Aller sur : `http://localhost/admin/wordpress/tours`
2. Cliquer sur "Éditer" pour le tour #14432
3. Modifier le prix adulte : `6000` (au lieu de 5000)
4. Cliquer "Enregistrer"

### Vérification

```bash
php artisan tinker
```

```php
$post = \App\Models\Wp\WpPost::find(14432);
echo "Prix adulte modifié: " . $post->getMeta('adult_price') . "\n";
exit
```

**Résultat attendu :** `6000`

**Vérifier aussi dans WordPress Admin** :
→ Le prix doit être mis à jour instantanément

---

## TEST E : Supprimer un tour depuis Laravel

### Via UI Laravel

1. Créer un tour test d'abord (voir TEST C)
2. Noter son ID (ex: 14450)
3. Aller sur : `http://localhost/admin/wordpress/tours`
4. Cliquer sur "Supprimer" pour ce tour
5. Confirmer

### Vérification

```bash
php artisan tinker
```

```php
$exists = \App\Models\Wp\WpPost::find(14450); // remplacer par l'ID du tour supprimé
echo $exists ? "Tour existe encore" : "Tour supprimé";
exit
```

**Résultat attendu :** `"Tour supprimé"`

**Vérifier aussi dans WordPress Admin** : Le tour ne doit plus apparaître

---

## TEST F : Test de slug unique

```bash
php artisan tinker
```

```php
$repo = app(\App\Services\Wp\WpTourRepository::class);

// Créer tour avec slug existant
$tour1 = $repo->createTour([
    'title' => 'Test Slug Unique',
    'slug' => 'test-slug',
    'adult_price' => 1000,
]);

// Créer un autre avec le même slug
$tour2 = $repo->createTour([
    'title' => 'Test Slug Unique 2',
    'slug' => 'test-slug',  // même slug
    'adult_price' => 2000,
]);

echo "Tour 1 slug: " . $tour1->post_name . "\n";
echo "Tour 2 slug: " . $tour2->post_name . "\n";

// Cleanup
$tour1->delete();
$tour2->delete();
exit
```

**Résultat attendu :**
```
Tour 1 slug: test-slug
Tour 2 slug: test-slug-2
```

---

## TEST G : Lister tous les tours avec pagination

```bash
php artisan tinker
```

```php
$repo = app(\App\Services\Wp\WpTourRepository::class);
$tours = $repo->listTours(10);

echo "Total tours: " . $tours->total() . "\n";
echo "Page actuelle: " . $tours->currentPage() . "\n";
echo "Tours par page: " . $tours->perPage() . "\n";

echo "\nPremiers tours:\n";
foreach ($tours->take(5) as $tour) {
    echo "- [{$tour->ID}] {$tour->post_title} ({$tour->post_status})\n";
}
exit
```

**Résultat attendu :**
```
Total tours: 26
Page actuelle: 1
Tours par page: 10

Premiers tours:
- [14432] Séjour Dubaï 7 jours (6 nuits) (publish)
- [14431] Circuit Marrakech 5 jours (publish)
...
```

---

## TEST H : Test des metas (set/get/delete)

```bash
php artisan tinker
```

```php
$post = \App\Models\Wp\WpPost::find(14432);

// Set meta
$post->setMeta('test_meta_key', 'test_value');
echo "Meta set\n";

// Get meta
$value = $post->getMeta('test_meta_key');
echo "Meta value: " . $value . "\n";

// Update meta
$post->setMeta('test_meta_key', 'updated_value');
$value = $post->getMeta('test_meta_key');
echo "Meta updated: " . $value . "\n";

// Delete meta
$post->deleteMeta('test_meta_key');
$value = $post->getMeta('test_meta_key', 'not_found');
echo "Meta after delete: " . $value . "\n";

exit
```

**Résultat attendu :**
```
Meta set
Meta value: test_value
Meta updated: updated_value
Meta after delete: not_found
```

---

## TEST I : Vérifier modification visible sur WordPress

### Étape 1 : Modifier via Laravel UI
1. Aller sur : `http://localhost/admin/wordpress/tours`
2. Éditer tour #14432
3. Modifier le titre en : `Séjour Dubaï 7 jours (6 nuits) - MODIFIÉ DEPUIS LARAVEL`
4. Enregistrer

### Étape 2 : Vérifier sur WordPress
1. Ouvrir : https://ajinsafro.net/tours/sejour-dubai-7-jours-6-nuits/
2. Le nouveau titre doit s'afficher immédiatement

**✅ SUCCÈS : Modification visible sans sync, sans plugin, sans délai**

---

## Checklist finale

- [ ] TEST A : Connexion WP OK, count = 26 tours
- [ ] TEST B : Lecture tour 14432 + metas OK
- [ ] TEST C : Création tour via UI Laravel OK
- [ ] TEST D : Modification tour 14432 OK
- [ ] TEST E : Suppression tour OK
- [ ] TEST F : Slug unique automatique OK
- [ ] TEST G : Pagination tours OK
- [ ] TEST H : Set/Get/Delete metas OK
- [ ] TEST I : Modification visible sur WordPress OK

---

## Troubleshooting

### Erreur : Database connection [wp] not configured

**Solution :** Vérifier `.env` :
```env
WP_DB_DATABASE=ajinsafronet_wp_tkrpc
WP_DB_HOST=127.0.0.1
WP_DB_USERNAME=root
WP_DB_PASSWORD=***
```

Puis :
```bash
php artisan config:clear
php artisan config:cache
```

### Erreur : Class App\Console\Commands\Str not found

**Solution :** Import incorrect. Utiliser :
```php
use Illuminate\Support\Str;
```

### Tours ne s'affichent pas dans Laravel

**Solution :** Vérifier le préfixe de table dans `config/database.php` :
```php
'wp' => [
    'prefix' => 'cFdgeZ_',
    // ...
]
```

### Modifications non visibles sur WordPress

**Solution :** Vider cache WordPress :
1. WP Admin → Plugins → Caching Plugin → Clear Cache
2. Ou ajouter `?nocache=1` à l'URL

---

## Résultat attendu global

**✅ Tous les tests passent**  
**✅ Laravel CRUD direct dans DB WordPress**  
**✅ Modifications immédiatement visibles sur ajinsafro.net**  
**✅ Aucune synchronisation, aucun plugin, aucune API**  
**✅ ~26 tours affichés dans Laravel Admin**

---

## Commandes utiles

```bash
# Compter tours
php artisan tinker --execute="echo \App\Models\Wp\WpPost::tours()->count();"

# Lister premiers tours
php artisan tinker --execute="\App\Models\Wp\WpPost::tours()->limit(5)->get(['ID','post_title'])->each(fn(\$t)=>print(\$t->ID.' - '.\$t->post_title.PHP_EOL));"

# Vérifier DB
php artisan tinker --execute="echo 'Laravel DB: '.DB::connection()->getDatabaseName().PHP_EOL.'WP DB: '.DB::connection('wp')->getDatabaseName();"
```
