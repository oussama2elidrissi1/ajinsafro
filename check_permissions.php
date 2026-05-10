<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n=== PERMISSIONS DES AGENCES ===\n";
$agencyPerms = [
    'agencies.view',
    'agencies.create',
    'agencies.edit',
    'agencies.delete',
    'agency_employees.view',
    'agency_employees.create',
    'agency_employees.edit',
    'agency_employees.delete',
    'agency_performance.view',
    'agency_commissions.view'
];

foreach ($agencyPerms as $perm) {
    $p = Permission::where('name', $perm)->first();
    echo "$perm: " . ($p ? "✓ OK" : "✗ MANQUANT") . "\n";
}

echo "\n=== RÔLES EXISTANTS ===\n";
$roles = Role::all();
foreach ($roles as $role) {
    echo "- " . $role->name . " (ID: " . $role->id . ")\n";
}

echo "\n=== UTILISATEUR DEV/ADMIN ===\n";
$user = User::where('is_admin', true)->first();
if ($user) {
    echo "Utilisateur: " . $user->email . " (ID: " . $user->id . ")\n";
    $roleNames = $user->roles->pluck('name')->toArray();
    echo "Rôles: " . (count($roleNames) > 0 ? implode(', ', $roleNames) : "AUCUN") . "\n";
    
    $allPerms = $user->getAllPermissions();
    echo "Permissions totales: " . count($allPerms) . "\n";
    echo "- agencies.view? " . ($user->can('agencies.view') ? "✓ OUI" : "✗ NON") . "\n";
    echo "- agency_employees.view? " . ($user->can('agency_employees.view') ? "✓ OUI" : "✗ NON") . "\n";
    echo "- agency_performance.view? " . ($user->can('agency_performance.view') ? "✓ OUI" : "✗ NON") . "\n";
} else {
    echo "✗ Pas d'utilisateur admin trouvé\n";
}

echo "\n";
