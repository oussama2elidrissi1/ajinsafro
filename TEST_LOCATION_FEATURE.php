<?php

/**
 * Test Location Feature - À exécuter dans Laravel Tinker
 * 
 * Usage: php artisan tinker
 * Puis copier/coller chaque test
 */

// ===================================
// TEST 1: Lire les locations depuis WP DB
// ===================================
$locations = \DB::connection('wp')
    ->table('posts')
    ->where('post_type', 'location')
    ->where('post_status', 'publish')
    ->select('ID', 'post_title', 'post_parent')
    ->orderBy('post_parent')
    ->orderBy('post_title')
    ->get();

echo "Nombre de locations: " . $locations->count() . "\n";
foreach ($locations->take(5) as $loc) {
    echo "- [{$loc->ID}] {$loc->post_title} (parent: {$loc->post_parent})\n";
}

// ===================================
// TEST 2: Construire l'arbre via Repository
// ===================================
$repo = app(\App\Services\Wp\WpTourRepository::class);
$tree = $repo->getLocationsTree();

echo "\nArbre de locations:\n";
print_r($tree);

// ===================================
// TEST 3: Lire multi_location pour un tour
// ===================================
$tourId = 14386; // Remplacer par un ID tour existant

$multiLocationValue = \DB::connection('wp')
    ->table('postmeta')
    ->where('post_id', $tourId)
    ->where('meta_key', 'multi_location')
    ->value('meta_value');

echo "\nTour #{$tourId} - multi_location brut: " . ($multiLocationValue ?? 'NULL') . "\n";

// Parser avec la méthode repository
$locationIds = $repo->parseMultiLocation($multiLocationValue);
echo "IDs parsés: " . implode(', ', $locationIds) . "\n";

// ===================================
// TEST 4: Format multi_location (Laravel -> WP)
// ===================================
$testIds = [54, 55, 56];
$formatted = $repo->formatMultiLocation($testIds);

echo "\nTest format:\n";
echo "Input IDs: " . implode(', ', $testIds) . "\n";
echo "Format WP: " . $formatted . "\n";
echo "Attendu: _54_,_55_,_56_\n";

// ===================================
// TEST 5: Sauvegarder multi_location
// ===================================
$testTourId = 14386; // Remplacer par un ID tour de test
$testLocationIds = [54, 55, 56]; // IDs locations à sauvegarder

// Format
$formattedValue = $repo->formatMultiLocation($testLocationIds);

// Update ou insert
\DB::connection('wp')
    ->table('postmeta')
    ->updateOrInsert(
        ['post_id' => $testTourId, 'meta_key' => 'multi_location'],
        ['meta_value' => $formattedValue]
    );

echo "\n✓ Sauvegardé multi_location pour tour #{$testTourId}: {$formattedValue}\n";

// Vérifier
$saved = \DB::connection('wp')
    ->table('postmeta')
    ->where('post_id', $testTourId)
    ->where('meta_key', 'multi_location')
    ->value('meta_value');

echo "Valeur en DB: {$saved}\n";
echo ($saved === $formattedValue ? "✓ SUCCÈS" : "✗ ERREUR") . "\n";

// ===================================
// TEST 6: Vérifier dans WP Admin
// ===================================
echo "\n=== VÉRIFICATION FINALE ===\n";
echo "1. Aller dans WP Admin → Circuits → Ouvrir tour #{$testTourId}\n";
echo "2. Scroll vers 'Tour location'\n";
echo "3. Vérifier que les locations 54, 55, 56 sont cochées\n";
echo "4. Aller dans Laravel Admin → /admin/circuits/voyages/{$testTourId}/edit\n";
echo "5. Onglet 'Location' → Vérifier que les mêmes locations sont cochées\n";
echo "6. Cocher/décocher des locations et sauvegarder\n";
echo "7. Recharger WP Admin → Les changements doivent être visibles\n";

// ===================================
// RÉSULTAT ATTENDU
// ===================================
echo "\n=== RÉSULTAT ATTENDU ===\n";
echo "✓ Locations affichées en arbre hiérarchique\n";
echo "✓ Champ search 'Type to search' filtre les locations\n";
echo "✓ Locations déjà sélectionnées sont pré-cochées\n";
echo "✓ Format WP respecté: _ID1_,_ID2_,_ID3_\n";
echo "✓ Sauvegarde fonctionne: Laravel ↔ WordPress\n";
