<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Models\DepartureCharge;
use App\Models\FinanceSupplier;
use App\Models\StructuralExpense;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Referentiel fournisseurs et encours par fournisseur.
 */
class FinanceSupplierController extends FinanceControlController
{
    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        $filters = $this->commonFilters($request);

        $suppliers = FinanceSupplier::query()
            ->when($filters['search'], fn (Builder $q, string $s) => $q->where('name', 'like', '%'.$s.'%'))
            ->withSum(['charges as billed_amount' => fn (Builder $q) => $q->accountable()], 'amount')
            ->withSum(['charges as paid_amount_total' => fn (Builder $q) => $q->accountable()], 'paid_amount')
            ->withCount(['charges as charges_count' => fn (Builder $q) => $q->accountable()])
            ->withSum(['structuralExpenses as structural_amount' => fn (Builder $q) => $q->accountable()], 'amount')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('admin.finance.control.suppliers.index', [
            'suppliers' => $suppliers,
            'filters' => $filters,
            'typeLabels' => FinanceSupplier::TYPE_LABELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        $validated = $request->validate($this->rules());
        $validated['created_by'] = $request->user()->id;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);

        FinanceSupplier::query()->create($validated);

        return back()->with('success', 'Fournisseur ajoute.');
    }

    public function update(Request $request, FinanceSupplier $supplier): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        $validated = $request->validate($this->rules());
        $validated['updated_by'] = $request->user()->id;
        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);

        $supplier->update($validated);

        return back()->with('success', 'Fournisseur mis a jour.');
    }

    /**
     * Desactivation plutot que suppression des lors que le fournisseur porte des mouvements.
     */
    public function destroy(Request $request, FinanceSupplier $supplier): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::EXPENSES_MANAGE);

        $hasMovements = DepartureCharge::query()->where('supplier_id', $supplier->id)->exists()
            || StructuralExpense::query()->where('supplier_id', $supplier->id)->exists();

        if ($hasMovements) {
            $supplier->update(['is_active' => false, 'updated_by' => $request->user()->id]);

            return back()->with('success', 'Fournisseur desactive : des mouvements financiers y sont rattaches.');
        }

        $supplier->delete();

        return back()->with('success', 'Fournisseur supprime.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:190'],
            'type' => ['required', Rule::in(array_keys(FinanceSupplier::TYPE_LABELS))],
            'contact_name' => ['nullable', 'string', 'max:190'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
