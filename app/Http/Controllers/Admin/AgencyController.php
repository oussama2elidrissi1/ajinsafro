<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAgencyRequest;
use App\Http\Requests\Admin\UpdateAgencyRequest;
use App\Models\AgencyEmployee;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\User;
use App\Services\BranchScopeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AgencyController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = $this->agencyQueryForUser($user)
            ->with(['manager:id,name,email'])
            ->withCount([
                'agencyEmployees',
                'reservations',
                'users',
            ])
            ->select('branches.*')
            ->selectSub($this->revenueSubquery(), 'revenue_total');

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $city = trim((string) $request->query('city', ''));
        $country = trim((string) $request->query('country', ''));
        $agencyType = trim((string) $request->query('agency_type', ''));
        $managerId = (int) $request->query('manager_id', 0);
        $activity = trim((string) $request->query('activity', ''));

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('country', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        if ($city !== '') {
            $query->where('city', $city);
        }

        if ($country !== '') {
            $query->where('country', $country);
        }

        if ($agencyType !== '') {
            $query->where('agency_type', $agencyType);
        }

        if ($managerId > 0) {
            $query->where('manager_user_id', $managerId);
        }

        if ($activity === 'active') {
            $query->where('is_active', true);
        } elseif ($activity === 'inactive') {
            $query->where('is_active', false);
        }

        $agencies = $query
            ->orderByDesc(DB::raw("CASE WHEN status = 'active' THEN 1 ELSE 0 END"))
            ->orderBy('name')
            ->paginate(15);
        $agencies->appends($request->query());

        $summaryQuery = clone $query;
        $visibleAgencies = $summaryQuery->get(['id', 'status', 'default_commission_rate']);
        $totalRevenue = (float) $visibleAgencies->sum(function (Branch $branch): float {
            return (float) ($branch->revenue_total ?? 0);
        });

        return view('admin.agencies.index', [
            'agencies' => $agencies,
            'filters' => compact('search', 'status', 'city', 'country', 'agencyType', 'managerId', 'activity'),
            'managerOptions' => $this->managerOptionsForUser($user),
            'cityOptions' => $this->cityOptionsForUser($user),
            'countryOptions' => $this->countryOptionsForUser($user),
            'agencyTypeLabels' => Branch::agencyTypeLabels(),
            'statusLabels' => Branch::statusLabels(),
            'kpis' => [
                ['label' => 'Points de vente', 'value' => number_format($visibleAgencies->count(), 0, ',', ' '), 'icon' => 'bx bx-buildings', 'color' => '-blue', 'note' => 'Périmètre visible'],
                ['label' => 'Actives', 'value' => number_format($visibleAgencies->where('status', Branch::STATUS_ACTIVE)->count(), 0, ',', ' '), 'icon' => 'bx bx-badge-check', 'color' => '-green', 'note' => 'En activité'],
                ['label' => 'Suspendues', 'value' => number_format($visibleAgencies->where('status', Branch::STATUS_SUSPENDED)->count(), 0, ',', ' '), 'icon' => 'bx bx-pause-circle', 'color' => '-orange', 'note' => 'À surveiller'],
                ['label' => "CA page", 'value' => number_format($totalRevenue, 0, ',', ' ') . ' DH', 'icon' => 'bx bx-line-chart', 'color' => '-violet', 'note' => 'Sur la liste courante'],
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.agencies.form', [
            'agency' => new Branch([
                'type' => Branch::TYPE_BRANCH,
                'agency_type' => Branch::AGENCY_TYPE_INTERNAL,
                'status' => Branch::STATUS_ACTIVE,
                'currency' => 'MAD',
                'default_commission_type' => Branch::DEFAULT_COMMISSION_TYPE_PERCENTAGE,
            ]),
            'isEdit' => false,
            'managerOptions' => $this->managerOptionsForUser($request->user()),
            'agencyTypeLabels' => Branch::agencyTypeLabels(),
            'statusLabels' => Branch::statusLabels(),
        ]);
    }

    public function store(StoreAgencyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $agency = new Branch();
        $this->fillAgency($agency, $data, $request);
        $agency->save();

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', 'Point de vente créé avec succès.');
    }

    public function show(Request $request, Branch $agency): View
    {
        $this->ensureAgencyInScope($request->user(), $agency);

        $agency->load([
            'manager:id,name,email',
            'agencyEmployees.user.roles',
        ]);

        $recentReservations = $agency->reservations()
            ->with(['creator:id,name', 'agent:id,name', 'salesManager:id,name'])
            ->latest()
            ->limit(8)
            ->get();

        $employees = $agency->agencyEmployees()
            ->with(['user.roles'])
            ->orderByDesc(DB::raw("CASE WHEN status = 'active' THEN 1 ELSE 0 END"))
            ->orderBy('first_name')
            ->limit(8)
            ->get();

        $monthlySeries = $this->monthlyPerformanceSeries($agency);
        $totals = $this->agencyTotals($agency);

        return view('admin.agencies.show', [
            'agency' => $agency,
            'recentReservations' => $recentReservations,
            'employees' => $employees,
            'monthlySeries' => $monthlySeries,
            'totals' => $totals,
        ]);
    }

    public function edit(Request $request, Branch $agency): View
    {
        $this->ensureAgencyInScope($request->user(), $agency);

        return view('admin.agencies.form', [
            'agency' => $agency,
            'isEdit' => true,
            'managerOptions' => $this->managerOptionsForUser($request->user()),
            'agencyTypeLabels' => Branch::agencyTypeLabels(),
            'statusLabels' => Branch::statusLabels(),
        ]);
    }

    public function update(UpdateAgencyRequest $request, Branch $agency): RedirectResponse
    {
        $this->ensureAgencyInScope($request->user(), $agency);

        $data = $request->validated();
        $this->fillAgency($agency, $data, $request);
        $agency->save();

        return redirect()
            ->route('admin.agencies.show', $agency)
            ->with('success', 'Point de vente mis à jour.');
    }

    public function toggleStatus(Request $request, Branch $agency): RedirectResponse
    {
        $this->ensureAgencyInScope($request->user(), $agency);

        $agency->status = $agency->status === Branch::STATUS_ACTIVE
            ? Branch::STATUS_INACTIVE
            : Branch::STATUS_ACTIVE;
        $agency->is_active = $agency->status === Branch::STATUS_ACTIVE;
        $agency->save();

        return back()->with('success', 'Statut du point de vente mis à jour.');
    }

    public function destroy(Request $request, Branch $agency): RedirectResponse
    {
        $this->ensureAgencyInScope($request->user(), $agency);

        $agency->status = Branch::STATUS_INACTIVE;
        $agency->is_active = false;
        $agency->archived_at = now();
        $agency->save();

        return redirect()
            ->route('admin.agencies.index')
            ->with('success', 'Point de vente archivé.');
    }

    public function performance(Request $request): View
    {
        $user = $request->user();
        $query = $this->agencyQueryForUser($user)->withCount([
            'agencyEmployees',
            'reservations',
        ]);

        $period = trim((string) $request->query('period', '30'));
        $agencyId = (int) $request->query('agency_id', 0);
        $city = trim((string) $request->query('city', ''));
        $prestationType = trim((string) $request->query('prestation_type', ''));

        if ($agencyId > 0) {
            $query->whereKey($agencyId);
        }

        if ($city !== '') {
            $query->where('city', $city);
        }

        $agencies = $query->orderBy('name')->get();
        $fromDate = match ($period) {
            '7' => now()->subDays(7),
            '90' => now()->subDays(90),
            '365' => now()->subYear(),
            default => now()->subDays(30),
        };

        $rows = $agencies->map(function (Branch $agency) use ($fromDate, $prestationType) {
            $reservations = $agency->reservations()
                ->when($prestationType !== '', fn (Builder $builder) => $builder->where('prestation_type', $prestationType))
                ->where('created_at', '>=', $fromDate);

            $total = (clone $reservations)->count();
            $confirmed = (clone $reservations)->whereIn('status', [
                Reservation::STATUS_CONFIRMED,
                Reservation::STATUS_PAID,
                Reservation::STATUS_PARTIALLY_PAID,
            ])->count();
            $cancelled = (clone $reservations)->where('status', Reservation::STATUS_CANCELLED)->count();
            $pending = (clone $reservations)->whereIn('status', [
                Reservation::STATUS_PENDING,
                Reservation::STATUS_OPTION,
                Reservation::STATUS_SHARED_ROOM_PENDING,
                Reservation::STATUS_SHARED_ROOM_PAIRED,
            ])->count();
            $revenue = (float) (clone $reservations)
                ->selectRaw($this->revenueAggregateExpression() . ' as total')
                ->value('total');
            $estimatedCommission = $agency->default_commission_rate
                ? round($revenue * ((float) $agency->default_commission_rate / 100), 2)
                : 0.0;

            return [
                'agency' => $agency,
                'total' => $total,
                'confirmed' => $confirmed,
                'cancelled' => $cancelled,
                'pending' => $pending,
                'revenue' => $revenue,
                'estimated_commission' => $estimatedCommission,
                'conversion_rate' => $total > 0 ? round(($confirmed / $total) * 100, 1) : 0,
            ];
        });

        $topEmployees = AgencyEmployee::query()
            ->with(['branch:id,name', 'user'])
            ->whereIn('branch_id', $agencies->pluck('id')->all())
            ->get()
            ->map(function (AgencyEmployee $employee) use ($fromDate) {
                $count = 0;
                if ($employee->user_id) {
                    $count = Reservation::query()
                        ->where('created_at', '>=', $fromDate)
                        ->where(function (Builder $query) use ($employee) {
                            $query
                                ->where('agent_id', $employee->user_id)
                                ->orWhere('sales_manager_id', $employee->user_id)
                                ->orWhere('created_by', $employee->user_id);
                        })
                        ->count();
                }

                return [
                    'employee' => $employee,
                    'count' => $count,
                ];
            })
            ->sortByDesc('count')
            ->take(8)
            ->values();

        return view('admin.agencies.performance', [
            'rows' => $rows,
            'topEmployees' => $topEmployees,
            'filters' => compact('period', 'agencyId', 'city', 'prestationType'),
            'agencies' => $agencies,
            'cityOptions' => $this->cityOptionsForUser($user),
        ]);
    }

    private function agencyQueryForUser(User $user): Builder
    {
        $query = Branch::query()->notArchived();
        if (! $this->branchScope->canSeeAllBranches($user)) {
            $query->whereIn('id', $this->branchScope->visibleBranchIds($user) ?? []);
        }

        return $query;
    }

    private function ensureAgencyInScope(User $user, Branch $agency): void
    {
        $exists = $this->agencyQueryForUser($user)->whereKey($agency->id)->exists();
        abort_unless($exists, 403, 'Accès non autorisé à ce point de vente.');
    }

    private function managerOptionsForUser(User $user)
    {
        $query = User::query()->orderBy('name');
        $this->branchScope->scopeUsers($query, $user);

        return $query->get(['id', 'name', 'email', 'branch_id']);
    }

    private function cityOptionsForUser(User $user): array
    {
        return $this->agencyQueryForUser($user)
            ->whereNotNull('city')
            ->orderBy('city')
            ->pluck('city')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function countryOptionsForUser(User $user): array
    {
        return $this->agencyQueryForUser($user)
            ->whereNotNull('country')
            ->orderBy('country')
            ->pluck('country')
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function fillAgency(Branch $agency, array $data, Request $request): void
    {
        $agency->fill([
            'name' => $data['name'],
            'code' => $data['code'],
            'type' => $data['type'],
            'agency_type' => $data['agency_type'],
            'status' => $data['status'],
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'manager_user_id' => $data['manager_user_id'] ?? null,
            'default_commission_rate' => $data['default_commission_rate'] ?? null,
            'default_commission_type' => $data['default_commission_type'] ?? null,
            'default_commission_value' => $data['default_commission_value'] ?? ($data['default_commission_rate'] ?? null),
            'monthly_revenue_target' => $data['monthly_revenue_target'] ?? null,
            'monthly_reservations_target' => $data['monthly_reservations_target'] ?? null,
            'currency' => $data['currency'] ?? 'MAD',
            'business_hours' => $data['business_hours'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
            'is_active' => $data['status'] === Branch::STATUS_ACTIVE,
            'archived_at' => null,
        ]);

        if ($request->hasFile('logo')) {
            $agency->logo_path = $request->file('logo')->store('agencies/logos', 'public');
        }

        $existingDocuments = is_array($agency->documents) ? $agency->documents : [];
        $newDocuments = [];
        if ($request->hasFile('documents')) {
            foreach ((array) $request->file('documents') as $document) {
                $newDocuments[] = [
                    'name' => $document->getClientOriginalName(),
                    'path' => $document->store('agencies/documents', 'public'),
                ];
            }
        }

        $agency->documents = array_values(array_merge($existingDocuments, $newDocuments));
    }

    public function dashboard(Request $request, Branch $agency): View
    {
        $user = $request->user();
        $this->ensureAgencyInScope($user, $agency);

        $agency->loadCount(['users', 'agencyEmployees', 'reservations']);

        $reservations = Reservation::query()
            ->with(['agent.roles', 'salesManager.roles', 'tour'])
            ->where('branch_id', $agency->id)
            ->latest()
            ->limit(8)
            ->get();

        $employees = AgencyEmployee::query()
            ->with(['user.roles'])
            ->where('branch_id', $agency->id)
            ->where('status', AgencyEmployee::STATUS_ACTIVE)
            ->orderBy('first_name')
            ->limit(8)
            ->get();

        $revenueTotal = (float) Reservation::query()
            ->where('branch_id', $agency->id)
            ->sum('paid_amount');

        return view('admin.agencies.dashboard', [
            'agency' => $agency,
            'reservations' => $reservations,
            'employees' => $employees,
            'revenueTotal' => $revenueTotal,
            'pendingReservationsCount' => Reservation::query()->where('branch_id', $agency->id)->where('status', Reservation::STATUS_PENDING)->count(),
            'unassignedReservationsCount' => Reservation::query()->where('branch_id', $agency->id)->whereNull('agent_id')->count(),
        ]);
    }

    private function monthlyPerformanceSeries(Branch $agency): array
    {
        $months = [];
        $reservations = [];
        $revenue = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->locale('fr')->translatedFormat('M Y');
            $base = $agency->reservations()
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month);

            $reservations[] = (clone $base)->count();
            $revenue[] = (float) (clone $base)->selectRaw($this->revenueAggregateExpression() . ' as total')->value('total');
        }

        return [
            'labels' => $months,
            'reservations' => $reservations,
            'revenue' => $revenue,
        ];
    }

    private function agencyTotals(Branch $agency): array
    {
        $reservations = $agency->reservations();
        $revenue = (float) (clone $reservations)->selectRaw($this->revenueAggregateExpression() . ' as total')->value('total');
        $thisMonth = now()->startOfMonth();
        $clientsHandled = (clone $reservations)
            ->whereNotNull('client_external_id')
            ->distinct('client_external_id')
            ->count('client_external_id');

        return [
            'reservations_total' => (clone $reservations)->count(),
            'reservations_month' => (clone $reservations)->where('created_at', '>=', $thisMonth)->count(),
            'revenue_total' => $revenue,
            'estimated_commission' => $agency->default_commission_rate ? round($revenue * ((float) $agency->default_commission_rate / 100), 2) : 0.0,
            'employees_active' => $agency->agencyEmployees()->active()->count(),
            'clients_handled' => $clientsHandled,
        ];
    }

    private function revenueSubquery()
    {
        return Reservation::query()
            ->selectRaw($this->revenueAggregateExpression())
            ->whereColumn('reservations.branch_id', 'branches.id');
    }

    private function revenueAggregateExpression(): string
    {
        $hasBasePrice = Schema::hasColumn('reservations', 'base_price');
        $hasRoomSupplement = Schema::hasColumn('reservations', 'room_supplement_total');

        if ($hasBasePrice && $hasRoomSupplement) {
            return 'COALESCE(SUM(COALESCE(base_price, 0) + COALESCE(room_supplement_total, 0)), 0)';
        }

        if ($hasBasePrice) {
            return 'COALESCE(SUM(COALESCE(base_price, 0)), 0)';
        }

        return 'COALESCE(SUM(COALESCE(paid_amount, 0)), 0)';
    }
}
