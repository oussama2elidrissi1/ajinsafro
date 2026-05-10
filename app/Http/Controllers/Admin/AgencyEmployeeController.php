<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgencyEmployeeRequest;
use App\Http\Requests\Admin\UpdateAgencyEmployeeRequest;
use App\Models\AgencyEmployee;
use App\Models\Branch;
use App\Models\Reservation;
use App\Services\Admin\AgencyEmployeeAccountService;
use App\Services\BranchScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Permission\Models\Role as SpatieRole;

class AgencyEmployeeController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope,
        protected AgencyEmployeeAccountService $accountService,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $this->employeeQueryForUser($user)
            ->with(['branch:id,name,city,country', 'user.roles'])
            ->select('agency_employees.*')
            ->selectSub($this->reservationCountSubquery(), 'handled_reservations_count');

        $search = trim((string) $request->query('search', ''));
        $branchId = (int) $request->query('branch_id', 0);
        $position = trim((string) $request->query('position', ''));
        $roleName = trim((string) $request->query('role_name', ''));
        $status = trim((string) $request->query('status', ''));
        $city = trim((string) $request->query('city', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('position', 'like', '%' . $search . '%');
            });
        }

        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        if ($position !== '') {
            $query->where('position', $position);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($city !== '') {
            $query->whereHas('branch', fn (Builder $builder) => $builder->where('city', $city));
        }

        if ($roleName !== '') {
            $query->whereHas('user.roles', fn (Builder $builder) => $builder->where('name', $roleName));
        }

        $employees = $query
            ->orderByDesc(DB::raw("CASE WHEN status = 'active' THEN 1 ELSE 0 END"))
            ->orderBy('first_name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.agency-employees.index', [
            'employees' => $employees,
            'filters' => compact('search', 'branchId', 'position', 'roleName', 'status', 'city'),
            'branches' => $this->branchesForUser($user),
            'positionOptions' => AgencyEmployee::positionOptions(),
            'roles' => SpatieRole::query()->orderBy('name')->get(['id', 'name']),
            'cityOptions' => $this->cityOptionsForUser($user),
            'statusLabels' => AgencyEmployee::statusLabels(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.agency-employees.form', [
            'employee' => new AgencyEmployee([
                'branch_id' => (int) $request->query('agency_id', 0) ?: null,
                'status' => AgencyEmployee::STATUS_ACTIVE,
            ]),
            'isEdit' => false,
            'branches' => $this->branchesForUser($request->user()),
            'roles' => SpatieRole::query()->orderBy('name')->get(['id', 'name']),
            'positionOptions' => AgencyEmployee::positionOptions(),
            'statusLabels' => AgencyEmployee::statusLabels(),
        ]);
    }

    public function store(StoreAgencyEmployeeRequest $request): RedirectResponse
    {
        $this->ensureBranchInScope($request->user(), (int) $request->input('branch_id'));
        $data = $request->validated();

        $employee = new AgencyEmployee();
        $this->fillEmployee($employee, $data, $request);
        $employee->save();
        $this->accountService->sync($employee, $data);

        return redirect()
            ->route('admin.agency-employees.show', $employee)
            ->with('success', "Employé d'agence créé avec succès.");
    }

    public function show(Request $request, AgencyEmployee $employee): View
    {
        $this->ensureEmployeeInScope($request->user(), $employee);

        $employee->load(['branch', 'user.roles']);
        $recentReservations = collect();
        if ($employee->user_id) {
            $recentReservations = Reservation::query()
                ->where(function (Builder $builder) use ($employee) {
                    $builder
                        ->where('agent_id', $employee->user_id)
                        ->orWhere('sales_manager_id', $employee->user_id)
                        ->orWhere('created_by', $employee->user_id);
                })
                ->latest()
                ->limit(8)
                ->get();
        }

        return view('admin.agency-employees.show', [
            'employee' => $employee,
            'recentReservations' => $recentReservations,
        ]);
    }

    public function edit(Request $request, AgencyEmployee $employee): View
    {
        $this->ensureEmployeeInScope($request->user(), $employee);

        return view('admin.agency-employees.form', [
            'employee' => $employee->load('user.roles'),
            'isEdit' => true,
            'branches' => $this->branchesForUser($request->user()),
            'roles' => SpatieRole::query()->orderBy('name')->get(['id', 'name']),
            'positionOptions' => AgencyEmployee::positionOptions(),
            'statusLabels' => AgencyEmployee::statusLabels(),
        ]);
    }

    public function update(UpdateAgencyEmployeeRequest $request, AgencyEmployee $employee): RedirectResponse
    {
        $this->ensureEmployeeInScope($request->user(), $employee);
        $this->ensureBranchInScope($request->user(), (int) $request->input('branch_id'));
        $data = $request->validated();

        $this->fillEmployee($employee, $data, $request);
        $employee->save();
        $this->accountService->sync($employee, $data);

        return redirect()
            ->route('admin.agency-employees.show', $employee)
            ->with('success', "Employé d'agence mis à jour.");
    }

    public function destroy(Request $request, AgencyEmployee $employee): RedirectResponse
    {
        $this->ensureEmployeeInScope($request->user(), $employee);

        if ($employee->user) {
            $employee->user->forceFill(['is_active' => false])->save();
            $employee->user->syncRoles([]);
            $employee->user->syncPermissions([]);
        }

        $employee->delete();

        return redirect()
            ->route('admin.agency-employees.index')
            ->with('success', "Employé d'agence supprimé.");
    }

    private function fillEmployee(AgencyEmployee $employee, array $data, Request $request): void
    {
        $employee->fill([
            'branch_id' => $data['branch_id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'position' => $data['position'] ?? null,
            'status' => $data['status'],
            'can_login' => (bool) ($data['can_login'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            $employee->avatar_path = $request->file('avatar')->store('agency-employees/avatars', 'public');
        }
    }

    private function employeeQueryForUser($user): Builder
    {
        $query = AgencyEmployee::query();
        $branchIds = $this->branchScope->visibleBranchIds($user);
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query;
    }

    private function ensureEmployeeInScope($user, AgencyEmployee $employee): void
    {
        abort_unless(
            $this->employeeQueryForUser($user)->whereKey($employee->id)->exists(),
            403,
            "Accès non autorisé à cet employé."
        );
    }

    private function ensureBranchInScope($user, int $branchId): void
    {
        abort_unless(
            $this->branchesForUser($user)->pluck('id')->contains($branchId),
            403,
            'Accès non autorisé à cette agence.'
        );
    }

    private function branchesForUser($user)
    {
        $query = Branch::query()->notArchived()->orderBy('name');
        $branchIds = $this->branchScope->visibleBranchIds($user);
        if ($branchIds !== null) {
            $query->whereIn('id', $branchIds);
        }

        return $query->get();
    }

    private function cityOptionsForUser($user): array
    {
        return $this->branchesForUser($user)
            ->pluck('city')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function reservationCountSubquery()
    {
        return Reservation::query()
            ->selectRaw('COUNT(*)')
            ->where(function (Builder $builder) {
                $builder
                    ->whereColumn('reservations.agent_id', 'agency_employees.user_id')
                    ->orWhereColumn('reservations.sales_manager_id', 'agency_employees.user_id')
                    ->orWhereColumn('reservations.created_by', 'agency_employees.user_id');
            });
    }
}
