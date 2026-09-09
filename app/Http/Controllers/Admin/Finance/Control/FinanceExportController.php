<?php

namespace App\Http\Controllers\Admin\Finance\Control;

use App\Models\DepartureCharge;
use App\Models\ReservationPayment;
use App\Models\StructuralExpense;
use App\Services\Finance\FinanceControlService;
use App\Support\FinanceControlPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exports comptables au format CSV (separateur « ; », BOM UTF-8 pour Excel FR).
 *
 * Les exports sont diffuses en flux : aucun fichier temporaire, pas de montee memoire
 * sur les gros volumes (chunkById).
 */
class FinanceExportController extends FinanceControlController
{
    private const EXPORTS = ['encaissements', 'charges-voyages', 'charges-structure', 'projets'];

    public function __construct(private readonly FinanceControlService $finance)
    {
    }

    public function index(Request $request): View
    {
        $this->authorizeFinance($request, FinanceControlPermissions::REPORTING_VIEW);

        return view('admin.finance.control.exports', [
            'filters' => $this->commonFilters($request),
            'voyages' => $this->voyageOptions(),
            'branches' => $this->branchOptions(),
            'exports' => self::EXPORTS,
        ]);
    }

    public function download(Request $request, string $type): StreamedResponse
    {
        $this->authorizeFinance($request, FinanceControlPermissions::REPORTING_VIEW);
        abort_unless(in_array($type, self::EXPORTS, true), 404);

        $filters = $this->commonFilters($request);
        $filename = 'finance-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($type, $filters): void {
            $handle = fopen('php://output', 'w');
            // BOM : Excel francais interprete correctement l'UTF-8.
            fwrite($handle, "\xEF\xBB\xBF");

            match ($type) {
                'encaissements' => $this->streamCollections($handle, $filters),
                'charges-voyages' => $this->streamTravelExpenses($handle, $filters),
                'charges-structure' => $this->streamStructuralExpenses($handle, $filters),
                'projets' => $this->streamProjects($handle, $filters),
            };

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @param  resource  $handle
     */
    private function streamCollections($handle, array $filters): void
    {
        $this->row($handle, ['Date', 'Dossier', 'Client', 'Agence', 'Voyage', 'Mode', 'Montant', 'Reference', 'Justificatif']);

        ReservationPayment::query()
            ->select('reservation_payments.*')
            ->join('reservations', 'reservations.id', '=', 'reservation_payments.reservation_id')
            ->whereIn('reservations.id', $this->finance->validReservationsQuery()->select('reservations.id'))
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('reservations.branch_id', $id))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('reservation_payments.payment_date', '<=', $d))
            ->with(['reservation.branch:id,name', 'reservation.voyage:id,name'])
            ->chunkById(500, function ($payments) use ($handle): void {
                foreach ($payments as $payment) {
                    $reservation = $payment->reservation;
                    $this->row($handle, [
                        $payment->payment_date?->format('d/m/Y'),
                        $reservation?->dossier_number,
                        trim(($reservation?->client_first_name ?? '').' '.($reservation?->client_last_name ?? '')),
                        $reservation?->branch?->name,
                        $reservation?->voyage?->name,
                        $payment->payment_method,
                        number_format((float) $payment->amount, 2, ',', ''),
                        $payment->reference,
                        $payment->proof_file ? 'Oui' : 'Non',
                    ]);
                }
            }, 'reservation_payments.id', 'id');
    }

    /**
     * @param  resource  $handle
     */
    private function streamTravelExpenses($handle, array $filters): void
    {
        $this->row($handle, ['Date', 'Voyage', 'Depart', 'Categorie', 'Fournisseur', 'Libelle', 'Prevu', 'Reel', 'Paye', 'Reste', 'Statut', 'Agence', 'Facture']);

        DepartureCharge::query()
            ->with(['departure:id,voyage_id,start_date', 'departure.voyage:id,name', 'type:id,name', 'supplier:id,name', 'branch:id,name'])
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('branch_id', $id))
            ->when($filters['voyage_id'], fn ($q, $id) => $q->where('voyage_id', $id))
            ->when($filters['date_from'], fn ($q, $d) => $q->whereDate('charge_date', '>=', $d))
            ->when($filters['date_to'], fn ($q, $d) => $q->whereDate('charge_date', '<=', $d))
            ->chunkById(500, function ($charges) use ($handle): void {
                foreach ($charges as $charge) {
                    $this->row($handle, [
                        $charge->charge_date?->format('d/m/Y'),
                        $charge->departure?->voyage?->name,
                        $charge->departure?->start_date?->format('d/m/Y'),
                        $charge->type?->name,
                        $charge->supplier?->name ?: $charge->supplier_name,
                        $charge->title,
                        number_format($charge->effective_planned_amount, 2, ',', ''),
                        number_format((float) $charge->amount, 2, ',', ''),
                        number_format((float) $charge->paid_amount, 2, ',', ''),
                        number_format($charge->remaining_amount, 2, ',', ''),
                        $charge->status_label,
                        $charge->branch?->name,
                        $charge->invoice_reference,
                    ]);
                }
            });
    }

    /**
     * @param  resource  $handle
     */
    private function streamStructuralExpenses($handle, array $filters): void
    {
        $this->row($handle, ['Date', 'Agence', 'Categorie', 'Libelle', 'Fournisseur', 'Montant', 'Paye', 'Reste', 'Statut', 'Echeance', 'Recurrente']);

        StructuralExpense::query()
            ->with(['branch:id,name', 'supplier:id,name'])
            ->when($filters['branch_id'], fn ($q, $id) => $q->where('branch_id', $id))
            ->forPeriod($filters['date_from'], $filters['date_to'])
            ->chunkById(500, function ($expenses) use ($handle): void {
                foreach ($expenses as $expense) {
                    $this->row($handle, [
                        $expense->expense_date?->format('d/m/Y'),
                        $expense->branch?->name,
                        $expense->category_label,
                        $expense->label,
                        $expense->supplier?->name,
                        number_format((float) $expense->amount, 2, ',', ''),
                        number_format((float) $expense->paid_amount, 2, ',', ''),
                        number_format($expense->remaining_amount, 2, ',', ''),
                        $expense->status_label,
                        $expense->due_date?->format('d/m/Y'),
                        $expense->is_recurring ? (StructuralExpense::RECURRENCE_LABELS[$expense->recurrence] ?? 'Oui') : 'Non',
                    ]);
                }
            });
    }

    /**
     * @param  resource  $handle
     */
    private function streamProjects($handle, array $filters): void
    {
        $this->row($handle, [
            'Voyage', 'Depart', 'Dossiers', 'Voyageurs', 'CA vendu', 'Encaisse', 'Reste clients',
            'Charges prevues', 'Charges reelles', 'Charges payees', 'Reste fournisseurs',
            'Marge previsionnelle', 'Marge reelle', 'Taux de marge %',
        ]);

        $departures = $this->finance->departuresQuery($filters)->with('voyage:id,name')->get();

        foreach ($this->finance->buildProjectRows($departures, $filters) as $row) {
            $this->row($handle, [
                $row['departure']->voyage?->name,
                $row['departure']->start_date?->format('d/m/Y'),
                $row['reservations_count'],
                $row['travelers_count'],
                number_format($row['sold_amount'], 2, ',', ''),
                number_format($row['collected_amount'], 2, ',', ''),
                number_format($row['client_remaining'], 2, ',', ''),
                number_format($row['planned_charges'], 2, ',', ''),
                number_format($row['real_charges'], 2, ',', ''),
                number_format($row['paid_charges'], 2, ',', ''),
                number_format($row['supplier_remaining'], 2, ',', ''),
                number_format($row['planned_margin'], 2, ',', ''),
                number_format($row['real_margin'], 2, ',', ''),
                number_format($row['margin_rate'], 2, ',', ''),
            ]);
        }
    }

    /**
     * @param  resource  $handle
     * @param  list<mixed>  $values
     */
    private function row($handle, array $values): void
    {
        fputcsv($handle, array_map(static fn ($value) => (string) ($value ?? ''), $values), ';');
    }
}
