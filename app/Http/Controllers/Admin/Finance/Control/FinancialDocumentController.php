<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Http\Requests\Admin\Finance\FinancialDocumentRequest;
use App\Models\DepartureCharge;
use App\Models\FinanceSupplier;
use App\Models\FinancialDocument;
use App\Models\ReservationPayment;
use App\Models\StructuralExpense;
use App\Services\Finance\FinanceControlService;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Centre de justificatifs.
 *
 * Deux modes de lecture :
 *  - la liste des pieces enregistrees ;
 *  - la liste des operations financieres SANS piece, avec seuil de montant configurable.
 */
class FinancialDocumentController extends FinanceControlController
{
    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::DOCUMENTS_MANAGE);

        $filters = $this->commonFilters($request);
        $documentType = $this->text($request, 'document_type');
        $view = $request->query('view') === 'missing' ? 'missing' : 'documents';
        $minAmount = max(0, (float) $request->query('min_amount', 0));

        $documents = FinancialDocument::query()
            ->with(['supplier:id,name', 'branch:id,name', 'departure:id,voyage_id,start_date', 'departure.voyage:id,name', 'creator:id,name'])
            ->when($filters['status'], fn (Builder $q, string $s) => $q->where('status', $s))
            ->when($documentType, fn (Builder $q, string $t) => $q->where('document_type', $t))
            ->when($filters['branch_id'], fn (Builder $q, int $id) => $q->where('branch_id', $id))
            ->when($filters['departure_id'], fn (Builder $q, int $id) => $q->where('departure_id', $id))
            ->when($filters['date_from'], fn (Builder $q, string $d) => $q->whereDate('document_date', '>=', $d))
            ->when($filters['date_to'], fn (Builder $q, string $d) => $q->whereDate('document_date', '<=', $d))
            ->when($filters['search'], fn (Builder $q, string $s) => $q->where('reference', 'like', '%'.$s.'%'))
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.finance.control.documents.index', [
            'documents' => $documents,
            'view' => $view,
            'minAmount' => $minAmount,
            'missing' => $view === 'missing' ? $this->missingMovements($minAmount) : collect(),
            'filters' => $filters + ['document_type' => $documentType],
            'branches' => $this->branchOptions(),
            'suppliers' => FinanceSupplier::query()->orderBy('name')->get(['id', 'name']),
            'typeLabels' => FinancialDocument::TYPE_LABELS,
            'statusLabels' => FinancialDocument::STATUS_LABELS,
        ]);
    }

    public function store(FinancialDocumentRequest $request): RedirectResponse
    {
        $document = new FinancialDocument($this->payload($request));
        $document->created_by = $request->user()->id;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $document->file_path = $file->store('finance-documents/'.now()->format('Y/m'), 'public');
            $document->file_name = $file->getClientOriginalName();
            $document->file_mime = $file->getClientMimeType();
            $document->file_size = $file->getSize();
        }

        $document->save();

        return back()->with('success', 'Justificatif enregistre.');
    }

    public function update(FinancialDocumentRequest $request, FinancialDocument $document): RedirectResponse
    {
        $document->fill($this->payload($request));
        $document->updated_by = $request->user()->id;

        if ($request->hasFile('file')) {
            if ($document->file_path) {
                Storage::disk('public')->delete($document->file_path);
            }
            $file = $request->file('file');
            $document->file_path = $file->store('finance-documents/'.now()->format('Y/m'), 'public');
            $document->file_name = $file->getClientOriginalName();
            $document->file_mime = $file->getClientMimeType();
            $document->file_size = $file->getSize();
        }

        $document->save();

        return back()->with('success', 'Justificatif mis a jour.');
    }

    /**
     * Changement de statut de controle (a controler / valide / rejete).
     */
    public function setStatus(Request $request, FinancialDocument $document): RedirectResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::DOCUMENTS_MANAGE);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(FinancialDocument::STATUS_LABELS))],
        ]);

        $document->status = $validated['status'];
        $document->updated_by = $request->user()->id;

        if ($validated['status'] === FinancialDocument::STATUS_VALIDATED) {
            $document->validated_by = $request->user()->id;
            $document->validated_at = now();
        }

        $document->save();

        return back()->with('success', 'Statut du justificatif mis a jour.');
    }

    /**
     * Telechargement controle : le fichier n'est jamais expose par une URL publique devinable.
     */
    public function download(Request $request, FinancialDocument $document): StreamedResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::DOCUMENTS_MANAGE);

        abort_unless($document->file_path && Storage::disk('public')->exists($document->file_path), 404);

        return Storage::disk('public')->download($document->file_path, $document->file_name ?: basename($document->file_path));
    }

    /**
     * Operations financieres depourvues de piece justificative.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function missingMovements(float $minAmount): \Illuminate\Support\Collection
    {
        $payments = $this->finance->paymentsMissingDocumentsQuery($minAmount)
            ->with(['reservation:id,dossier_number,client_first_name,client_last_name,branch_id,departure_id', 'reservation.branch:id,name'])
            ->orderByDesc('payment_date')
            ->limit(200)
            ->get()
            ->map(fn (ReservationPayment $payment) => [
                'kind' => 'Encaissement client',
                'date' => $payment->payment_date,
                'label' => trim(($payment->reservation?->dossier_number ?: 'RES-'.$payment->reservation_id).' - '.trim(($payment->reservation?->client_first_name ?? '').' '.($payment->reservation?->client_last_name ?? ''))),
                'amount' => (float) $payment->amount,
                'agency' => $payment->reservation?->branch?->name,
                'movement_type' => 'payment',
                'movement_id' => $payment->id,
            ]);

        $charges = $this->finance->chargesMissingDocumentsQuery($minAmount)
            ->with(['departure:id,voyage_id,start_date', 'departure.voyage:id,name', 'branch:id,name'])
            ->orderByDesc('charge_date')
            ->limit(200)
            ->get()
            ->map(fn (DepartureCharge $charge) => [
                'kind' => 'Charge voyage',
                'date' => $charge->charge_date,
                'label' => $charge->title.' - '.($charge->departure?->voyage?->name ?: 'Depart '.$charge->departure_id),
                'amount' => (float) $charge->amount,
                'agency' => $charge->branch?->name,
                'movement_type' => 'travel_expense',
                'movement_id' => $charge->id,
            ]);

        $structural = $this->finance->structuralMissingDocumentsQuery($minAmount)
            ->with('branch:id,name')
            ->orderByDesc('expense_date')
            ->limit(200)
            ->get()
            ->map(fn (StructuralExpense $expense) => [
                'kind' => 'Charge structure',
                'date' => $expense->expense_date,
                'label' => $expense->label,
                'amount' => (float) $expense->amount,
                'agency' => $expense->branch?->name,
                'movement_type' => 'structural_expense',
                'movement_id' => $expense->id,
            ]);

        return $payments->concat($charges)->concat($structural)
            ->sortByDesc(fn (array $row) => $row['date']?->timestamp ?? 0)
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(FinancialDocumentRequest $request): array
    {
        $data = $request->safe()->except(['file', 'movement_type', 'movement_id']);
        $data['currency'] = $data['currency'] ?? 'MAD';

        $movementType = $request->validated('movement_type');
        $movementId = $request->validated('movement_id');

        // Le type morph n'est jamais pris depuis la requete : il est resolu depuis une liste blanche.
        if ($movementType && $movementId) {
            $class = FinancialDocumentRequest::ALLOWED_MOVEMENTS[$movementType];
            $movement = $class::query()->find($movementId);

            if ($movement) {
                $data['documentable_type'] = $movement->getMorphClass();
                $data['documentable_id'] = $movement->getKey();
                $data = $this->inheritContext($data, $movement);
            }
        }

        return $data;
    }

    /**
     * Reprend le contexte (depart, agence, fournisseur) depuis le mouvement rattache
     * pour que le justificatif soit retrouvable depuis la fiche projet.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function inheritContext(array $data, object $movement): array
    {
        if ($movement instanceof DepartureCharge) {
            $data['departure_id'] = $data['departure_id'] ?? $movement->departure_id;
            $data['voyage_id'] = $movement->voyage_id;
            $data['branch_id'] = $data['branch_id'] ?? $movement->branch_id;
            $data['supplier_id'] = $data['supplier_id'] ?? $movement->supplier_id;
        }

        if ($movement instanceof StructuralExpense) {
            $data['branch_id'] = $data['branch_id'] ?? $movement->branch_id;
            $data['supplier_id'] = $data['supplier_id'] ?? $movement->supplier_id;
        }

        if ($movement instanceof ReservationPayment) {
            $reservation = $movement->reservation;
            $data['departure_id'] = $data['departure_id'] ?? $reservation?->departure_id;
            $data['voyage_id'] = $reservation?->voyage_id;
            $data['branch_id'] = $data['branch_id'] ?? $reservation?->branch_id;
        }

        return $data;
    }
}
