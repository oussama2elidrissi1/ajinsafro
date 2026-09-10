<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;

class AdminMenuPermissionRegistry
{
    public const ADMIN_ACCESS_PERMISSION = 'admin.access';

    private const SYSTEM_PERMISSIONS = [
        [
            'name' => self::ADMIN_ACCESS_PERMISSION,
            'label' => 'Acces interface admin',
        ],
    ];

    public static function rolePermissionSections(?array $availablePermissions = null): array
    {
        $available = static::normalizeAvailablePermissions($availablePermissions);
        $sections = [];

        $systemSection = static::systemPermissionSection($available);
        if ($systemSection !== null) {
            $sections[] = $systemSection;
        }

        foreach (config('admin_menu.items', []) as $section) {
            $sectionNode = static::buildSectionNode($section, $available, static::pageIndex($section));

            if ($sectionNode !== null) {
                $sections[] = $sectionNode;
            }
        }

        return $sections;
    }

    public static function flatPermissionGroups(?array $availablePermissions = null): array
    {
        $available = static::normalizeAvailablePermissions($availablePermissions);
        $groups = [];

        $systemGroup = static::flatSystemPermissionGroup($available);
        if ($systemGroup !== null) {
            $groups[] = $systemGroup;
        }

        foreach (config('admin_menu.items', []) as $section) {
            $index = static::pageIndex($section);
            $permissions = [];
            $added = [];

            static::pushPermission(
                $permissions,
                $added,
                $available,
                $section['permission'] ?? null,
                static::accessLabel($section['label'] ?? 'Section'),
                $index['pages']
            );

            foreach ($section['children'] ?? [] as $child) {
                static::flattenNodePermissions($permissions, $added, $available, $child, $index['pages']);
            }

            if ($permissions === [] && $index['unmanaged'] === []) {
                continue;
            }

            $groups[] = [
                'key' => $section['key'] ?? str()->slug((string) ($section['label'] ?? 'section')),
                'label' => (string) ($section['label'] ?? 'Section'),
                'permissions' => $permissions,
                // Pages du menu qu'aucune permission ne pilote : affichees pour information,
                // afin que l'ecran d'acces reste le reflet exact du menu.
                'unmanaged' => $index['unmanaged'],
            ];
        }

        return $groups;
    }

    /**
     * Toutes les pages du menu admin (tout noeud portant une route), avec le contexte
     * necessaire pour les rapprocher d'une permission.
     *
     * @return list<array{section_key:string, section_label:string, label:string, trail:string, route:string, permissions:list<string>, reason:?string}>
     */
    public static function menuPages(): array
    {
        $pages = [];

        foreach (config('admin_menu.items', []) as $section) {
            $sectionKey = (string) ($section['key'] ?? str()->slug((string) ($section['label'] ?? 'section')));
            $sectionLabel = (string) ($section['label'] ?? 'Section');
            $index = static::pageIndex($section);

            foreach ($index['pages'] as $permission => $permissionPages) {
                foreach ($permissionPages as $page) {
                    $pages[$page['route'].'|'.$page['trail']]['section_key'] = $sectionKey;
                    $pages[$page['route'].'|'.$page['trail']]['section_label'] = $sectionLabel;
                    $pages[$page['route'].'|'.$page['trail']]['label'] = $page['label'];
                    $pages[$page['route'].'|'.$page['trail']]['trail'] = $page['trail'];
                    $pages[$page['route'].'|'.$page['trail']]['route'] = $page['route'];
                    $pages[$page['route'].'|'.$page['trail']]['permissions'][] = (string) $permission;
                    $pages[$page['route'].'|'.$page['trail']]['reason'] = null;
                }
            }

            foreach ($index['unmanaged'] as $page) {
                $pages[$page['route'].'|'.$page['trail']] = [
                    'section_key' => $sectionKey,
                    'section_label' => $sectionLabel,
                    'label' => $page['label'],
                    'trail' => $page['trail'],
                    'route' => $page['route'],
                    'permissions' => [],
                    'reason' => $page['reason'],
                ];
            }
        }

        return array_values($pages);
    }

    /**
     * Index des pages d'une branche du menu : liste des pages par permission effective,
     * plus les pages qu'aucune permission ne pilote.
     *
     * @return array{pages: array<string, list<array{label:string, trail:string, route:string}>>, unmanaged: list<array{label:string, trail:string, route:string, reason:string}>}
     */
    private static function pageIndex(array $section): array
    {
        $pages = [];
        $unmanaged = [];

        static::collectPages($section, $pages, $unmanaged, [], null);

        return ['pages' => $pages, 'unmanaged' => $unmanaged];
    }

    /**
     * Une page herite de la permission de son parent quand elle n'en declare pas :
     * c'est exactement la regle appliquee par AdminMenuService pour l'affichage du menu.
     */
    private static function collectPages(array $node, array &$pages, array &$unmanaged, array $trail, mixed $inherited): void
    {
        $trail[] = (string) ($node['label'] ?? '?');
        $own = $node['permission'] ?? null;
        $effective = static::permissionNames($own) !== [] ? $own : $inherited;

        if (! empty($node['route'])) {
            $page = [
                'label' => (string) ($node['label'] ?? '?'),
                'trail' => implode(' › ', $trail),
                'route' => (string) $node['route'],
            ];

            $names = static::permissionNames($effective);

            if ($names === []) {
                $unmanaged[] = $page + ['reason' => static::restrictionReason($node)];
            } else {
                foreach ($names as $name) {
                    $pages[$name][] = $page;
                }
            }
        }

        foreach ($node['children'] ?? [] as $child) {
            static::collectPages($child, $pages, $unmanaged, $trail, $effective);
        }
    }

    /** @return list<string> */
    private static function permissionNames(mixed $permission): array
    {
        $names = is_array($permission) ? $permission : [$permission];

        return array_values(array_filter(
            $names,
            static fn ($name): bool => is_string($name) && $name !== ''
        ));
    }

    /** Pourquoi une page n'a pas de case a cocher. */
    private static function restrictionReason(array $node): string
    {
        if (! empty($node['emails'])) {
            return 'Réservée à des comptes nominatifs (adresse e-mail).';
        }

        if (! empty($node['roles'])) {
            return 'Réservée à des rôles précis.';
        }

        if (! empty($node['gate'])) {
            return 'Réservée par une règle système non délégable.';
        }

        return 'Ouverte à tout compte ayant accès à l\'administration.';
    }

    public static function allPermissionNames(): array
    {
        $names = [];

        foreach (config('admin_menu.items', []) as $section) {
            static::collectNodePermissionNames($names, $section);
        }

        $names = array_merge($names, static::flattenPermissionValues(config('admin_menu.route_permissions', [])));
        $names = array_merge($names, static::flattenPermissionValues(config('admin_menu.route_prefix_permissions', [])));
        $names = array_merge($names, [
            self::ADMIN_ACCESS_PERMISSION,
            'agency_commissions.view',
            'commissions.view-own',
            'commissions.view-team',
            'commissions.view-all',
            'commissions.manage',
            'commissions.mark-paid',
            'commissions.export',
            'reservations.create',
            'reservations.store',
            'reservations.edit',
            'reservations.update',
            'reservations.destroy',
            'reservations.view_sensitive',
            'reservations.view_financial',
            'reservations.view_client_contact',
            'reservations.view_internal_notes',
            'reservations.view_commissions',
        ]);

        return array_values(array_unique(array_filter($names)));
    }

    /**
     * Crée en base les permissions du registre qui n'existent pas encore, afin que
     * chaque page du menu ait toujours sa case dans les écrans Accès / Rôles.
     * Ne fait aucune écriture quand tout est déjà synchronisé.
     */
    public static function ensurePermissionsExist(): void
    {
        $names = static::allPermissionNames();
        $existing = Permission::query()->whereIn('name', $names)->pluck('name')->all();
        $missing = array_diff($names, $existing);

        if ($missing === []) {
            return;
        }

        foreach ($missing as $name) {
            Permission::findOrCreate($name, 'web');
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function legacyPermissionMap(): array
    {
        return [
            'products-services.view' => ['circuits.view', 'accommodations.view', 'circuits.tour-transfers.view'],
            'circuits.departures.view' => ['circuits.view'],
            'group-deals.view' => ['circuits.view'],
            'group-deals.offers.view' => ['circuits.view'],
            'group-deals.trips.view' => ['circuits.view'],
            'group-deals.departures.view' => ['circuits.view'],
            'group-deals.participants.view' => ['circuits.view'],
            'group-deals.tiers.view' => ['circuits.view'],
            'accommodations.wordpress-hotels.view' => ['accommodations.view'],
            'accommodations.packages.view' => ['accommodations.view'],
            'activities.view' => ['accommodations.view'],
            'activities.offers.view' => ['accommodations.view'],
            'activities.categories.view' => ['accommodations.view'],
            'activities.gallery.view' => ['accommodations.view'],
            'activities.availability.view' => ['accommodations.view'],
            'hajj-omra.view' => ['products-services.view'],
            'hajj-omra.requests.view' => ['products-services.view'],
            'economic-offers.view' => ['products-services.view'],
            'economic-offers.requests.view' => ['products-services.view'],
            'transfers.view' => ['circuits.tour-transfers.view'],
            'transfers.offers.view' => ['circuits.tour-transfers.view'],
            'transfers.vehicles.view' => ['circuits.tour-transfers.view'],
            'transfers.pricing.view' => ['circuits.tour-transfers.view'],
            'transfers.availability.view' => ['circuits.tour-transfers.view'],
        ];
    }

    public static function expandLegacySelections(array $selectedPermissions): array
    {
        $selected = array_fill_keys($selectedPermissions, true);

        foreach (static::legacyPermissionMap() as $newPermission => $legacyPermissions) {
            foreach ($legacyPermissions as $legacyPermission) {
                if (isset($selected[$legacyPermission])) {
                    $selected[$newPermission] = true;
                    break;
                }
            }
        }

        return array_keys($selected);
    }

    private static function systemPermissionSection(array $available): ?array
    {
        $permissions = static::availableSystemPermissions($available);

        if ($permissions === []) {
            return null;
        }

        return [
            'key' => 'system_access',
            'label' => 'Acces systeme',
            'permissions' => $permissions,
            'modules' => [],
            'unmanaged' => [],
        ];
    }

    private static function flatSystemPermissionGroup(array $available): ?array
    {
        $permissions = static::availableSystemPermissions($available);

        if ($permissions === []) {
            return null;
        }

        return [
            'key' => 'system_access',
            'label' => 'Acces systeme',
            'permissions' => $permissions,
            'unmanaged' => [],
        ];
    }

    private static function availableSystemPermissions(array $available): array
    {
        return array_values(array_map(
            static fn (array $permission): array => $permission + ['pages' => []],
            array_filter(
                self::SYSTEM_PERMISSIONS,
                static fn (array $permission): bool => isset($available[$permission['name']])
            )
        ));
    }

    private static function buildSectionNode(array $section, array $available, array $index): ?array
    {
        $permissions = [];
        $added = [];
        $modules = [];

        static::pushPermission(
            $permissions,
            $added,
            $available,
            $section['permission'] ?? null,
            static::accessLabel($section['label'] ?? 'Section'),
            $index['pages']
        );

        foreach ($section['children'] ?? [] as $child) {
            $module = static::buildModuleNode($child, $available, $index['pages']);

            if ($module !== null) {
                $modules[] = $module;
            }
        }

        if ($permissions === [] && $modules === []) {
            return null;
        }

        return [
            'key' => $section['key'] ?? str()->slug((string) ($section['label'] ?? 'section')),
            'label' => (string) ($section['label'] ?? 'Section'),
            'permissions' => $permissions,
            'modules' => $modules,
            'unmanaged' => $index['unmanaged'],
        ];
    }

    private static function buildModuleNode(array $item, array $available, array $pageIndex): ?array
    {
        $permissions = [];
        $added = [];

        static::pushPermission(
            $permissions,
            $added,
            $available,
            $item['permission'] ?? null,
            static::accessLabel($item['label'] ?? 'Module'),
            $pageIndex
        );

        foreach ($item['children'] ?? [] as $child) {
            if (! empty($child['children'])) {
                static::pushPermission(
                    $permissions,
                    $added,
                    $available,
                    $child['permission'] ?? null,
                    static::accessLabel($child['label'] ?? 'Sous-module'),
                    $pageIndex
                );

                foreach ($child['children'] as $grandChild) {
                    static::pushPermission(
                        $permissions,
                        $added,
                        $available,
                        $grandChild['permission'] ?? null,
                        (string) ($grandChild['label'] ?? $grandChild['permission']),
                        $pageIndex
                    );
                }

                continue;
            }

            static::pushPermission(
                $permissions,
                $added,
                $available,
                $child['permission'] ?? null,
                (string) ($child['label'] ?? $child['permission']),
                $pageIndex
            );
        }

        if ($permissions === []) {
            return null;
        }

        return [
            'key' => $item['key'] ?? str()->slug((string) ($item['label'] ?? 'module')),
            'label' => (string) ($item['label'] ?? 'Module'),
            'permissions' => $permissions,
        ];
    }

    private static function flattenNodePermissions(array &$permissions, array &$added, array $available, array $node, array $pageIndex): void
    {
        static::pushPermission(
            $permissions,
            $added,
            $available,
            $node['permission'] ?? null,
            ! empty($node['children'])
                ? static::accessLabel($node['label'] ?? 'Module')
                : (string) ($node['label'] ?? ($node['permission'] ?? 'Permission')),
            $pageIndex
        );

        foreach ($node['children'] ?? [] as $child) {
            static::flattenNodePermissions($permissions, $added, $available, $child, $pageIndex);
        }
    }

    private static function collectNodePermissionNames(array &$names, array $node): void
    {
        $permission = $node['permission'] ?? null;

        if (is_string($permission) && $permission !== '') {
            $names[] = $permission;
        } elseif (is_array($permission)) {
            foreach ($permission as $permissionName) {
                if (is_string($permissionName) && $permissionName !== '') {
                    $names[] = $permissionName;
                }
            }
        }

        foreach ($node['children'] ?? [] as $child) {
            static::collectNodePermissionNames($names, $child);
        }
    }

    private static function pushPermission(array &$permissions, array &$added, array $available, mixed $name, string $label, array $pageIndex = []): void
    {
        foreach (static::permissionNames($name) as $permissionName) {
            if (! isset($available[$permissionName]) || isset($added[$permissionName])) {
                continue;
            }

            $permissions[] = [
                'name' => $permissionName,
                'label' => $label,
                // Toutes les pages du menu que cette permission ouvre : une permission
                // partagee par plusieurs pages ne doit plus en masquer aucune.
                'pages' => $pageIndex[$permissionName] ?? [],
            ];
            $added[$permissionName] = true;
        }
    }

    private static function accessLabel(string $label): string
    {
        return 'Accès ' . trim($label);
    }

    private static function normalizeAvailablePermissions(?array $availablePermissions): array
    {
        $availablePermissions ??= Permission::query()->pluck('name')->all();

        return array_fill_keys($availablePermissions, true);
    }

    private static function flattenPermissionValues(array $values): array
    {
        $permissions = [];

        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $permissions[] = $value;
                continue;
            }

            if (! is_array($value)) {
                continue;
            }

            foreach ($value as $permissionName) {
                if (is_string($permissionName) && $permissionName !== '') {
                    $permissions[] = $permissionName;
                }
            }
        }

        return $permissions;
    }
}
