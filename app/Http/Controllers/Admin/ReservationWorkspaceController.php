<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\TravelDate;
use App\Models\Voyage;
use App\Models\Wp\WpPost;
use App\Services\BranchScopeService;
use App\Services\ReservationListQueryService;
use App\Services\ReservationService;
use App\Services\ReservationVisibilityService;
use App\Services\ReservationDossierService;
use App\Services\ReservationWorkspaceBookingService;
use App\Services\ReservationWorkspaceCatalogService;
use App\Services\ReservationWorkspaceCommercialService;
use App\Models\Setting;
use App\Support\AdminReservationFlash;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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
        protected ReservationVisibilityService $reservationVisibility,
        protected ReservationDossierService $reservationDossier,
        protected ReservationWorkspaceCommercialService $commercial,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeWorkspace($request);
        $workspaceView = $this->normalizeWorkspaceView($request->query('view'));
        if (! $request->filled('view') && $request->user()?->hasRole('commercial_reservations_only')) {
            $workspaceView = 'catalog';
        }
        $workspaceFilters = $this->normalizeWorkspaceFilters($request);

        $catalog = $this->catalog->buildRows($request->user());
        $allRows = $catalog['rows'];
        $catalogMeta = $catalog['meta'];

        $enriched = $this->commercial->enrichRows($allRows, $request->user());
        $allRows = $enriched['rows'];
        $commercialKpis = $enriched['kpis'];
        $commercialAssistant = $enriched['assistant'];

        $catalogParam = $request->query('catalog');
        if (is_string($catalogParam) && $catalogParam !== '' && in_array($catalogParam, ['upcoming', 'past', 'none'], true)) {
            $catalogScope = $catalogParam;
            $scoped = $this->catalog->scopeCatalogRows($allRows, $catalogScope);
        } elseif ($catalogParam === 'all') {
            $catalogScope = 'all';
            $scoped = $allRows;
        } else {
            $catalogScope = 'all';
            $scoped = $allRows;
        }

        $workspaceFilterOptions = [
            'destinations' => $this->workspaceDestinationOptions($scoped),
        ];

        $rows = $this->catalog->filterWorkspaceRows($scoped, $workspaceFilters);

        $sort = $request->query('sort');
        $direction = strtolower(trim((string) $request->query('direction', 'asc')));
        $allowedSorts = ['ref', 'voyage', 'destination', 'departure_date', 'sold_pending', 'remaining', 'capacity'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'departure_date';
            $direction = 'asc';
        }
        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }
        $rows = $this->catalog->sortCatalogRowsForWorkspaceDisplay($rows, $sort, $direction);
        $sellableRows = $rows
            ->filter(fn (array $row): bool => $this->isWorkspaceRowSellableForList($row))
            ->values();

        $wsModalSettings = [
            'show_commission' => Setting::getValue('ws_modal_show_commission', '1') === '1',
            'show_commission_type' => Setting::getValue('ws_modal_show_commission_type', '1') === '1',
            'show_commission_amount' => Setting::getValue('ws_modal_show_commission_amount', '1') === '1',
            'show_commission_percentage' => Setting::getValue('ws_modal_show_commission_percentage', '1') === '1',
            'show_commission_fixed' => Setting::getValue('ws_modal_show_commission_fixed', '1') === '1',
            'show_commission_agent' => Setting::getValue('ws_modal_show_commission_agent', '1') === '1',
            'show_commission_branch' => Setting::getValue('ws_modal_show_commission_branch', '1') === '1',
            'show_commission_help' => Setting::getValue('ws_modal_show_commission_help', '1') === '1',
            'show_departure_report' => Setting::getValue('ws_modal_show_departure_report', '1') === '1',
        ];

        return view('admin.reservations.workspace.index', [
            'catalogRows' => $rows,
            'workspaceSellableRows' => $sellableRows,
            'catalogMeta' => $catalogMeta,
            'catalogScope' => $catalogScope,
            'catalogFullCount' => $allRows->count(),
            'catalogPackageCount' => (int) ($catalogMeta['wp_tour_count'] ?? $allRows->where('type', 'package')->count()),
            'catalogTotalCount' => $rows->count(),
            'commercialKpis' => $commercialKpis,
            'commercialAssistant' => $commercialAssistant,
            'wsModalSettings' => $wsModalSettings,
            'workspaceView' => $workspaceView,
            'workspaceFilters' => $workspaceFilters,
            'workspaceFilterOptions' => $workspaceFilterOptions,
            'workspaceResetUrl' => route('admin.reservations.workspace', ['view' => $workspaceView]),
            'currentSort' => $sort,
            'currentDirection' => $direction,
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

        try {
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

            $voyageForMeta = Voyage::query()->findOrFail((int) $request->input('tour_id'));
            $catalogRow = $this->catalog->findCatalogRowForBooking(
                $voyageForMeta,
                $request->string('prestation_type')->toString(),
                $user
            );
            $catalogRow = is_array($catalogRow) ? $catalogRow : [];

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

            $clientIdForOwnership = $request->string('client_mode')->toString() === 'existing'
                ? (int) $request->input('client_external_id')
                : null;
            $ownership = $this->branchScope->defaultReservationOwnership($user, $clientIdForOwnership ?: null);

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
                'dossier_status' => Reservation::DOSSIER_PENDING,
                'base_price' => $bookingResolve['base_total'],
                'total_base' => $bookingResolve['base_total'],
                'room_supplement_total' => $bookingResolve['room_supplement_total'],
                'extras_total' => $bookingResolve['extras_total'],
                'total_amount' => $bookingResolve['authoritative_total'],
                'paid_amount' => (float) $request->input('montant_paye'),
                'remaining_amount' => max(0, (float) $bookingResolve['authoritative_total'] - (float) $request->input('montant_paye')),
                'payment_status' => $this->reservationDossier->derivePaymentStatus((float) $bookingResolve['authoritative_total'], (float) $request->input('montant_paye')),
                'notes' => $notes,
                'passengers' => $passengers,
                'branch_id' => $ownership['branch_id'],
                'sales_manager_id' => $ownership['sales_manager_id'],
                'agent_id' => $user->id,
                'assigned_to' => $user->id,
                'created_by' => $user->id,
                'created_by_user_id' => $user->id,
                'hotel_rooms' => [],
                'extras_payload' => $extrasPayload,
                'payment_payload' => (float) $request->input('montant_paye') > 0 ? [
                    'payment_date' => now()->toDateString(),
                    'payment_method' => $request->input('payment_mode') ?: 'Autre',
                    'amount' => (float) $request->input('montant_paye'),
                    'reference' => null,
                    'note' => 'Paiement initial workspace',
                    'created_by' => $user->id,
                ] : null,
                'wp_tour_post_id' => $voyageForMeta->wp_post_id ? (int) $voyageForMeta->wp_post_id : null,
                'catalog_source_code' => $catalogRow['code'] ?? null,
                'voyage_flight_id' => isset($catalogRow['flight_id']) ? (int) $catalogRow['flight_id'] : null,
            ];

            if ($request->string('client_mode')->toString() === 'new') {
                $data['client_external_id'] = null;
            }

            $reservation = $this->reservationService->create($data, null, null);

            foreach ($docPaths as $index => $path) {
                $this->reservationDossier->addDocument(
                    $reservation,
                    'other',
                    'Document workspace #'.($index + 1),
                    $path,
                    null,
                    $user->id
                );
            }

            if (config('app.debug')) {
                Log::debug('workspace.store.ok', [
                    'reservation_id' => $reservation->id,
                    'tour_id' => $reservation->tour_id,
                    'wp_tour_post_id' => $reservation->wp_tour_post_id,
                    'travel_date_id' => $reservation->travel_date_id,
                    'prestation_type' => $reservation->prestation_type,
                    'status' => $reservation->status,
                    'catalog_code' => $catalogRow['code'] ?? null,
                ]);
            }

            return redirect()->route('admin.reservations.index', array_filter([
                'voyage_id' => (int) $reservation->tour_id,
                'travel_date_id' => $bookingResolve['resolved_travel_date_id'] ?? null,
                'status' => Reservation::STATUS_EN_COURS,
                'highlight' => $reservation->id,
                'id' => $reservation->id,
                'created' => '1',
            ], fn ($v) => $v !== null && $v !== ''))->with('reservation_created', AdminReservationFlash::createdPayload($reservation));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            if (config('app.debug')) {
                Log::warning('workspace.store.failed', [
                    'message' => $e->getMessage(),
                    'tour_id' => $request->input('tour_id'),
                    'prestation_type' => $request->input('prestation_type'),
                    'travel_date_id' => $request->input('travel_date_id'),
                ]);
            }
            $msg = config('app.debug')
                ? $e->getMessage()
                : 'Une erreur technique est survenue lors de l’enregistrement. Réessayez ou contactez le support.';

            return redirect()
                ->route('admin.reservations.workspace')
                ->withInput($request->except(['workspace_documents']))
                ->with('workspace_store_error', $msg);
        }
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

        $q = $this->reservationListQuery->baseQuery($request->user(), [
            'tour_id' => (int) $voyage->id,
            'travel_date_id' => $travelDateId,
        ])
            ->whereIn('tour_id', Voyage::allIdsSharingWpTour((int) $voyage->id))
            ->with(['passengers', 'client:id,client_code,full_name', 'travelDate']);
        $this->reservationListQuery->applyTravelDateFilter($q, $travelDateId);
        $reservations = $q->orderByDesc('created_at')->limit(500)->get();
        $reservations->transform(function (Reservation $reservation) use ($request) {
            return $this->reservationVisibility->sanitizeReservationModel($reservation, $request->user());
        });

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
        $this->reservationVisibility->sanitizeReservationModel($reservation, $request->user());

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

    private function normalizeWorkspaceView(mixed $raw): string
    {
        $view = strtolower(trim((string) ($raw ?? '')));

        return in_array($view, ['catalog', 'list', 'calendar'], true) ? $view : 'catalog';
    }

    /**
     * @return array{search: string, type: string, destination: string, date_from: string, date_to: string, budget_min: ?int, budget_max: ?int}
     */
    private function normalizeWorkspaceFilters(Request $request): array
    {
        $search = trim((string) $request->query('search', $request->query('q', '')));
        $type = strtolower(trim((string) $request->query('type', '')));
        if (! in_array($type, ['', 'all', 'package', 'vol', 'hebergement'], true)) {
            $type = '';
        }

        return [
            'search' => $search,
            'type' => $type,
            'destination' => trim((string) $request->query('destination', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'budget_min' => $this->normalizeWorkspaceBudget($request->query('budget_min')),
            'budget_max' => $this->normalizeWorkspaceBudget($request->query('budget_max')),
        ];
    }

    private function normalizeWorkspaceBudget(mixed $raw): ?int
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        if (is_numeric($raw)) {
            return max(0, (int) round((float) $raw));
        }

        $digits = preg_replace('/[^\d]/', '', (string) $raw);

        return $digits !== '' ? max(0, (int) $digits) : null;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $rows
     * @return array<int, string>
     */
    private function workspaceDestinationOptions(\Illuminate\Support\Collection $rows): array
    {
        return $rows
            ->map(function (array $row): string {
                return trim((string) ($row['voyage_destination'] ?? data_get($row, 'modal_detail.destination', '')));
            })
            ->filter(fn (string $destination): bool => $destination !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function assertReservationVisible(Request $request, Reservation $reservation): void
    {
        $q = Reservation::query()->whereKey($reservation->getKey());
        $this->branchScope->scopeReservations($q, $request->user());
        $this->reservationVisibility->applyScope($q, $request->user());
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

    /**
     * Keep only reservation-ready sales offers for workspace list/catalog/calendar.
     *
     * @param  array<string, mixed>  $row
     */
    private function isWorkspaceRowSellableForList(array $row): bool
    {
        if (($row['type'] ?? 'package') !== 'package') {
            return false;
        }

        if (! (bool) data_get($row, 'commercial.is_sellable', false)) {
            return false;
        }

        $departures = collect(data_get($row, 'modal_detail.departures', []));
        if ($departures->isEmpty()) {
            return false;
        }

        return $departures->contains(function ($departure): bool {
            if (! is_array($departure)) {
                return false;
            }

            if (empty($departure['date_iso']) || ! empty($departure['is_past'])) {
                return false;
            }

            $statusKey = (string) ($departure['status_key'] ?? '');
            if ($statusKey === 'full') {
                return false;
            }

            $remaining = $departure['remaining'] ?? null;

            return $remaining === null || (int) $remaining > 0;
        });
    }
}
