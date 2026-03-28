<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
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

        if (! $user->can($permission)) {
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
