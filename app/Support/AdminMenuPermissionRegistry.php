<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;

class AdminMenuPermissionRegistry
{
    public static function rolePermissionSections(?array $availablePermissions = null): array
    {
        $available = static::normalizeAvailablePermissions($availablePermissions);
        $sections = [];

        foreach (config('admin_menu.items', []) as $section) {
            $sectionNode = static::buildSectionNode($section, $available);

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

        foreach (config('admin_menu.items', []) as $section) {
            $permissions = [];
            $added = [];

            static::pushPermission(
                $permissions,
                $added,
                $available,
                $section['permission'] ?? null,
                static::accessLabel($section['label'] ?? 'Section')
            );

            foreach ($section['children'] ?? [] as $child) {
                static::flattenNodePermissions($permissions, $added, $available, $child);
            }

            if ($permissions === []) {
                continue;
            }

            $groups[] = [
                'key' => $section['key'] ?? str()->slug((string) ($section['label'] ?? 'section')),
                'label' => (string) ($section['label'] ?? 'Section'),
                'permissions' => $permissions,
            ];
        }

        return $groups;
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

    private static function buildSectionNode(array $section, array $available): ?array
    {
        $permissions = [];
        $added = [];
        $modules = [];

        static::pushPermission(
            $permissions,
            $added,
            $available,
            $section['permission'] ?? null,
            static::accessLabel($section['label'] ?? 'Section')
        );

        foreach ($section['children'] ?? [] as $child) {
            $module = static::buildModuleNode($child, $available);

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
        ];
    }

    private static function buildModuleNode(array $item, array $available): ?array
    {
        $permissions = [];
        $added = [];

        static::pushPermission(
            $permissions,
            $added,
            $available,
            $item['permission'] ?? null,
            static::accessLabel($item['label'] ?? 'Module')
        );

        foreach ($item['children'] ?? [] as $child) {
            if (! empty($child['children'])) {
                static::pushPermission(
                    $permissions,
                    $added,
                    $available,
                    $child['permission'] ?? null,
                    static::accessLabel($child['label'] ?? 'Sous-module')
                );

                foreach ($child['children'] as $grandChild) {
                    static::pushPermission(
                        $permissions,
                        $added,
                        $available,
                        $grandChild['permission'] ?? null,
                        (string) ($grandChild['label'] ?? $grandChild['permission'])
                    );
                }

                continue;
            }

            static::pushPermission(
                $permissions,
                $added,
                $available,
                $child['permission'] ?? null,
                (string) ($child['label'] ?? $child['permission'])
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

    private static function flattenNodePermissions(array &$permissions, array &$added, array $available, array $node): void
    {
        static::pushPermission(
            $permissions,
            $added,
            $available,
            $node['permission'] ?? null,
            ! empty($node['children'])
                ? static::accessLabel($node['label'] ?? 'Module')
                : (string) ($node['label'] ?? ($node['permission'] ?? 'Permission'))
        );

        foreach ($node['children'] ?? [] as $child) {
            static::flattenNodePermissions($permissions, $added, $available, $child);
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

    private static function pushPermission(array &$permissions, array &$added, array $available, mixed $name, string $label): void
    {
        $names = is_array($name) ? $name : [$name];

        foreach ($names as $permissionName) {
            if (! is_string($permissionName) || $permissionName === '' || ! isset($available[$permissionName]) || isset($added[$permissionName])) {
                continue;
            }

            $permissions[] = [
                'name' => $permissionName,
                'label' => $label,
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
