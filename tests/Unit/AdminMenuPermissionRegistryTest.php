<?php

namespace Tests\Unit;

use App\Support\AdminMenuPermissionRegistry;
use Tests\TestCase;

/**
 * Invariant du module d'acces : l'ecran « Permissions personnalisees » doit rester le reflet
 * exact du menu admin. Toute page ajoutee ou retiree dans config/admin_menu.php se repercute
 * automatiquement ; ce test echoue si une page devient invisible dans l'ecran.
 */
class AdminMenuPermissionRegistryTest extends TestCase
{
    public function test_every_menu_page_is_visible_in_the_custom_access_screen(): void
    {
        $groups = AdminMenuPermissionRegistry::flatPermissionGroups(
            AdminMenuPermissionRegistry::allPermissionNames()
        );

        $rendered = [];
        foreach ($groups as $group) {
            foreach ($group['permissions'] as $permission) {
                foreach ($permission['pages'] as $page) {
                    $rendered[$page['route'].'|'.$page['trail']] = true;
                }
            }
            foreach ($group['unmanaged'] as $page) {
                $rendered[$page['route'].'|'.$page['trail']] = true;
            }
        }

        $missing = [];
        foreach (AdminMenuPermissionRegistry::menuPages() as $page) {
            if (! isset($rendered[$page['route'].'|'.$page['trail']])) {
                $missing[] = $page['trail'].' ['.$page['route'].']';
            }
        }

        $this->assertSame([], $missing, "Pages du menu absentes de l'ecran d'acces :\n".implode("\n", $missing));
    }

    public function test_pages_sharing_one_permission_are_all_listed(): void
    {
        $groups = AdminMenuPermissionRegistry::flatPermissionGroups(
            AdminMenuPermissionRegistry::allPermissionNames()
        );

        $shared = [];
        foreach ($groups as $group) {
            foreach ($group['permissions'] as $permission) {
                if (count($permission['pages']) > 1) {
                    $shared[$permission['name']] = count($permission['pages']);
                }
            }
        }

        // Plusieurs pages du menu partagent volontairement une permission (ex. finance.projects.view) :
        // la case reste unique, mais elle doit nommer toutes les pages qu'elle ouvre.
        $this->assertNotSame([], $shared, 'Le cas « une permission pour plusieurs pages » doit rester couvert.');
    }

    public function test_permission_names_of_every_page_are_registered(): void
    {
        $registered = array_fill_keys(AdminMenuPermissionRegistry::allPermissionNames(), true);

        foreach (AdminMenuPermissionRegistry::menuPages() as $page) {
            foreach ($page['permissions'] as $permission) {
                $this->assertArrayHasKey(
                    $permission,
                    $registered,
                    "La permission {$permission} de la page {$page['trail']} n'est pas dans le registre."
                );
            }
        }
    }

    public function test_pages_without_permission_are_reported_with_a_reason(): void
    {
        foreach (AdminMenuPermissionRegistry::menuPages() as $page) {
            if ($page['permissions'] === []) {
                $this->assertNotNull($page['reason'], "La page {$page['trail']} doit expliquer pourquoi elle n'a pas de case.");
            }
        }

        $this->addToAssertionCount(1);
    }
}
