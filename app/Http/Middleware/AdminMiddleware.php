<?php

namespace App\Http\Middleware;

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
        if (! $request->user()) {
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
            return redirect()->away($adminUrl . '/login');
        }

        if (! $request->user()->canAccessAdmin()) {
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
            return redirect()->away($adminUrl . '/admin/dashboard')->with('error', 'Access denied.');
        }

        if ($request->is('admin/*') && $this->isAgentPortalOnly($request->user())) {
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
            return redirect()->away($adminUrl . '/agent/reservations-a-la-carte')->with('error', 'Acces admin non autorise pour ce compte.');
        }

        if (isset($request->user()->is_active) && ! $request->user()->is_active) {
            auth()->logout();
            $adminUrl = rtrim((string) config('app.admin_url', config('app.url')), '/');
            return redirect()->away($adminUrl . '/login')->with('error', 'Your account is disabled.');
        }

        return $next($request);
    }

    private function isAgentPortalOnly($user): bool
    {
        if ($user->is_admin) {
            return false;
        }

        $adminRoles = [
            \App\Services\BranchScopeService::ROLE_SUPER_ADMIN,
            \App\Services\BranchScopeService::ROLE_SIEGE_ADMIN,
            \App\Services\BranchScopeService::ROLE_BRANCH_ADMIN,
            \App\Services\BranchScopeService::ROLE_CHEF_COMMERCIAL,
            \App\Services\BranchScopeService::ROLE_MANAGER,
            'Super Admin',
            'Admin SiÃ¨ge',
            'Chef Commercial',
            'Manager',
        ];

        if ($user->hasRole($adminRoles)) {
            return false;
        }

        return $user->hasRole([
            \App\Services\BranchScopeService::ROLE_AGENT,
            'Agent',
            'Agent Offline',
        ]);
    }
}
