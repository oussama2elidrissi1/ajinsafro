<?php

namespace App\Http\Middleware;

use App\Services\BranchScopeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');

            return redirect()->away($adminUrl . '/login');
        }

        if (isset($user->is_active) && ! $user->is_active) {
            auth()->logout();
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');

            return redirect()->away($adminUrl . '/login')->with('error', 'Your account is disabled.');
        }

        if ($this->isAdminArea($request) && ! $user->canAccessAdmin()) {
            abort(403, 'Acces admin non autorise pour ce compte.');
        }

        if ($this->isAgentArea($request) && ! $this->canAccessAgentArea($user)) {
            abort(403, 'Acces agent non autorise pour ce compte.');
        }

        if (! $this->isAdminArea($request) && ! $this->isAgentArea($request) && ! $this->canAccessAgentArea($user)) {
            abort(403, 'Acces non autorise pour ce compte.');
        }

        return $next($request);
    }

    private function isAdminArea(Request $request): bool
    {
        return $request->is('admin') || $request->is('admin/*') || $request->routeIs('admin.*');
    }

    private function isAgentArea(Request $request): bool
    {
        return $request->is('agent') || $request->is('agent/*') || $request->routeIs('agent.*');
    }

    private function canAccessAgentArea($user): bool
    {
        if ($user->canAccessAdmin()) {
            return true;
        }

        if ($user->isClientPortal() || $user->isPartner()) {
            return false;
        }

        return $user->hasRole([
            BranchScopeService::ROLE_SUPER_ADMIN,
            BranchScopeService::ROLE_SIEGE_ADMIN,
            BranchScopeService::ROLE_BRANCH_ADMIN,
            BranchScopeService::ROLE_CHEF_COMMERCIAL,
            BranchScopeService::ROLE_MANAGER,
            BranchScopeService::ROLE_COMMERCIAL,
            BranchScopeService::ROLE_AGENT,
            BranchScopeService::ROLE_COMMERCIAL_RESERVATIONS_ONLY,
            'Super Admin',
            'Admin Siege',
            'Admin Siège',
            'Chef Commercial',
            'Manager',
            'Commercial',
            'Agent',
            'Agent Offline',
        ]) || $user->can('reservations.view') || $user->can('custom_requests.view');
    }
}
