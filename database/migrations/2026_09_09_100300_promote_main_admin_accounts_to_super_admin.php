<?php

use Database\Seeders\SuperAdminAccountsSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Promeut les comptes d'administration principale au role `super_admin`.
 *
 * Pourquoi une migration et pas seulement un seeder : le deploiement de production
 * n'execute que `php artisan migrate --force`. Lancer `db:seed` y serait dangereux,
 * DatabaseSeeder embarquant des seeders de donnees de demonstration.
 *
 * La logique reste unique, portee par SuperAdminAccountsSeeder : cette migration ne fait
 * que l'invoquer. Elle est idempotente et n'enleve aucun role existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        (new SuperAdminAccountsSeeder)->run();
    }

    public function down(): void
    {
        // Volontairement sans effet : retirer le role super_admin au compte
        // d'administration principale le priverait de l'acces a la plateforme.
    }
};
