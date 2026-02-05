# Test Taxonomies - Vérification Double Préfixe

## Test 1 : Lire 5 terms pour taxonomy 'language'

### Via Laravel Tinker

```bash
php artisan tinker
```

```php
// Test connexion + lecture terms
\DB::connection('wp')
    ->table('terms as t')
    ->join('term_taxonomy as tt', 't.term_id', '=', 'tt.term_id')
    ->where('tt.taxonomy', 'language')
    ->select('t.term_id', 't.name', 't.slug')
    ->orderBy('t.name')
    ->limit(5)
    ->get();
```

**Résultat attendu** :
```php
Illuminate\Support\Collection {#xxxx
  all: [
    {
      +"term_id": 123,
      +"name": "English",
      +"slug": "english",
    },
    {
      +"term_id": 124,
      +"name": "French",
      +"slug": "french",
    },
    ...
  ]
}
```

**❌ Si erreur** : `SQLSTATE[42S02]: Table 'cFdgeZ_cFdgeZ_terms' doesn't exist`
- Vérifier que les fichiers ont été modifiés correctement
- Vérifier `config/database.php` : `'prefix' => 'cFdgeZ_'`

---

## Test 2 : Vérifier toutes les taxonomies

```php
$taxonomies = ['language', 'languages', 'durations', 'st_tour_type'];

foreach ($taxonomies as $taxonomy) {
    $count = \DB::connection('wp')
        ->table('term_taxonomy')
        ->where('taxonomy', $taxonomy)
        ->count();
    
    echo "Taxonomy '$taxonomy': $count terms\n";
}
```

**Résultat attendu** :
```
Taxonomy 'language': 15 terms
Taxonomy 'languages': 15 terms
Taxonomy 'durations': 8 terms
Taxonomy 'st_tour_type': 12 terms
```

---

## Test 3 : Lire taxonomies assignées à un tour

```php
// Choisir un ID tour existant (ex: 14386)
$postId = 14386;

$assignedLanguages = \DB::connection('wp')
    ->table('term_relationships as tr')
    ->join('term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
    ->join('terms as t', 'tt.term_id', '=', 't.term_id')
    ->where('tr.object_id', $postId)
    ->where('tt.taxonomy', 'language')
    ->select('t.term_id', 't.name')
    ->get();

echo "Languages assignées: \n";
print_r($assignedLanguages);
```

**Résultat attendu** :
```
Languages assignées:
Illuminate\Support\Collection {
  all: [
    {
      +"term_id": 123,
      +"name": "English",
    },
    ...
  ]
}
```

---

## Test 4 : Écrire une taxonomy (simulation)

```php
// Test setPostTerms via repository
$repo = app(\App\Services\Wp\WpTourRepository::class);

$postId = 14386; // ID tour existant
$termIds = [123, 124]; // IDs terms existants

// Assigner terms
\DB::connection('wp')->table('term_relationships')
    ->where('object_id', $postId)
    ->whereIn('term_taxonomy_id', function($query) {
        $query->select('term_taxonomy_id')
            ->from('term_taxonomy')
            ->where('taxonomy', 'language');
    })
    ->delete();

foreach ($termIds as $termId) {
    $tt = \DB::connection('wp')->table('term_taxonomy')
        ->where('term_id', $termId)
        ->where('taxonomy', 'language')
        ->first();
    
    if ($tt) {
        \DB::connection('wp')->table('term_relationships')->insert([
            'object_id' => $postId,
            'term_taxonomy_id' => $tt->term_taxonomy_id,
            'term_order' => 0,
        ]);
        
        echo "Assigned term $termId to post $postId\n";
    }
}
```

**Résultat attendu** :
```
Assigned term 123 to post 14386
Assigned term 124 to post 14386
```

---

## Test 5 : Via SQL Direct

```sql
-- Test 1: Lire terms
SELECT t.term_id, t.name, t.slug, tt.taxonomy
FROM cFdgeZ_terms t
JOIN cFdgeZ_term_taxonomy tt ON t.term_id = tt.term_id
WHERE tt.taxonomy IN ('language', 'languages', 'durations', 'st_tour_type')
ORDER BY tt.taxonomy, t.name
LIMIT 20;

-- Test 2: Vérifier terms assignés à un tour
SELECT 
    p.ID,
    p.post_title,
    t.name as term_name,
    tt.taxonomy
FROM cFdgeZ_posts p
JOIN cFdgeZ_term_relationships tr ON p.ID = tr.object_id
JOIN cFdgeZ_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
JOIN cFdgeZ_terms t ON tt.term_id = t.term_id
WHERE p.post_type = 'st_tours'
  AND tt.taxonomy IN ('language', 'languages', 'durations', 'st_tour_type')
  AND p.ID = 14386  -- Remplacer par un ID tour existant
ORDER BY tt.taxonomy;
```

---

## Test 6 : Via Admin Laravel

```bash
# 1. Aller sur
https://admin.ajinsafro.com/admin/circuits/voyages/14386/edit

# 2. Vérifier :
# - Onglet "Catégories" s'affiche
# - Les 4 taxonomies ont des checkboxes
# - Les terms existants sont cochés
# - Pas d'erreur HTTP 500
```

---

## Débogage

### Erreur "Table doesn't exist"

**Vérifier query Laravel** :
```php
\DB::connection('wp')->enableQueryLog();

// Exécuter query
\DB::connection('wp')->table('terms')->limit(1)->get();

// Voir query exacte
dd(\DB::connection('wp')->getQueryLog());
```

**Résultat attendu** :
```php
[
    [
        "query" => "select * from `cFdgeZ_terms` limit 1",
        "bindings" => [],
        "time" => 1.23
    ]
]
```

**❌ Si** : `select * from cFdgeZ_cFdgeZ_terms`
- Le préfixe est dupliqué
- Changer `->table('cFdgeZ_terms')` en `->table('terms')`

---

## Checklist Tests

- [ ] Test 1 : Lecture 5 terms language OK
- [ ] Test 2 : Comptage toutes taxonomies OK
- [ ] Test 3 : Lecture taxonomies assignées OK
- [ ] Test 4 : Écriture taxonomies OK
- [ ] Test 5 : SQL direct retourne résultats
- [ ] Test 6 : Admin Laravel affiche taxonomies sans erreur
- [ ] Bonus : Modifier taxonomy dans Laravel → visible dans WP

**Si tous les tests passent** → Le système taxonomies est opérationnel ! ✅
