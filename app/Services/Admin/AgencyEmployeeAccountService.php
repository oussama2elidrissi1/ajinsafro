<?php

namespace App\Services\Admin;

use App\Models\AgencyEmployee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class AgencyEmployeeAccountService
{
    public function sync(AgencyEmployee $employee, array $data): void
    {
        $canLogin = (bool) ($data['can_login'] ?? false);
        $fullName = trim(($data['first_name'] ?? $employee->first_name) . ' ' . ($data['last_name'] ?? $employee->last_name));
        $roleName = $data['role_name'] ?? null;
        $user = $employee->user;

        if (! $canLogin) {
            if ($user) {
                $user->forceFill([
                    'name' => $fullName !== '' ? $fullName : $user->name,
                    'phone' => $data['phone'] ?? $user->phone,
                    'branch_id' => $employee->branch_id,
                    'job_title' => $data['position'] ?? $employee->position,
                    'is_active' => false,
                ])->save();

                $user->syncRoles([]);
                $user->syncPermissions([]);
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            return;
        }

        if (! $user) {
            $user = new User();
        }

        $user->forceFill([
            'name' => $fullName !== '' ? $fullName : $user->name,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'branch_id' => $employee->branch_id,
            'job_title' => $data['position'] ?? null,
            'user_type' => 'agency_employee',
            'is_admin' => false,
            'is_active' => ($data['status'] ?? AgencyEmployee::STATUS_ACTIVE) === AgencyEmployee::STATUS_ACTIVE,
            'access_mode' => 'role',
            'base_role' => $roleName,
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make((string) $data['password']);
        } elseif (! $user->exists) {
            $user->password = Hash::make(bin2hex(random_bytes(12)));
        }

        $user->save();
        $user->syncRoles($roleName ? [$roleName] : []);
        $user->syncPermissions([]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ((int) $employee->user_id !== (int) $user->id) {
            $employee->user()->associate($user);
            $employee->save();
        }
    }
}
