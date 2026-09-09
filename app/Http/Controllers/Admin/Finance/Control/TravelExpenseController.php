<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Http\Requests\Admin\Finance\TravelExpenseRequest;
use App\Models\ChargeType;
use App\Models\Departure;
use App\Models\DepartureCharge;
use App\Models\FinanceSupplier;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Charges des projets de voyage.
 *
 * S'appuie sur la table existante `departure_charges` et sur le referentiel
 * existant `charge_types` : aucune table de charges parallele n'est creee.
 */
class TravelExpenseController extends FinanceControlController
{
    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::PROJECTS_VIEW);

        $filters = $this->commonFilters($request);
        $chargeTypeId = $this->id($request, 'charge_type_id');
        $supplierId = $this->id($request, 'supplier_id');

        $charges = DepartureCharge::query()
            ->with(['departure:id,voyage_id,start_date', 'departure.voyage:id,name', 'type:id,name', 'supplier:id,name', 'branch:id,name'])
            ->when($filters['departure_id'], fn (Builder $q, int $id) => $q->where('departure_id', $id))
            ->when($filters['voyage_id'], fn (Builder $q, int $id) => $q->where('voyage_id', $id))
            ->when($filters['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->when($filters['status'], fn (Builder $q, string $status) => $q->where('status', $status))
            ->when($chargeTypeId, fn (Builder $q, int $id) => $q->where('charge_type_id', $id))
            ->when($supplierId, fn (Builder $q, int $id) => $q->where('supplier_id', $id))
            ->when($filters['date_from'], fn (Builder $q, string $d) => $q->whereDate('charge_date', '>=', $d))
            ->when($filters['date_to'], fn (Builder $q, string $d) => $q->whereDate('charge_date', '<=', $d))
            ->when($filters['search'], fn (Builder $q, string $s) => $q->where('title', 'like', '%'.$s.'%'))
            ->orderByDesc('charge_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.finance.control.travel-expenses.index', [
            'charges' => $charges,
            'filters' => $filters + ['charge_type_id' => $chargeTypeId, 'supplier_id' => $supplierId],
            'voyages' => $this->voyageOptions(),
            'branches' => $this->branchOptions(),
            'chargeTypes' => ChargeType::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'suppliers' => FinanceSupplier::query()->orderBy('name')->get(['id', 'name']),
            'statusLabels' => DepartureCharge::STATUS_LABELS,
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        return $this->form($request, new DepartureCharge([
            'departure_id' => $this->id($request, 'departure_id'),
            'currency' => 'MAD',
            'payment_method' => 'autre',
            'status' => DepartureCharge::STATUS_PLANNED,
            'charge_date' => now()->toDateString(),
            'paid_amount' => 0,
        ]), 'create');
    }

    public function store(TravelExpenseRequest $request): RedirectResponse
    {
        $departure = Departure::query()->findOrFail($request->validated('departure_id'));

        $charge = new DepartureCharge($this->payload($request, $departure));
        $charge->created_by = $request->user()->id;

        if ($request->hasFile('attachment')) {
            $charge->attachment = $request->file('attachment')->store('departure-charges/'.now()->format('Y/m'), 'public');
        }

        $charge->syncLegacyPaymentStatus();
        $charge->save();

        return redirect()
            ->route('admin.finance.control.travel-projects.show', [$departure, 'tab' => 'charges'])
            ->with('success', 'Charge ajoutee au projet.');
    }

    public function edit(Request $request, DepartureCharge $travel_expense): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        return $this->form($request, $travel_expense, 'edit');
    }

    public function update(TravelExpenseRequest $request, DepartureCharge $travel_expense): RedirectResponse
    {
        $departure = Departure::query()->findOrFail($request->validated('departure_id'));

        $travel_expense->fill($this->payload($request, $departure));
        $travel_expense->updated_by = $request->user()->id;

        if ($request->hasFile('attachment')) {
            if ($travel_expense->attachment) {
                Storage::disk('public')->delete($travel_expense->attachment);
            }
            $travel_expense->attachment = $request->file('attachment')->store('departure-charges/'.now()->format('Y/m'), 'public');
        }

        $travel_expense->syncLegacyPaymentStatus();
        $travel_expense->save();

        return redirect()
            ->route('admin.finance.control.travel-projects.show', [$departure, 'tab' => 'charges'])
            ->with('success', 'Charge mise a jour.');
    }

    /**
     * Annulation et non suppression : l'historique du mouvement financier est conserve.
     * La charge annulee sort de tous les totaux via le scope `accountable`.
     */
    public function cancel(Request $request, DepartureCharge $travel_expense): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        $travel_expense->status = DepartureCharge::STATUS_CANCELLED;
        $travel_expense->updated_by = $request->user()->id;
        $travel_expense->syncLegacyPaymentStatus();
        $travel_expense->save();

        return back()->with('success', 'Charge annulee. Elle reste consultable dans l\'historique.');
    }

    /**
     * Validation par un responsable : trace validated_by / validated_at.
     */
    public function validateCharge(Request $request, DepartureCharge $travel_expense): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        $travel_expense->validated_by = $request->user()->id;
        $travel_expense->validated_at = now();
        $travel_expense->save();

        return back()->with('success', 'Charge validee.');
    }

    private function form(Request $request, DepartureCharge $charge, string $mode): View
    {
        return view('admin.finance.control.travel-expenses.form', [
            'charge' => $charge,
            'mode' => $mode,
            'departures' => Departure::query()
                ->with('voyage:id,name')
                ->orderByDesc('start_date')
                ->limit(500)
                ->get(['id', 'voyage_id', 'start_date']),
            'chargeTypes' => ChargeType::query()->active()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'suppliers' => FinanceSupplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'branches' => $this->branchOptions(),
            'statusLabels' => DepartureCharge::STATUS_LABELS,
            'paymentMethods' => DepartureCharge::PAYMENT_METHODS,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(TravelExpenseRequest $request, Departure $departure): array
    {
        $data = $request->safe()->except('attachment');
        $data['voyage_id'] = $departure->voyage_id;
        $data['currency'] = $data['currency'] ?: 'MAD';
        $data['paid_amount'] = (float) ($data['paid_amount'] ?? 0);
        $data['planned_amount'] = $data['planned_amount'] !== null && $data['planned_amount'] !== ''
            ? (float) $data['planned_amount']
            : (float) $data['amount'];

        return $data;
    }
}
