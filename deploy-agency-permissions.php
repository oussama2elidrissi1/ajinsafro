#!/usr/bin/env php
<?php
/**
 * Script de déploiement des permissions des agences
 * Nettoie tous les caches et lance les seeders
 */

$baseDir = __DIR__;
$isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

// Couleurs pour le terminal
$colors = [
    'reset'   => "\033[0m",
    'green'   => "\033[32m",
    'red'     => "\033[31m",
    'yellow'  => "\033[33m",
    'cyan'    => "\033[36m",
    'bold'    => "\033[1m",
];

function runCommand($cmd, $description = '') {
    global $isWindows, $colors;
    
    if (!$isWindows) {
        echo "\n{$colors['cyan']}▶ {$description}{$colors['reset']}\n";
        echo "{$colors['yellow']}→ {$cmd}{$colors['reset']}\n";
    }
    
    $output = shell_exec($cmd . ' 2>&1');
    if ($output) {
        echo $output . "\n";
    }
}

echo "\n{$colors['bold']}{$colors['cyan']}╔════════════════════════════════════════════════════╗{$colors['reset']}\n";
echo "{$colors['bold']}{$colors['cyan']}║  DÉPLOIEMENT DES PERMISSIONS DES AGENCES          ║{$colors['reset']}\n";
echo "{$colors['bold']}{$colors['cyan']}╚════════════════════════════════════════════════════╝{$colors['reset']}\n";

// Changer vers le répertoire de l'app
chdir($baseDir);

// 1. Nettoyer tous les caches
echo "\n{$colors['bold']}{$colors['yellow']}[1/5] Nettoyage des caches...{$colors['reset']}\n";
runCommand('php artisan optimize:clear', 'Nettoyage optimisation');
runCommand('php artisan cache:clear', 'Nettoyage cache');
runCommand('php artisan view:clear', 'Nettoyage views');
runCommand('php artisan config:clear', 'Nettoyage config');
runCommand('php artisan route:clear', 'Nettoyage routes');

// 2. Réinitialiser le cache des permissions Spatie
echo "\n{$colors['bold']}{$colors['yellow']}[2/5] Réinitialisation cache Spatie permissions...{$colors['reset']}\n";
runCommand('php artisan permission:cache-reset', 'Cache Spatie réinitialisé');

// 3. Lancer le seeder AdminPermissionsSeeder
echo "\n{$colors['bold']}{$colors['yellow']}[3/5] Lancement AdminPermissionsSeeder...{$colors['reset']}\n";
runCommand('php artisan db:seed --class=AdminPermissionsSeeder', 'AdminPermissionsSeeder exécuté');

// 4. Lancer le seeder AjinsafroRolesSeeder
echo "\n{$colors['bold']}{$colors['yellow']}[4/5] Lancement AjinsafroRolesSeeder...{$colors['reset']}\n";
runCommand('php artisan db:seed --class=AjinsafroRolesSeeder', 'AjinsafroRolesSeeder exécuté');

// 5. Lancer le seeder AgencyPermissionsSeeder
echo "\n{$colors['bold']}{$colors['yellow']}[5/5] Lancement AgencyPermissionsSeeder...{$colors['reset']}\n";
runCommand('php artisan db:seed --class=AgencyPermissionsSeeder', 'AgencyPermissionsSeeder exécuté');

echo "\n{$colors['bold']}{$colors['green']}╔════════════════════════════════════════════════════╗{$colors['reset']}\n";
echo "{$colors['bold']}{$colors['green']}║  ✓ DÉPLOIEMENT TERMINÉ                             ║{$colors['reset']}\n";
echo "{$colors['bold']}{$colors['green']}╚════════════════════════════════════════════════════╝{$colors['reset']}\n";

echo "\n{$colors['cyan']}Prochaines étapes :{$colors['reset']}\n";
echo "1. Tester : https://booking.ajinsafro.net/admin/agencies\n";
echo "2. Vérifier que l'erreur 403 a disparu\n";
echo "3. Vérifier que la sidebar affiche correctement les agences\n";
echo "4. Vérifier qu'un seul groupe de menu est ouvert à la fois\n";
echo "\n";
