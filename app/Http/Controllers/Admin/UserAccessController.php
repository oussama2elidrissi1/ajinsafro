<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BranchScopeService;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class UserAccessController extends Controller
{
    /** Rôles à portée globale : non attribuables par un compte limité à une agence. */
    private const GLOBAL_ROLE_NAMES = [
        BranchScopeService::ROLE_SUPER_ADMIN,
        BranchScopeService::ROLE_SIEGE_ADMIN,
        'Super Admin',
        'Admin Siège',
        'Admin',
    ];

    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));

        $query = User::query()->with(['roles', 'branch']);
        $this->branchScope->scopeUsers($query, $request->user());
        $users = $query
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(15);

        $users->appends($request->query());

        return view('admin.settings.utilisateurs.index', compact('users', 'search'));
    }

    public function create()
    {
        return view('admin.settings.utilisateurs.form', $this->buildFormPayload(new User()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request, true);
        $this->constrainPayloadToScope($request->user(), $data);

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->address = $data['address'] ?? null;
        $user->is_admin = (bool) ($data['is_admin'] ?? false);
        $user->is_active = (bool) ($data['is_active'] ?? true);
        $user->access_mode = $data['access_mode'];
        $user->base_role = $data['access_mode'] === 'role' ? ($data['role_name'] ?? null) : null;
        $user->branch_id = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        $user->manager_id = ! empty($data['manager_id']) ? (int) $data['manager_id'] : null;
        $user->job_title = $data['job_title'] ?? null;
        $user->user_type = $data['user_type'] ?? null;
        $user->password = Hash::make($data['password']);
        $user->save();

        $this->syncUserAccess($user, $data);

        return redirect()
            ->route('admin.settings.utilisateurs')
            ->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(Request $request, User $user)
    {
        $this->ensureUserInScope($request->user(), $user);
        return view('admin.settings.utilisateurs.form', $this->buildFormPayload($user));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureUserInScope($request->user(), $user);
        $data = $this->validatePayload($request, false, $user);
        $this->constrainPayloadToScope($request->user(), $data);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->address = $data['address'] ?? null;
        $user->is_admin = (bool) ($data['is_admin'] ?? false);
        $user->is_active = (bool) ($data['is_active'] ?? true);
        $user->access_mode = $data['access_mode'];
        $user->base_role = $data['access_mode'] === 'role' ? ($data['role_name'] ?? null) : null;
        $user->branch_id = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        $user->manager_id = array_key_exists('manager_id', $data) ? (empty($data['manager_id']) ? null : (int) $data['manager_id']) : $user->manager_id;
        $user->job_title = $data['job_title'] ?? null;
        $user->user_type = $data['user_type'] ?? null;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        $this->syncUserAccess($user, $data);

        return redirect()
            ->route('admin.settings.utilisateurs.edit', $user)
            ->with('success', 'Utilisateur mis à jour.');
    }

    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->ensureUserInScope($request->user(), $user);
        $user->is_active = ! $user->is_active;
        $user->save();

        return redirect()
            ->route('admin.settings.utilisateurs')
            ->with('success', $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.settings.utilisateurs')->with('error', 'Vous ne pouvez pas supprimer votre propre compte.');
        }
        $this->ensureUserInScope($request->user(), $user);

        $user->delete();

        return redirect()->route('admin.settings.utilisateurs')->with('success', 'Utilisateur supprimé.');
    }

    /**
     * Un compte limité à une agence ne peut créer/modifier que des utilisateurs de son
     * point de vente, sans rôle global, sans is_admin, et sans permissions qu'il n'a pas lui-même.
     */
    private function constrainPayloadToScope(User $currentUser, array &$data): void
    {
        $visibleBranchIds = $this->branchScope->visibleBranchIds($currentUser);
        if ($visibleBranchIds === null) {
            return;
        }

        $branchId = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        if ($branchId === null) {
            $data['branch_id'] = $visibleBranchIds[0] ?? null;
        } elseif (! in_array($branchId, $visibleBranchIds, true)) {
            throw ValidationException::withMessages([
                'branch_id' => 'Vous ne pouvez gérer que les utilisateurs de votre point de vente.',
            ]);
        }

        $data['is_admin'] = false;

        if (! empty($data['role_name']) && in_array($data['role_name'], self::GLOBAL_ROLE_NAMES, true)) {
            throw ValidationException::withMessages([
                'role_name' => 'Ce rôle est réservé au siège.',
            ]);
        }

        if ($data['access_mode'] === 'custom') {
            $ownPermissions = $currentUser->getAllPermissions()->pluck('name')->all();
            $data['permissions'] = array_values(array_intersect($data['permissions'] ?? [], $ownPermissions));
        }
    }

    private function ensureUserInScope(User $currentUser, User $targetUser): void
    {
        $query = User::query()->where('id', $targetUser->id);
        $this->branchScope->scopeUsers($query, $currentUser);
        if ($query->doesntExist()) {
            abort(403, 'Accès non autorisé à cet utilisateur.');
        }
    }

    private function buildFormPayload(User $user): array
    {
        $roles = Role::query()->orderBy('name')->get();
        if ($this->branchScope->visibleBranchIds(request()->user()) !== null) {
            $roles = $roles->reject(fn (Role $role) => in_array($role->name, self::GLOBAL_ROLE_NAMES, true))->values();
        }
        $permissionGroups = AdminMenuPermissionRegistry::flatPermissionGroups(
            Permission::query()->pluck('name')->all()
        );
        $branches = $this->branchScope->branchesForSelect(request()->user());

        $rolePermissionsMap = $roles
            ->mapWithKeys(fn (Role $role) => [$role->name => $role->permissions->pluck('name')->values()->all()])
            ->toArray();

        $selectedRole = $user->roles->first()?->name ?? $user->base_role;
        $accessMode = $user->access_mode ?: 'role';

        $directPermissions = $user->permissions->pluck('name')->values()->all();
        $rolePermissions = $selectedRole ? ($rolePermissionsMap[$selectedRole] ?? []) : [];
        $selectedPermissions = AdminMenuPermissionRegistry::expandLegacySelections(
            $accessMode === 'role' ? $rolePermissions : $directPermissions
        );

        $managersQuery = User::query()->where('is_active', true)->whereNotNull('branch_id');
        $this->branchScope->scopeUsers($managersQuery, request()->user());
        $managers = $managersQuery->orderBy('name')->get(['id', 'name', 'email', 'branch_id']);

        return [
            'userModel' => $user,
            'roles' => $roles,
            'branches' => $branches,
            'managers' => $managers,
            'permissionGroups' => $permissionGroups,
            'rolePermissionsMap' => $rolePermissionsMap,
            'selectedRole' => $selectedRole,
            'selectedPermissions' => $selectedPermissions,
            'isEdit' => $user->exists,
        ];
    }

    private function validatePayload(Request $request, bool $isCreate, ?User $user = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        if ($user) {
            $emailRule = $emailRule->ignore($user->id);
        }

        $baseRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'phone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'job_title' => ['nullable', 'string', 'max:100'],
            'user_type' => ['nullable', 'string', 'max:50'],
            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'access_mode' => ['required', Rule::in(['role', 'custom'])],
            'role_name' => ['nullable', Rule::exists('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ];

        if ($isCreate) {
            $baseRules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $baseRules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        $data = $request->validate($baseRules);
        $data['permissions'] = $this->normalizePermissionsInput($data['permissions'] ?? []);

        if ($data['access_mode'] === 'role' && empty($data['role_name'])) {
            throw ValidationException::withMessages([
                'role_name' => 'Le rôle est requis en mode héritage.',
            ]);
        }

        return $data;
    }

    private function normalizePermissionsInput(mixed $permissions): array
    {
        if (! is_array($permissions)) {
            return [];
        }

        $permissions = array_values(array_unique(array_filter(array_map(static fn ($value) => is_string($value) ? trim($value) : '', $permissions))));

        if (empty($permissions)) {
            return [];
        }

        return \Spatie\Permission\Models\Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->values()
            ->all();
    }

    private function syncUserAccess(User $user, array $data): void
    {
        $accessMode = $data['access_mode'];
        $roleName = $data['role_name'] ?? null;
        $permissions = $this->normalizePermissionsInput($data['permissions'] ?? []);

        \Log::debug('UserAccessController@syncUserAccess payload', [
            'user_id' => $user->id,
            'access_mode' => $accessMode,
            'role_name' => $roleName,
            'permissions_count' => count($permissions),
            'permissions' => $permissions,
        ]);

        if ($accessMode === 'role') {
            $user->syncRoles($roleName ? [$roleName] : []);
            $user->syncPermissions([]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            return;
        }

        $user->syncRoles([]);
        $user->syncPermissions($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionGroups(): array
    {
        $groups = [];
        $availablePermissions = Permission::query()->pluck('name')->flip();

        foreach (config('admin_menu.items', []) as $section) {
            $permissions = [];
            $addedInGroup = [];

            $pushPermission = static function (string $name, string $label) use (&$permissions, &$addedInGroup, $availablePermissions): void {
                if (! $availablePermissions->has($name)) {
                    return;
                }

                if (isset($addedInGroup[$name])) {
                    return;
                }

                $permissions[] = [
                    'name' => $name,
                    'label' => $label,
                ];
                $addedInGroup[$name] = true;
            };

            if (! empty($section['permission'])) {
                $pushPermission((string) $section['permission'], 'Accès section: ' . $section['label']);
            }

            foreach ($section['children'] ?? [] as $child) {
                if (empty($child['permission'])) {
                    continue;
                }

                $pushPermission((string) $child['permission'], (string) ($child['label'] ?? $child['permission']));
            }

            if (! empty($permissions)) {
                $groups[] = [
                    'key' => $section['key'] ?? str()->slug($section['label']),
                    'label' => $section['label'],
                    'permissions' => $permissions,
                ];
            }
        }

        return $groups;
    }
}
