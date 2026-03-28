<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;

class UserAccessController extends Controller
{
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

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->address = $data['address'] ?? null;
        $user->is_admin = (bool) ($data['is_admin'] ?? true);
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

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;
        $user->address = $data['address'] ?? null;
        $user->is_admin = (bool) ($data['is_admin'] ?? true);
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
        $permissionGroups = $this->permissionGroups();
        $branches = $this->branchScope->branchesForSelect(request()->user());

        $rolePermissionsMap = $roles
            ->mapWithKeys(fn (Role $role) => [$role->name => $role->permissions->pluck('name')->values()->all()])
            ->toArray();

        $selectedRole = $user->roles->first()?->name ?? $user->base_role;

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
            'selectedPermissions' => $user->permissions->pluck('name')->toArray(),
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

        if ($data['access_mode'] === 'role' && empty($data['role_name'])) {
            throw ValidationException::withMessages([
                'role_name' => 'Le rôle est requis en mode héritage.',
            ]);
        }

        return $data;
    }

    private function syncUserAccess(User $user, array $data): void
    {
        $accessMode = $data['access_mode'];
        $roleName = $data['role_name'] ?? null;

        if ($accessMode === 'role') {
            $user->syncRoles($roleName ? [$roleName] : []);
            $user->syncPermissions([]);
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            return;
        }

        $user->syncRoles([]);
        $user->syncPermissions($data['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionGroups(): array
    {
        $groups = [];

        foreach (config('admin_menu.items', []) as $section) {
            $permissions = [];

            if (! empty($section['permission'])) {
                $permissions[] = [
                    'name' => $section['permission'],
                    'label' => 'Accès section: ' . $section['label'],
                ];
            }

            foreach ($section['children'] ?? [] as $child) {
                if (empty($child['permission'])) {
                    continue;
                }

                $permissions[] = [
                    'name' => $child['permission'],
                    'label' => $child['label'],
                ];
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
