<?php

namespace App\Console\Commands;

use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronise les permissions avec le menu admin (config/admin_menu.php).
 *
 * A lancer apres tout ajout ou retrait de page dans le menu :
 *   - les permissions manquantes sont creees (une page ajoutee devient cochable) ;
 *   - les permissions orphelines (plus referencees par aucune page) sont signalees,
 *     et supprimees avec --prune.
 *
 * La suppression est volontairement explicite : retirer une permission retire aussi
 * l'acces aux comptes et roles qui la portaient.
 */
class SyncAdminMenuPermissions extends Command
{
    protected $signature = 'admin:sync-menu-permissions
                            {--prune : Supprime les permissions qui ne correspondent plus a aucune page du menu}';

    protected $description = 'Aligne les permissions en base sur les pages declarees dans le menu admin.';

    public function handle(): int
    {
        $expected = AdminMenuPermissionRegistry::allPermissionNames();
        $existing = Permission::query()->pluck('name')->all();

        $missing = array_values(array_diff($expected, $existing));
        $orphans = array_values(array_diff($existing, $expected));

        $this->line(sprintf('Pages du menu    : %d', count(AdminMenuPermissionRegistry::menuPages())));
        $this->line(sprintf('Permissions menu : %d', count($expected)));
        $this->line(sprintf('Permissions base : %d', count($existing)));

        if ($missing !== []) {
            foreach ($missing as $name) {
                Permission::findOrCreate($name, 'web');
                $this->info('+ '.$name);
            }
            $this->info(sprintf('%d permission(s) creee(s).', count($missing)));
        } else {
            $this->info('Aucune permission manquante.');
        }

        if ($orphans === []) {
            $this->info('Aucune permission orpheline.');
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn(sprintf('%d permission(s) orpheline(s) — plus aucune page du menu ne les utilise :', count($orphans)));

        $rows = Permission::query()
            ->whereIn('name', $orphans)
            ->withCount(['roles', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission) => [
                $permission->name,
                $permission->roles_count,
                $permission->users_count,
            ])
            ->all();

        $this->table(['Permission', 'Roles', 'Utilisateurs'], $rows);

        if (! $this->option('prune')) {
            $this->line('Relancez avec --prune pour les supprimer.');
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return self::SUCCESS;
        }

        $deleted = Permission::query()->whereIn('name', $orphans)->delete();
        $this->info(sprintf('%d permission(s) supprimee(s).', $deleted));

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return self::SUCCESS;
    }
}
