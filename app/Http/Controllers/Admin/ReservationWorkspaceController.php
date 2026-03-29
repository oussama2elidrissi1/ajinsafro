<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Reservation;
use App\Models\ReservationExtra;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Services\BranchScopeService;
use App\Services\ReservationListQueryService;
use App\Services\ReservationService;
use App\Services\ReservationWorkspaceBookingService;
use App\Services\ReservationWorkspaceCatalogService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReservationWorkspaceController extends Controller
{
    public function __construct(
        protected ReservationService $reservationService,
        protected BranchScopeService $branchScope,
        protected ReservationWorkspaceCatalogService $catalog,
        protected ReservationWorkspaceBookingService $workspaceBooking,
        protected ReservationListQueryService $reservationListQuery,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeWorkspace($request);

        $catalog = $this->catalog->buildRows($request->user());
        $rows = $catalog['rows'];
        $catalogMeta = $catalog['meta'];

        $clientsQuery = Client::query()->orderByDesc('id')->limit(300);
        $this->branchScope->scopeClients($clientsQuery, $request->user());
        $clients = $clientsQuery->get(['id', 'client_code', 'full_name', 'email', 'phone']);

        return view('admin.reservations.workspace.index', [
            'catalogRows' => $rows,
            'catalogMeta' => $catalogMeta,
            'catalogPackageCount' => (int) ($catalogMeta['wp_tour_count'] ?? $rows->where('type', 'package')->count()),
            'catalogTotalCount' => $rows->count(),
            'clients' => $clients,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Même périmètre que les routes admin.reservations.* (middleware → reservations.view).
        abort_unless($request->user()->can('reservations.view'), 403);
        $request->validate([
            'prestation_type' => 'required|in:package,vol,hebergement',
            'tour_id' => 'required|integer|exists:voyages,id',
            'travel_date_id' => 'nullable|integer',
            'client_mode' => 'required|in:existing,new',
            'client_external_id' => 'required_if:client_mode,existing|nullable|integer|exists:clients,id',
            'titulaire_type' => 'required|in:adulte,enfant,bebe',
            'titulaire_civilite' => 'nullable|string|max:20',
            'titulaire_nom' => 'required|string|max:100',
            'titulaire_prenom' => 'required|string|max:100',
            'titulaire_dob' => 'nullable|date',
            'titulaire_document' => 'required|string|max:100',
            'titulaire_nationalite' => 'nullable|string|max:10',
            'titulaire_doc_expires' => 'nullable|date',
            'titulaire_phone' => 'required|string|max:50',
            'titulaire_email' => 'nullable|email|max:190',
            'montant_total' => 'required|numeric|min:0',
            'montant_paye' => 'required|numeric|min:0',
            'payment_mode' => 'nullable|string|max:80',
            'extras_json' => 'nullable|string',
            'passengers_json' => 'nullable|string',
            'workspace_documents' => 'nullable|array',
            'workspace_documents.*' => 'file|max:10240',
            'workspace_notes' => 'nullable|string|max:5000',
            'vol_rbd' => 'nullable|string|max:10',
            'vol_tarif_type' => 'nullable|string|max:80',
            'vol_ff_number' => 'nullable|string|max:80',
            'package_room_type' => 'nullable|string|max:120',
            'package_remarks' => 'nullable|string|max:2000',
            'hotel_room_type' => 'nullable|string|max:80',
            'hotel_pension' => 'nullable|string|max:80',
            'hotel_remarks' => 'nullable|string|max:2000',
        ]);

        $user = $request->user();
        $extrasPayload = [];
        if ($request->filled('extras_json')) {
            $decoded = json_decode($request->string('extras_json')->toString(), true);
            $extrasPayload = is_array($decoded) ? $decoded : [];
        }

        $companions = [];
        if ($request->filled('passengers_json')) {
            $decoded = json_decode($request->string('passengers_json')->toString(), true);
            $companions = is_array($decoded) ? $decoded : [];
        }

        $passengers = [];
        $passengers[] = [
            'first_name' => $request->string('titulaire_prenom')->toString(),
            'last_name' => $request->string('titulaire_nom')->toString(),
            'type' => $this->mapPaxType($request->string('titulaire_type')->toString()),
            'birth_date' => $request->input('titulaire_dob'),
            'document_type' => 'passport',
            'document_number' => $request->string('titulaire_document')->toString(),
        ];

        foreach ($companions as $row) {
            if (! is_array($row)) {
                continue;
            }
            $fn = trim((string) ($row['first_name'] ?? ''));
            $ln = trim((string) ($row['last_name'] ?? ''));
            if ($fn === '' && $ln === '') {
                continue;
            }
            $passengers[] = [
                'first_name' => $fn,
                'last_name' => $ln,
                'type' => $this->mapPaxType((string) ($row['type'] ?? 'adulte')),
                'birth_date' => $row['birth_date'] ?? null,
                'document_type' => 'passport',
                'document_number' => $row['document_number'] ?? null,
            ];
        }

        $bookingResolve = $this->workspaceBooking->validateWorkspaceStoreAndResolveTotals(
            $request,
            $user,
            $passengers,
            $extrasPayload,
        );

        $docPaths = [];
        foreach ($request->file('workspace_documents', []) as $file) {
            if ($file && $file->isValid()) {
                $docPaths[] = $file->store('reservation-workspace/'.date('Y/m'), 'public');
            }
        }

        $workspaceMeta = [
            'prestation_type' => $request->string('prestation_type')->toString(),
            'civilite' => $request->input('titulaire_civilite'),
            'nationalite' => $request->input('titulaire_nationalite'),
            'doc_expires' => $request->input('titulaire_doc_expires'),
            'payment_mode_label' => $request->input('payment_mode'),
            'vol' => [
                'rbd' => $request->input('vol_rbd'),
                'tarif' => $request->input('vol_tarif_type'),
                'ff' => $request->input('vol_ff_number'),
            ],
            'package' => [
                'room' => $request->input('package_room_type'),
                'remarks' => $request->input('package_remarks'),
            ],
            'hotel' => [
                'room' => $request->input('hotel_room_type'),
                'pension' => $request->input('hotel_pension'),
                'remarks' => $request->input('hotel_remarks'),
            ],
            'documents' => $docPaths,
            'booking_snapshot' => $bookingResolve['booking_snapshot'],
        ];

        $notes = trim((string) $request->input('workspace_notes', ''));
        $notes .= ($notes !== '' ? "\n\n" : '').'<!--WORKSPACE_META-->'.json_encode($workspaceMeta, JSON_UNESCAPED_UNICODE);

        $paymentType = $this->mapPaymentType($request->input('payment_mode'));

        $data = [
            'tour_id' => (int) $request->input('tour_id'),
            'travel_date_id' => $bookingResolve['resolved_travel_date_id'] ?? null,
            'prestation_type' => $request->string('prestation_type')->toString(),
            'client_mode' => $request->string('client_mode')->toString(),
            'client_external_id' => $request->string('client_mode')->toString() === 'existing'
                ? (int) $request->input('client_external_id')
                : null,
            'client_first_name' => $request->string('titulaire_prenom')->toString(),
            'client_last_name' => $request->string('titulaire_nom')->toString(),
            'client_email' => $request->input('titulaire_email'),
            'client_phone' => $request->string('titulaire_phone')->toString(),
            'client_document_type' => 'passport',
            'client_document_number' => $request->string('titulaire_document')->toString(),
            'payment_type' => $paymentType,
            'status' => Reservation::STATUS_EN_COURS,
            'base_price' => $bookingResolve['authoritative_total'],
            'paid_amount' => (float) $request->input('montant_paye'),
            'notes' => $notes,
            'passengers' => $passengers,
            'branch_id' => $user->branch_id,
            'agent_id' => $user->id,
            'created_by' => $user->id,
            'hotel_rooms' => [],
        ];

        if ($request->string('client_mode')->toString() === 'new') {
            $data['client_external_id'] = null;
        }

        $reservation = $this->reservationService->create($data, null, null);

        foreach ($extrasPayload as $extra) {
            if (! is_array($extra)) {
                continue;
            }
            $name = trim((string) ($extra['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            ReservationExtra::query()->create([
                'reservation_id' => $reservation->id,
                'name' => $name,
                'price' => isset($extra['price']) ? (float) $extra['price'] : 0,
                'passenger_key' => isset($extra['pax']) ? (string) $extra['pax'] : null,
            ]);
        }

        return redirect()->route('admin.reservations.index', array_filter([
            'voyage_id' => (int) $request->input('tour_id'),
            'travel_date_id' => $bookingResolve['resolved_travel_date_id'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''))->with('success', 'Réservation enregistrée.');
    }

    public function prestationParticipants(Request $request): RedirectResponse
    {
        $this->authorizeWorkspace($request);
        $request->validate([
            'voyage_id' => 'required|integer|exists:voyages,id',
            'travel_date_id' => 'nullable|integer',
        ]);

        return redirect()->route('admin.reservations.index', array_filter([
            'voyage_id' => (int) $request->query('voyage_id'),
            'travel_date_id' => $request->filled('travel_date_id') ? (int) $request->query('travel_date_id') : null,
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function prestationPdf(Request $request): Response
    {
        $this->authorizeWorkspace($request);
        $request->validate([
            'voyage_id' => 'required|integer|exists:voyages,id',
            'travel_date_id' => 'nullable|integer',
        ]);

        $voyage = Voyage::findOrFail((int) $request->query('voyage_id'));
        $travelDateId = $request->filled('travel_date_id') ? (int) $request->query('travel_date_id') : null;

        $q = $this->reservationListQuery->baseQuery($request->user())
            ->where('tour_id', $voyage->id)
            ->with(['passengers', 'client:id,client_code,full_name', 'travelDate']);
        $this->reservationListQuery->applyTravelDateFilter($q, $travelDateId);
        $reservations = $q->orderByDesc('created_at')->limit(500)->get();

        $wpTourTitle = $this->resolveWpTourTitle($voyage->wp_post_id);
        $prestationDisplayTitle = $wpTourTitle ?? $voyage->name;
        $travelDateLabel = $this->resolveTravelDateLabel($travelDateId);

        $pdf = Pdf::loadView('admin.reservations.workspace.pdf.prestation', [
            'voyage' => $voyage,
            'travelDateId' => $travelDateId,
            'travelDateLabel' => $travelDateLabel,
            'wpTourTitle' => $wpTourTitle,
            'prestationDisplayTitle' => $prestationDisplayTitle,
            'reservations' => $reservations,
            'generatedAt' => now(),
        ]);

        $filename = 'fiche-prestation-voyage-'.$voyage->id.($travelDateId ? '-td'.$travelDateId : '').'.pdf';

        return $pdf->download($filename);
    }

    public function reservationPdf(Request $request, Reservation $reservation): Response
    {
        $this->authorizeWorkspace($request);
        $this->assertReservationVisible($request, $reservation);

        $reservation->load(['passengers', 'client', 'tour', 'travelDate', 'extras']);

        $pdf = Pdf::loadView('admin.reservations.workspace.pdf.reservation', [
            'reservation' => $reservation,
            'generatedAt' => now(),
        ]);

        return $pdf->download('reservation-'.$reservation->id.'.pdf');
    }

    private function resolveWpTourTitle(mixed $wpPostId): ?string
    {
        $id = $wpPostId !== null && $wpPostId !== '' ? (int) $wpPostId : 0;
        if ($id <= 0) {
            return null;
        }
        try {
            $title = WpPost::query()->tours()->where('ID', $id)->value('post_title');
            $title = $title !== null ? trim((string) $title) : '';

            return $title !== '' ? $title : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveTravelDateLabel(?int $travelDateId): ?string
    {
        if (! $travelDateId) {
            return null;
        }
        $td = TravelDate::query()->find($travelDateId);
        if (! $td || ! $td->date) {
            return null;
        }

        return $td->date->format('d/m/Y');
    }

    private function authorizeWorkspace(Request $request): void
    {
        abort_unless($request->user()->can('reservations.view'), 403);
    }

    private function assertReservationVisible(Request $request, Reservation $reservation): void
    {
        $q = Reservation::query()->whereKey($reservation->getKey());
        $this->branchScope->scopeReservations($q, $request->user());
        $this->branchScope->constrainReservationQueryForPortalUser($q, $request->user());
        if (! $q->exists()) {
            abort(404);
        }
    }

    private function mapPaxType(string $t): string
    {
        return match ($t) {
            'enfant' => 'child',
            'bebe' => 'infant',
            default => 'adult',
        };
    }

    private function mapPaymentType(?string $label): ?string
    {
        if ($label === null || $label === '') {
            return null;
        }
        $l = mb_strtolower($label);

        return match (true) {
            str_contains($l, 'virement') => Reservation::PAYMENT_VIREMENT,
            str_contains($l, 'espèce') || str_contains($l, 'espece') => Reservation::PAYMENT_ESPECE,
            str_contains($l, 'cash') || str_contains($l, 'cashplus') => Reservation::PAYMENT_CASHPLUS,
            default => Reservation::PAYMENT_ESPECE,
        };
    }
}
