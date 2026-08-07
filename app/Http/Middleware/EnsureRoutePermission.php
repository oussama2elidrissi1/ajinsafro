<?php

namespace App\Http\Middleware;

use App\Services\BranchScopeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    /**
     * Administration globale de la plateforme : réservée aux comptes à portée globale
     * (siège / super admin / dev), quel que soit l'état des permissions en base.
     * La gestion des utilisateurs (admin.settings.utilisateurs*) reste hors de cette liste :
     * elle est ouverte aux responsables de point de vente, scopée à leur agence.
     */
    private const GLOBAL_ADMIN_ROUTE_PREFIXES = [
        'admin.settings.index',
        'admin.settings.parametres-generaux',
        'admin.settings.referentiels-metier',
        'admin.settings.home-page',
        'admin.settings.charge-types',
        'admin.settings.roles-permissions',
        'admin.settings.securite',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $routeName = $request->route()?->getName();
        if (! $routeName) {
            return $next($request);
        }

        if ($this->isGlobalAdminRoute($routeName) && ! $this->userHasGlobalScope($user)) {
            abort(403, 'Section réservée à l\'administration siège.');
        }

        $routePermissions = config('admin_menu.route_permissions', []);
        $permission = $routePermissions[$routeName] ?? null;

        if (! $permission) {
            foreach (config('admin_menu.route_prefix_permissions', []) as $prefix => $mappedPermission) {
                if (str_starts_with($routeName, $prefix)) {
                    $permission = $mappedPermission;
                    break;
                }
            }
        }

        if (! $permission) {
            return $next($request);
        }

        if (! $this->userCanAccess($user, $permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }

    private function isGlobalAdminRoute(string $routeName): bool
    {
        foreach (self::GLOBAL_ADMIN_ROUTE_PREFIXES as $prefix) {
            if ($routeName === $prefix || str_starts_with($routeName, $prefix.'.')) {
                return true;
            }
        }

        return false;
    }

    private function userHasGlobalScope($user): bool
    {
        return app(BranchScopeService::class)->canSeeAllBranches($user)
            || (method_exists($user, 'isDevAdmin') && $user->isDevAdmin());
    }

    private function userCanAccess($user, string|array $permission): bool
    {
        if (is_string($permission)) {
            return $user->can($permission);
        }

        foreach ($permission as $permissionName) {
            if (is_string($permissionName) && $permissionName !== '' && $user->can($permissionName)) {
                return true;
            }
        }

        return false;
    }
}
