<?php

/**
 * Script de diagnostic rapide pour identifier le problème de persistance des vols
 * Exécuter: php diagnose-flights.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DIAGNOSTIC PERSISTANCE VOLS ===\n\n";

// 1. Vérifier connexion DB
echo "1. Connexion MySQL (default):\n";
try {
    $pdo = DB::connection()->getPdo();
    echo "   ✅ Connecté: " . $pdo->getAttribute(PDO::ATTR_SERVER_INFO) . "\n";
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Vérifier table voyage_flight_options
echo "\n2. Table voyage_flight_options:\n";
try {
    $exists = Schema::hasTable('voyage_flight_options');
    if ($exists) {
        echo "   ✅ Table existe\n";
        
        // Vérifier colonnes critiques
        $columns = ['departure_place_id', 'depart_at', 'arrive_at', 'from_city', 'to_city', 'airline_id'];
        foreach ($columns as $col) {
            $has = Schema::hasColumn('voyage_flight_options', $col);
            echo "   " . ($has ? "✅" : "❌") . " Colonne: $col\n";
        }
    } else {
        echo "   ❌ Table n'existe PAS\n";
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

// 3. Vérifier migrations
echo "\n3. Migrations critiques:\n";
$criticalMigrations = [
    '2026_02_12_100000_create_voyage_flight_options_table',
    '2026_02_21_100000_add_departure_place_id_to_voyage_flight_options',
];
foreach ($criticalMigrations as $migration) {
    try {
        $ran = DB::table('migrations')->where('migration', $migration)->exists();
        echo "   " . ($ran ? "✅" : "❌") . " $migration\n";
    } catch (Exception $e) {
        echo "   ⚠️  Erreur vérification: " . $e->getMessage() . "\n";
    }
}

// 4. Tester un enregistrement simple
echo "\n4. Test d'écriture (voyage_flight_options):\n";
try {
    // Trouver ou créer un voyage de test
    $voyage = \App\Models\Voyage::first();
    if (!$voyage) {
        echo "   ⚠️  Aucun voyage trouvé, création d'un voyage de test...\n";
        $voyage = \App\Models\Voyage::create([
            'name' => 'Test Diagnostic',
            'slug' => 'test-diagnostic-' . time(),
        ]);
    }
    
    echo "   📍 Voyage test: ID={$voyage->id}, Name={$voyage->name}\n";
    
    // Créer un flight option de test
    $data = [
        'voyage_id' => $voyage->id,
        'type' => 'outbound',
        'day_number' => 1,
        'from_city' => 'TEST_CITY',
        'to_city' => 'TEST_DEST',
        'departure_place_id' => null,
        'depart_at' => now(),
        'sort_order' => 0,
    ];
    
    $option = \App\Models\VoyageFlightOption::create($data);
    echo "   ✅ Création réussie: ID={$option->id}\n";
    
    // Vérifier lecture
    $read = \App\Models\VoyageFlightOption::find($option->id);
    if ($read) {
        echo "   ✅ Lecture réussie: from_city={$read->from_city}, to_city={$read->to_city}\n";
    }
    
    // Nettoyer
    $option->delete();
    echo "   ✅ Nettoyage effectué\n";
    
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
    echo "   Stack: " . $e->getTraceAsString() . "\n";
}

// 5. Vérifier le contenu actuel
echo "\n5. Vols existants:\n";
try {
    $count = \App\Models\VoyageFlightOption::count();
    echo "   📊 Total: $count flight options\n";
    
    if ($count > 0) {
        $sample = \App\Models\VoyageFlightOption::with('voyage')->limit(3)->get();
        foreach ($sample as $opt) {
            echo "   - ID={$opt->id}, Voyage={$opt->voyage?->name}, Type={$opt->type}, From={$opt->from_city}, To={$opt->to_city}, DeparturePlace={$opt->departure_place_id}\n";
        }
    }
} catch (Exception $e) {
    echo "   ❌ ERREUR: " . $e->getMessage() . "\n";
}

// 6. Vérifier les lieux de départ
echo "\n6. Lieux de départ (aj_travel_departure_places):\n";
try {
    $places = DB::connection('wp')->table('aj_travel_departure_places')->limit(5)->get();
    echo "   📊 Total: " . DB::connection('wp')->table('aj_travel_departure_places')->count() . " lieux\n";
    foreach ($places as $place) {
        echo "   - ID={$place->id}, Name={$place->name}, Code={$place->code}, TravelID={$place->travel_id}\n";
    }
} catch (Exception $e) {
    echo "   ⚠️  Table WP non accessible: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DIAGNOSTIC ===\n";
echo "\n📋 RÉSUMÉ:\n";
echo "Si vous voyez des ❌, ces points doivent être résolus.\n";
echo "Si tout est ✅, le problème vient probablement du formulaire ou du JavaScript.\n";
