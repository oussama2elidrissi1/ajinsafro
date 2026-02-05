<?php
/**
 * TEST FINAL - Tour Location Feature
 * À exécuter dans Laravel Tinker: php artisan tinker
 */

echo "=== TEST TOUR LOCATION FEATURE ===\n\n";

// ===================================
// TEST 1: Vérifier que les locations existent
// ===================================
echo "[TEST 1] Vérifier locations dans WP DB...\n";

$locations = \DB::connection('wp')
    ->table('posts')
    ->where('post_type', 'location')
    ->where('post_status', 'publish')
    ->select('ID', 'post_title', 'post_parent')
    ->orderBy('post_parent')
    ->orderBy('post_title')
    ->get();

echo "✓ Nombre de locations: " . $locations->count() . "\n";

if ($locations->count() > 0) {
    echo "Exemples:\n";
    foreach ($locations->take(5) as $loc) {
        $indent = str_repeat('  ', $loc->post_parent > 0 ? 1 : 0);
        echo "{$indent}- [{$loc->ID}] {$loc->post_title} (parent: {$loc->post_parent})\n";
    }
} else {
    echo "⚠ ATTENTION: Aucune location trouvée. Créez des locations dans WP Admin d'abord.\n";
}

echo "\n";

// ===================================
// TEST 2: Construire l'arbre via Repository
// ===================================
echo "[TEST 2] Construire arbre hiérarchique...\n";

$repo = app(\App\Services\Wp\WpTourRepository::class);
$tree = $repo->getLocationsTree();

echo "✓ Arbre construit avec " . count($tree) . " racine(s)\n";

if (!empty($tree)) {
    echo "Structure (premier niveau):\n";
    foreach (array_slice($tree, 0, 3) as $node) {
        $childCount = count($node['children'] ?? []);
        echo "  - {$node['title']} (ID: {$node['id']}, {$childCount} enfant(s))\n";
    }
}

echo "\n";

// ===================================
// TEST 3: Parser multi_location
// ===================================
echo "[TEST 3] Parser format WordPress...\n";

$testValues = [
    '_54_,_55_,_56_' => [54, 55, 56],
    '_123_' => [123],
    '' => [],
    null => [],
];

foreach ($testValues as $input => $expected) {
    $parsed = $repo->parseMultiLocation($input);
    $match = $parsed === $expected ? '✓' : '✗';
    $inputDisplay = $input === null ? 'NULL' : "'{$input}'";
    echo "{$match} Input: {$inputDisplay} => " . json_encode($parsed) . "\n";
}

echo "\n";

// ===================================
// TEST 4: Formatter multi_location
// ===================================
echo "[TEST 4] Formatter vers format WordPress...\n";

$testIds = [
    [54, 55, 56] => '_54_,_55_,_56_',
    [123] => '_123_',
    [] => '',
    [56, 54, 55] => '_54_,_55_,_56_', // Doit trier
    [54, 54, 55] => '_54_,_55_', // Doit dédupliquer
];

foreach ($testIds as $input => $expected) {
    $formatted = $repo->formatMultiLocation($input);
    $match = $formatted === $expected ? '✓' : '✗';
    echo "{$match} Input: " . json_encode($input) . " => '{$formatted}' (attendu: '{$expected}')\n";
}

echo "\n";

// ===================================
// TEST 5: Lire multi_location d'un tour existant
// ===================================
echo "[TEST 5] Lire multi_location d'un tour...\n";

// Trouver un tour existant
$tour = \DB::connection('wp')
    ->table('posts')
    ->where('post_type', 'st_tours')
    ->where('post_status', 'publish')
    ->first();

if ($tour) {
    echo "Tour trouvé: [{$tour->ID}] {$tour->post_title}\n";
    
    $multiLocationValue = \DB::connection('wp')
        ->table('postmeta')
        ->where('post_id', $tour->ID)
        ->where('meta_key', 'multi_location')
        ->value('meta_value');
    
    echo "Valeur brute: " . ($multiLocationValue ?? 'NULL') . "\n";
    
    $parsed = $repo->parseMultiLocation($multiLocationValue);
    echo "IDs parsés: " . json_encode($parsed) . "\n";
    echo "Nombre: " . count($parsed) . " location(s)\n";
} else {
    echo "⚠ Aucun tour trouvé\n";
}

echo "\n";

// ===================================
// TEST 6: Sauvegarder multi_location (simulation)
// ===================================
echo "[TEST 6] Simulation sauvegarde...\n";

if (isset($tour)) {
    $testLocationIds = [54, 55, 56]; // IDs de test
    $formatted = $repo->formatMultiLocation($testLocationIds);
    
    echo "IDs à sauvegarder: " . json_encode($testLocationIds) . "\n";
    echo "Format WP: '{$formatted}'\n";
    
    // Sauvegarder (décommenter pour tester réellement)
    // \DB::connection('wp')
    //     ->table('postmeta')
    //     ->updateOrInsert(
    //         ['post_id' => $tour->ID, 'meta_key' => 'multi_location'],
    //         ['meta_value' => $formatted]
    //     );
    // echo "✓ Sauvegardé dans WP DB\n";
    
    echo "⚠ Sauvegarde commentée (décommenter pour tester)\n";
}

echo "\n";

// ===================================
// CHECKLIST FINALE
// ===================================
echo "=== CHECKLIST FINALE ===\n";
echo "[ ] Locations existent dans WP DB\n";
echo "[ ] Arbre hiérarchique construit correctement\n";
echo "[ ] Parser fonctionne: '_54_,_55_' => [54, 55]\n";
echo "[ ] Formatter fonctionne: [54, 55] => '_54_,_55_'\n";
echo "[ ] Format exact (pas d'espaces): '_54_,_55_,_56_'\n";
echo "[ ] Tri automatique des IDs\n";
echo "[ ] Déduplication automatique\n";

echo "\n=== TESTS UI (Manuel) ===\n";
echo "1. Aller sur /admin/circuits/voyages/{id}/edit\n";
echo "2. Onglet 'Location'\n";
echo "3. Vérifier:\n";
echo "   - Section 'Tour location' s'affiche\n";
echo "   - Arbre hiérarchique avec indentation\n";
echo "   - Champ search 'Type to search'\n";
echo "   - Locations déjà sélectionnées sont cochées\n";
echo "   - Compteur affiche le bon nombre\n";
echo "4. Cocher/décocher des locations\n";
echo "5. Sauvegarder\n";
echo "6. Vérifier en DB:\n";
echo "   SELECT meta_value FROM cFdgeZ_postmeta\n";
echo "   WHERE post_id = {tour_id} AND meta_key = 'multi_location'\n";
echo "7. Vérifier dans WP Admin:\n";
echo "   Circuits → Ouvrir tour → Section 'Tour location'\n";
echo "   Les mêmes locations doivent être cochées\n";

echo "\n✅ Si tous les tests passent: Feature opérationnelle!\n";
