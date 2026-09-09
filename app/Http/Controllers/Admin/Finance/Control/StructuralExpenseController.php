<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Http\Requests\Admin\Finance\StructuralExpenseRequest;
use App\Models\FinanceSupplier;
use App\Models\FinancialDocument;
use App\Models\StructuralExpense;
use App\Services\Finance\StructuralExpenseRecurrenceService;
use App\Support\FinanceControlPermissions;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Charges de structure de l'agence : totalement disjointes des charges de voyage.
 *
 * Elles n'entrent jamais dans la marge d'un projet, uniquement dans le resultat de gestion.
 */
class StructuralExpenseController extends FinanceControlController
{
    public function __construct(private readonly StructuralExpenseRecurrenceService $recurrences)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::STRUCTURAL_EXPENSES_MANAGE);

        $filters = $this->commonFilters($request);
        $category = $this->text($request, 'category');

        $expenses = StructuralExpense::query()
            ->with(['branch:id,name', 'supplier:id,name', 'creator:id,name'])
            ->when($filters['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($category, fn (Builder $q, string $c) => $q->where('category', $c))
            ->when($filters['search'], fn (Builder $q, string $s) => $q->where('label', 'like', '%'.$s.'%'))
            ->forPeriod($filters['date_from'], $filters['date_to'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        $totals = StructuralExpense::query()
            ->accountable()
            ->when($filters['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($category, fn (Builder $q, string $c) => $q->where('category', $c))
            ->forPeriod($filters['date_from'], $filters['date_to'])
            ->selectRaw('SUM(amount) as amount, SUM(COALESCE(paid_amount, 0)) as paid')
            ->first();

        return view('admin.finance.control.structural-expenses.index', [
            'expenses' => $expenses,
            'filters' => $filters + ['category' => $category],
            'branches' => $this->branchOptions(),
            'categories' => StructuralExpense::CATEGORY_LABELS,
            'statusLabels' => StructuralExpense::STATUS_LABELS,
            'totalAmount' => round((float) ($totals->amount ?? 0), 2),
            'totalPaid' => round((float) ($totals->paid ?? 0), 2),
            'pendingRecurrences' => $this->recurrences->pendingOccurrences()->count(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::STRUCTURAL_EXPENSES_MANAGE);

        return $this->form(new StructuralExpense([
            'currency' => 'MAD',
            'status' => StructuralExpense::STATUS_PLANNED,
            'expense_date' => now()->toDateString(),
            'paid_amount' => 0,
            'is_recurring' => false,
        ]), 'create');
    }

    public function store(StructuralExpenseRequest $request): RedirectResponse
    {
        $expense = new StructuralExpense($this->payload($request));
        $expense->created_by = $request->user()->id;
        $expense->save();

        $this->attachDocument($request, $expense);

        return redirect()
            ->route('admin.finance.control.structural-expenses.index')
            ->with('success', 'Charge de structure enregistree.');
    }

    public function edit(Request $request, StructuralExpense $structural_expense): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::STRUCTURAL_EXPENSES_MANAGE);

        return $this->form($structural_expense, 'edit');
    }

    public function update(StructuralExpenseRequest $request, StructuralExpense $structural_expense): RedirectResponse
    {
        $structural_expense->fill($this->payload($request));
        $structural_expense->updated_by = $request->user()->id;
        $structural_expense->save();

        $this->attachDocument($request, $structural_expense);

        return redirect()
            ->route('admin.finance.control.structural-expenses.index')
            ->with('success', 'Charge de structure mise a jour.');
    }

    /** Annulation plutot que suppression : l'historique financier est preserve. */
    public function cancel(Request $request, StructuralExpense $structural_expense): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::STRUCTURAL_EXPENSES_MANAGE);

        $structural_expense->status = StructuralExpense::STATUS_CANCELLED;
        $structural_expense->updated_by = $request->user()->id;
        $structural_expense->save();

        return back()->with('success', 'Charge annulee. Elle reste consultable dans l\'historique.');
    }

    /**
     * Previsualisation des occurrences recurrentes manquantes.
     */
    public function recurrences(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::STRUCTURAL_EXPENSES_MANAGE);

        $until = $this->date($request, 'until');

        return view('admin.finance.control.structural-expenses.recurrences', [
            'templates' => $this->recurrences->templates(),
            'pending' => $this->recurrences->pendingOccurrences($until ? CarbonImmutable::parse($until) : null),
            'until' => $until ?: CarbonImmutable::now()->endOfMonth()->toDateString(),
        ]);
    }

    /**
     * Generation confirmee des occurrences. Idempotente : aucun doublon possible.
     */
    public function generateRecurrences(Request $request): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::STRUCTURAL_EXPENSES_MANAGE);

        $validated = $request->validate([
            'until' => ['nullable', 'date'],
        ]);

        $created = $this->recurrences->generate(
            isset($validated['until']) ? CarbonImmutable::parse($validated['until']) : null,
            (int) $request->user()->id
        );

        return redirect()
            ->route('admin.finance.control.structural-expenses.recurrences')
            ->with('success', $created > 0
                ? $created.' charge(s) recurrente(s) generee(s).'
                : 'Aucune nouvelle occurrence a generer : tout est deja a jour.');
    }

    private function form(StructuralExpense $expense, string $mode): View
    {
        return view('admin.finance.control.structural-expenses.form', [
            'expense' => $expense,
            'mode' => $mode,
            'branches' => $this->branchOptions(),
            'suppliers' => FinanceSupplier::query()->active()->orderBy('name')->get(['id', 'name']),
            'categories' => StructuralExpense::CATEGORY_LABELS,
            'statusLabels' => StructuralExpense::STATUS_LABELS,
            'recurrenceLabels' => StructuralExpense::RECURRENCE_LABELS,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(StructuralExpenseRequest $request): array
    {
        $data = $request->safe()->except('document');
        $data['currency'] = $data['currency'] ?: 'MAD';
        $data['paid_amount'] = (float) ($data['paid_amount'] ?? 0);
        $data['is_recurring'] = (bool) ($data['is_recurring'] ?? false);

        if (! $data['is_recurring']) {
            $data['recurrence'] = null;
            $data['recurrence_until'] = null;
        }

        return $data;
    }

    /**
     * Le justificatif joint au formulaire alimente directement le centre de justificatifs :
     * une seule saisie, une seule source de verite.
     */
    private function attachDocument(StructuralExpenseRequest $request, StructuralExpense $expense): void
    {
        if (! $request->hasFile('document')) {
            return;
        }

        $file = $request->file('document');
        $path = $file->store('finance-documents/'.now()->format('Y/m'), 'public');

        FinancialDocument::query()->create([
            'document_type' => 'facture_fournisseur',
            'status' => FinancialDocument::STATUS_TO_CHECK,
            'document_date' => $expense->expense_date,
            'amount' => $expense->amount,
            'currency' => $expense->currency,
            'documentable_type' => $expense->getMorphClass(),
            'documentable_id' => $expense->getKey(),
            'branch_id' => $expense->branch_id,
            'supplier_id' => $expense->supplier_id,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_mime' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'created_by' => $request->user()->id,
        ]);
    }
}
