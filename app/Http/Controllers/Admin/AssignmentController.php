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
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $query = Reservation::query()
            ->with(['branch', 'agent.roles', 'salesManager.roles', 'tour', 'createdBy', 'updatedBy'])
            ->orderByDesc('id');

        $this->branchScope->scopeReservations($query, $request->user(), []);
        $this->applyFilters($query, $request);

        $reservations = $query->paginate(15);
        $reservations->appends($request->query());

        return view('admin.assignments.index', [
            'reservations' => $reservations,
            'branches' => $this->branchesForUser($request->user()),
            'agentsByBranch' => $this->usersByBranch($request->user()),
            'filters' => [
                'search' => trim((string) $request->query('search', '')),
                'branch_id' => (int) $request->query('branch_id', 0),
                'agent_id' => (int) $request->query('agent_id', 0),
                'sales_manager_id' => (int) $request->query('sales_manager_id', 0),
                'status' => trim((string) $request->query('status', '')),
                'priority' => trim((string) $request->query('priority', '')),
                'unassigned' => $request->boolean('unassigned'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request, true);
        $reservation = Reservation::query()->findOrFail((int) $data['reservation_id']);
        $this->ensureReservationInScope($request->user(), $reservation);
        $this->applyAssignment($request->user(), $reservation, $data);

        return redirect()->route('admin.assignments.index')->with('success', 'Réservation affectée avec succès.');
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->ensureReservationInScope($request->user(), $reservation);
        $data = $this->validatePayload($request, false);
        $this->applyAssignment($request->user(), $reservation, $data);

        return redirect()->back()->with('success', 'Affectation mise à jour.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reservation_ids' => ['required', 'array', 'min:1'],
            'reservation_ids.*' => ['integer', 'exists:reservations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'sales_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_priority' => ['nullable', 'string', 'max:30'],
            'assignment_note' => ['nullable', 'string'],
        ]);

        $reservationIds = array_map('intval', $data['reservation_ids']);
        $reservations = Reservation::query()->whereIn('id', $reservationIds)->get();

        foreach ($reservations as $reservation) {
            $this->ensureReservationInScope($request->user(), $reservation);
            $this->applyAssignment($request->user(), $reservation, $data);
        }

        return redirect()->back()->with('success', 'Affectation groupée appliquée.');
    }

    public function remove(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->ensureReservationInScope($request->user(), $reservation);

        $reservation->forceFill([
            'branch_id' => null,
            'agent_id' => null,
            'sales_manager_id' => null,
            'assigned_at' => null,
            'assignment_priority' => null,
            'assignment_note' => null,
            'updated_by' => $request->user()->id,
        ])->save();

        return redirect()->back()->with('success', 'Affectation retirée.');
    }

    private function applyAssignment(User $currentUser, Reservation $reservation, array $data): void
    {
        $branchId = ! empty($data['branch_id']) ? (int) $data['branch_id'] : null;
        $agentId = ! empty($data['agent_id']) ? (int) $data['agent_id'] : null;
        $salesManagerId = ! empty($data['sales_manager_id']) ? (int) $data['sales_manager_id'] : null;

        if ($branchId && ! $this->branchesForUser($currentUser)->pluck('id')->contains($branchId)) {
            abort(403, 'Accès non autorisé à ce point de vente.');
        }

        if ($agentId && ! $this->userBelongsToBranch($currentUser, $agentId, $branchId)) {
            abort(403, 'Agent hors point de vente sélectionné.');
        }

        if ($salesManagerId && ! $this->userBelongsToBranch($currentUser, $salesManagerId, $branchId)) {
            abort(403, 'Chef commercial hors point de vente sélectionné.');
        }

        $reservation->forceFill([
            'branch_id' => $branchId,
            'agent_id' => $agentId,
            'sales_manager_id' => $salesManagerId,
            'assigned_at' => ($branchId || $agentId || $salesManagerId) ? now() : null,
            'assignment_priority' => $data['assignment_priority'] ?? null,
            'assignment_note' => $data['assignment_note'] ?? null,
            'updated_by' => $currentUser->id,
        ])->save();
    }

    private function validatePayload(Request $request, bool $isCreate): array
    {
        return $request->validate([
            'reservation_id' => [$isCreate ? 'required' : 'nullable', 'integer', 'exists:reservations,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'sales_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'assignment_priority' => ['nullable', 'string', 'max:30'],
            'assignment_note' => ['nullable', 'string'],
            'notify_agent' => ['nullable', 'boolean'],
        ]);
    }

    private function applyFilters($query, Request $request): void
    {
        $search = trim((string) $request->query('search', ''));
        $branchId = (int) $request->query('branch_id', 0);
        $agentId = (int) $request->query('agent_id', 0);
        $salesManagerId = (int) $request->query('sales_manager_id', 0);
        $status = trim((string) $request->query('status', ''));
        $priority = trim((string) $request->query('priority', ''));
        $unassigned = $request->boolean('unassigned');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('id', 'like', '%' . $search . '%')
                    ->orWhere('client_first_name', 'like', '%' . $search . '%')
                    ->orWhere('client_last_name', 'like', '%' . $search . '%')
                    ->orWhere('client_email', 'like', '%' . $search . '%');
            });
        }

        if ($branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        if ($agentId > 0) {
            $query->where('agent_id', $agentId);
        }

        if ($salesManagerId > 0) {
            $query->where('sales_manager_id', $salesManagerId);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($priority !== '') {
            $query->where('assignment_priority', $priority);
        }

        if ($unassigned) {
            $query->whereNull('branch_id')->whereNull('agent_id')->whereNull('sales_manager_id');
        }
    }

    private function ensureReservationInScope(User $currentUser, Reservation $reservation): void
    {
        $query = Reservation::query()->whereKey($reservation->id);
        $this->branchScope->scopeReservations($query, $currentUser, []);

        abort_unless($query->exists(), 403, 'Accès non autorisé à cette réservation.');
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

    private function usersByBranch(User $user): array
    {
        $query = User::query()->with(['roles'])->agencyStaff()->active()->orderBy('name');
        $branchIds = $this->branchScope->visibleBranchIds($user);
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query->get()->groupBy(fn (User $staff) => (string) ($staff->branch_id ?? 0))->map(function ($items) {
            return $items->map(function (User $staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'branch_id' => $staff->branch_id,
                    'role' => $staff->getRoleNames()->first(),
                    'job_title' => $staff->job_title,
                ];
            })->values()->all();
        })->toArray();
    }

    private function userBelongsToBranch(User $currentUser, int $userId, ?int $branchId): bool
    {
        $query = User::query()->whereKey($userId)->agencyStaff();
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $branchIds = $this->branchScope->visibleBranchIds($currentUser);
        if ($branchIds !== null) {
            $query->whereIn('branch_id', $branchIds);
        }

        return $query->exists();
    }
}
