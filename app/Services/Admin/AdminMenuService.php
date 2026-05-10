<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminMenuService
{
    public function buildForUser(?User $user, array $options = []): array
    {
        if (! $user) {
            return [];
        }

        $items = config('admin_menu.items', []);
        $onlyKeys = array_values(array_filter($options['only_keys'] ?? [], 'is_string'));
        $currentRoute = Route::currentRouteName() ?? '';

        if ($onlyKeys !== []) {
            $items = array_values(array_filter($items, static fn (array $item): bool => in_array((string) ($item['key'] ?? ''), $onlyKeys, true)));
        }

        return $this->buildNodes($items, $user, $currentRoute);
    }

    private function buildNodes(array $items, User $user, string $currentRoute, int $depth = 0): array
    {
        $nodes = [];
        $seen = [];

        foreach ($items as $item) {
            $node = $this->buildNode($item, $user, $currentRoute, $depth);

            if ($node === null) {
                continue;
            }

            $fingerprint = $node['key'] ?: (($node['route'] ?? '') . '|' . $node['label']);
            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $nodes[] = $node;
        }

        return $nodes;
    }

    private function buildNode(array $item, User $user, string $currentRoute, int $depth): ?array
    {
        $label = trim((string) ($item['label'] ?? ''));
        if ($label === '') {
            return null;
        }

        $route = $this->routeExists($item['route'] ?? null) ? (string) $item['route'] : null;
        $children = $this->buildNodes($item['children'] ?? [], $user, $currentRoute, $depth + 1);
        $hasVisibleChildren = $children !== [];
        $hasOwnAccess = $this->userCanAccessItem($user, $item, $route !== null);

        if (! $hasOwnAccess && ! $hasVisibleChildren) {
            return null;
        }

        if ($route === null && ! $hasVisibleChildren) {
            return null;
        }

        $params = is_array($item['params'] ?? null) ? $item['params'] : [];
        $query = is_array($item['query'] ?? null) ? $item['query'] : [];
        $href = $hasOwnAccess && $route ? $this->buildHref($route, $params, $query) : null;
        $active = $this->isRouteActive($currentRoute, $item, $route);

        foreach ($children as $child) {
            if ($child['active'] || $child['open']) {
                $active = true;
                break;
            }
        }

        return [
            'key' => (string) ($item['key'] ?? Str::slug($label)),
            'label' => $label,
            'icon' => $item['icon'] ?? null,
            'route' => $route,
            'href' => $href,
            'params' => $params,
            'query' => $query,
            'children' => $children,
            'active' => $active,
            'open' => $hasVisibleChildren && $active,
            'depth' => $depth,
            'has_direct_access' => $hasOwnAccess,
            'is_clickable' => $href !== null,
        ];
    }

    private function userCanAccessItem(User $user, array $item, bool $routeExists): bool
    {
        if (! empty($item['roles']) && ! $user->hasRole($item['roles'])) {
            return false;
        }

        if (! empty($item['permission']) && ! $user->can($item['permission'])) {
            return false;
        }

        if (! empty($item['route']) && ! $routeExists) {
            return false;
        }

        return true;
    }

    private function routeExists(mixed $route): bool
    {
        return is_string($route) && $route !== '' && Route::has($route);
    }

    private function buildHref(string $route, array $params, array $query): ?string
    {
        try {
            return route($route, array_merge($params, $query));
        } catch (UrlGenerationException) {
            return null;
        }
    }

    private function isRouteActive(string $currentRoute, array $item, ?string $route): bool
    {
        if ($currentRoute === '') {
            return false;
        }

        $patterns = $item['active_patterns'] ?? null;
        if (! is_array($patterns) || $patterns === []) {
            $patterns = $this->defaultActivePatterns($route);
        }

        foreach ($patterns as $pattern) {
            if (! is_string($pattern) || $pattern === '') {
                continue;
            }

            if (Str::is($pattern, $currentRoute)) {
                return true;
            }
        }

        return false;
    }

    private function defaultActivePatterns(?string $route): array
    {
        if (! $route) {
            return [];
        }

        $segments = explode('.', $route);
        $last = end($segments) ?: '';
        $resourceActions = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];

        if (in_array($last, $resourceActions, true) && count($segments) > 1) {
            array_pop($segments);

            return [implode('.', $segments) . '.*'];
        }

        return [$route, $route . '.*'];
    }
}
