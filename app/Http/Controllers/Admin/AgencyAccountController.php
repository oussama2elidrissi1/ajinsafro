<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyEmployee;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

class AgencyAccountController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $query = User::query()
            ->with(['branch', 'agencyEmployee', 'roles'])
            ->agencyStaff()
            ->select('users.*')
            ->selectSub($this->assignedReservationsSubquery(), 'assigned_reservations_count')
            ->orderByDesc('last_login_at')
            ->orderBy('name');

        $this->applyScope($query, $request->user());
        $this->applyFilters($query, $request);

        $accounts = $query->paginate(15);
        $accounts->appends($request->query());

        return view('admin.agency-accounts.index', [
            'accounts' => $accounts,
            'branches' => $this->branchesForUser($request->user()),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'filters' => [
                'search' => trim((string) $request->query('search', '')),
                'branch_id' => (int) $request->query('branch_id', 0),
                'role_name' => trim((string) $request->query('role_name', '')),
                'status' => trim((string) $request->query('status', '')),
                'can_login' => trim((string) $request->query('can_login', '')),
                'last_login' => trim((string) $request->query('last_login', '')),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $employeeId = (int) $request->query('employee_id', 0);
        $employee = $employeeId > 0 ? AgencyEmployee::query()->with(['branch', 'user.roles'])->findOrFail($employeeId) : null;
        if ($employee) {
            $this->ensureEmployeeInScope($request->user(), $employee);
        }

        return view('admin.agency-accounts.form', [
            'account' => new User([
                'name' => $employee?->full_name ?? '',
                'email' => $employee?->email ?? '',
                'branch_id' => $employee?->branch_id,
                'job_title' => $employee?->position,
                'is_active' => true,
            ]),
            'employee' => $employee,
            'employees' => $this->employeesForUser($request->user()),
            'branches' => $this->branchesForUser($request->user()),
            'users' => $this->usersForUser($request->user()),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'isEdit' => false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $employee = $this->resolveEmployee($request, $data);
        $user = $this->resolveTargetUser($request, $data, $employee);

        DB::transaction(function () use ($request, $data, $employee, $user): void {
            $branchId = $employee?->branch_id ?? (int) ($data['branch_id'] ?? 0) ?: null;
            $fullName = trim(($data['name'] ?? $user?->name ?? '') ?: (($employee?->first_name ?? '') . ' ' . ($employee?->last_name ?? '')));

            if (! $user) {
                $user = new User();
            }

            $user->fill([
                'name' => $fullName !== '' ? $fullName : 'Compte point de vente',
                'email' => $data['email'],
                'phone' => $data['phone'] ?? $user->phone,
                'branch_id' => $branchId,
                'job_title' => $data['job_title'] ?? $employee?->position,
                'user_type' => 'agency_employee',
                'is_admin' => false,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'access_mode' => 'role',
                'base_role' => $data['role_name'],
            ]);

            if (! empty($data['password'])) {
                $user->password = Hash::make((string) $data['password']);
            } elseif (! $user->exists) {
                $user->password = Hash::make(bin2hex(random_bytes(12)));
            }

            $user->save();
            $user->syncRoles([$data['role_name']]);
            $user->syncPermissions([]);

            if ($employee) {
                $employee->forceFill([
                    'branch_id' => $branchId,
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? $employee->phone,
                    'position' => $data['job_title'] ?? $employee->position,
                    'status' => (bool) ($data['is_active'] ?? true) ? AgencyEmployee::STATUS_ACTIVE : AgencyEmployee::STATUS_INACTIVE,
                    'can_login' => true,
                ]);
                $employee->user()->associate($user);
                $employee->save();
            }
        });

        return redirect()
            ->route('admin.agency-accounts.index')
            ->with('success', 'Compte point de vente enregistré avec succès.');
    }

    public function show(Request $request, User $user): View
    {
        $this->ensureAccountInScope($request->user(), $user);
        $user->load(['branch', 'agencyEmployee.branch', 'roles.permissions']);

        $recentReservations = Reservation::query()
            ->with(['branch', 'tour'])
            ->assignedTo($user->id)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.agency-accounts.show', [
            'account' => $user,
            'recentReservations' => $recentReservations,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureAccountInScope($request->user(), $user);
        $user->load(['branch', 'agencyEmployee', 'roles']);

        return view('admin.agency-accounts.form', [
            'account' => $user,
            'employee' => $user->agencyEmployee,
            'employees' => $this->employeesForUser($request->user()),
            'branches' => $this->branchesForUser($request->user()),
            'users' => $this->usersForUser($request->user()),
            'roles' => Role::query()->orderBy('name')->get(['id', 'name']),
            'isEdit' => true,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAccountInScope($request->user(), $user);
        $data = $this->validatePayload($request, $user->id);
        $employee = $this->resolveEmployee($request, $data);

        DB::transaction(function () use ($data, $user, $employee): void {
            $branchId = $employee?->branch_id ?? (int) ($data['branch_id'] ?? 0) ?: $user->branch_id;
            $fullName = trim(($data['name'] ?? $user->name) ?: (($employee?->first_name ?? '') . ' ' . ($employee?->last_name ?? '')));

            $user->fill([
                'name' => $fullName !== '' ? $fullName : $user->name,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? $user->phone,
                'branch_id' => $branchId,
                'job_title' => $data['job_title'] ?? $user->job_title,
                'is_active' => (bool) ($data['is_active'] ?? $user->is_active),
                'base_role' => $data['role_name'],
                'access_mode' => 'role',
            ]);

            if (! empty($data['password'])) {
                $user->password = Hash::make((string) $data['password']);
            }

            $user->save();
            $user->syncRoles([$data['role_name']]);
            $user->syncPermissions([]);

            if ($employee) {
                $employee->forceFill([
                    'branch_id' => $branchId,
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? $employee->phone,
                    'position' => $data['job_title'] ?? $employee->position,
                    'status' => (bool) ($data['is_active'] ?? true) ? AgencyEmployee::STATUS_ACTIVE : AgencyEmployee::STATUS_INACTIVE,
                    'can_login' => (bool) ($data['can_login'] ?? true),
                ]);
                $employee->user()->associate($user);
                $employee->save();
            }
        });

        return redirect()
            ->route('admin.agency-accounts.edit', $user)
            ->with('success', 'Compte point de vente mis à jour.');
    }

    public function disable(Request $request, User $user): RedirectResponse
    {
        $this->ensureAccountInScope($request->user(), $user);

        $user->forceFill(['is_active' => false])->save();
        if ($user->agencyEmployee) {
            $user->agencyEmployee->forceFill(['can_login' => false, 'status' => AgencyEmployee::STATUS_INACTIVE])->save();
        }

        return redirect()->back()->with('success', 'Accès login désactivé.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureAccountInScope($request->user(), $user);

        $temporaryPassword = $this->generateTemporaryPassword();
        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'is_active' => true,
        ])->save();

        return redirect()
            ->back()
            ->with('success', 'Mot de passe réinitialisé. Mot de passe temporaire: ' . $temporaryPassword);
    }

    private function validatePayload(Request $request, ?int $ignoreUserId = null): array
    {
        $existingUserId = (int) $request->input('existing_user_id', 0);
        $emailRule = Rule::unique('users', 'email');
        if ($ignoreUserId || $existingUserId > 0) {
            $emailRule = $emailRule->ignore($ignoreUserId ?: $existingUserId);
        }

        $requiresPassword = ! $ignoreUserId && ! $request->filled('existing_user_id');

        return $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', $emailRule],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('agency_employees', 'id')],
            'existing_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'job_title' => ['nullable', 'string', 'max:120'],
            'role_name' => ['required', Rule::exists('roles', 'name')],
            'is_active' => ['nullable', 'boolean'],
            'can_login' => ['nullable', 'boolean'],
            'password' => [$requiresPassword ? 'required' : 'nullable', 'string', 'min:8', 'confirmed'],
            'send_invitation' => ['nullable', 'boolean'],
        ]);
    }

    private function resolveEmployee(Request $request, array $data): ?AgencyEmployee
    {
        $employeeId = (int) ($data['employee_id'] ?? 0);
        if ($employeeId <= 0) {
            return null;
        }

        $employee = AgencyEmployee::query()->with('branch')->findOrFail($employeeId);
        $this->ensureEmployeeInScope($request->user(), $employee);

        return $employee;
    }

    private function resolveTargetUser(Request $request, array $data, ?AgencyEmployee $employee)
    {
        $existingUserId = (int) ($data['existing_user_id'] ?? 0);
        if ($existingUserId > 0) {
            $user = User::query()->with('agencyEmployee')->findOrFail($existingUserId);
            $this->ensureAccountInScope($request->user(), $user);

            return $user instanceof User ? $user : null;
        }

        if ($employee?->user) {
            return $employee->user instanceof User ? $employee->user : null;
        }

        $matched = User::query()->where('email', $data['email'])->first();
        if ($matched) {
            $this->ensureAccountInScope($request->user(), $matched);
        }

        return $matched instanceof User ? $matched : null;
    }

    private function ensureEmployeeInScope(User $currentUser, AgencyEmployee $employee): void
    {
        $query = AgencyEmployee::query()->whereKey($employee->id);
        $branchIds = $this->branchScope->visibleBranchIds($currentUser);
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }

        abort_unless($query->exists(), 403, 'Accès non autorisé à cet employé.');
    }

    private function ensureAccountInScope(User $currentUser, User $account): void
    {
        $query = User::query()->whereKey($account->id);
        $this->applyScope($query, $currentUser);

        abort_unless($query->exists(), 403, 'Accès non autorisé à ce compte.');
    }

    private function applyScope($query, User $user): void
    {
        $branchIds = $this->branchScope->visibleBranchIds($user);
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }
    }

    private function applyFilters($query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        $branchId = (int) $request->query('branch_id', 0);
        $roleName = trim((string) $request->query('role_name', ''));
        $status = trim((string) $request->query('status', ''));
        $canLogin = trim((string) $request->query('can_login', ''));
        $lastLogin = trim((string) $request->query('last_login', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        if ($roleName !== '') {
            $query->whereHas('roles', fn ($builder) => $builder->where('name', $roleName));
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($canLogin === '1') {
            $query->where('is_active', true)->whereNotNull('branch_id');
        } elseif ($canLogin === '0') {
            $query->where(function ($builder): void {
                $builder->where('is_active', false)->orWhereNull('branch_id');
            });
        }

        if ($lastLogin === 'never') {
            $query->whereNull('last_login_at');
        } elseif ($lastLogin === 'recent') {
            $query->whereNotNull('last_login_at');
        }
    }

    private function branchesForUser(User $user)
    {
        $query = Branch::query()->notArchived()->orderBy('name');
        $branchIds = $this->branchScope->visibleBranchIds($user);
        if ($branchIds !== null) {
            $query->whereIn('id', $branchIds);
        }

        return $query->get();
    }

    private function employeesForUser(User $user)
    {
        $query = AgencyEmployee::query()->with('branch')->orderBy('first_name');
        $branchIds = $this->branchScope->visibleBranchIds($user);
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query->get();
    }

    private function usersForUser(User $user)
    {
        $query = User::query()->with('branch')->orderBy('name');
        $branchIds = $this->branchScope->visibleBranchIds($user);
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query->get();
    }

    private function generateTemporaryPassword(): string
    {
        return substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(12))), 0, 12);
    }

    private function assignedReservationsSubquery()
    {
        return Reservation::query()
            ->selectRaw('count(*)')
            ->where(function ($builder): void {
                $builder->whereColumn('agent_id', 'users.id')
                    ->orWhereColumn('sales_manager_id', 'users.id')
                    ->orWhereColumn('created_by', 'users.id')
                    ->orWhereColumn('created_by_user_id', 'users.id');
            });
    }
}
