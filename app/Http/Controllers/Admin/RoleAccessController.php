<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminMenuPermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAccessController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('admin.settings.roles-permissions.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.settings.roles-permissions.form', [
            'roleModel' => new Role(['guard_name' => 'web']),
            'permissionSections' => AdminMenuPermissionRegistry::rolePermissionSections(
                Permission::query()->pluck('name')->all()
            ),
            'selectedPermissions' => [],
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.settings.roles-permissions')->with('success', 'Rôle créé avec succès.');
    }

    public function edit(Role $role)
    {
        return view('admin.settings.roles-permissions.form', [
            'roleModel' => $role,
            'permissionSections' => AdminMenuPermissionRegistry::rolePermissionSections(
                Permission::query()->pluck('name')->all()
            ),
            'selectedPermissions' => AdminMenuPermissionRegistry::expandLegacySelections(
                $role->permissions->pluck('name')->toArray()
            ),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::exists('permissions', 'name')],
        ]);

        $role->name = $data['name'];
        $role->save();

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.settings.roles-permissions.edit', $role)->with('success', 'Rôle mis à jour.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['Admin'], true)) {
            return redirect()->route('admin.settings.roles-permissions')->with('error', 'Le rôle Admin ne peut pas être supprimé.');
        }

        $role->delete();

        return redirect()->route('admin.settings.roles-permissions')->with('success', 'Rôle supprimé.');
    }
}
