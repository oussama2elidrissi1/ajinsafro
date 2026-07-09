<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ReservationsController as AdminReservationsController;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Services\BranchScopeService;
use App\Services\View\AgentPortalLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);

        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => trim((string) $request->query('status', '')),
            'date' => trim((string) $request->query('date', '')),
        ];

        $query = Reservation::query()
            ->with(['tour:id,name', 'travelDate:id,date', 'departure:id,start_date'])
            ->withCount('passengers');

        $this->branchScope->scopeReservations($query, $user);
        $this->branchScope->constrainReservationQueryForPortalUser($query, $user);

        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('dossier_number', 'like', $like)
                    ->orWhere('client_first_name', 'like', $like)
                    ->orWhere('client_last_name', 'like', $like)
                    ->orWhere('client_phone', 'like', $like)
                    ->orWhereHas('tour', fn (Builder $tourQuery) => $tourQuery->where('name', 'like', $like));
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['date'] !== '') {
            $query->where(function (Builder $builder) use ($filters): void {
                $builder->whereDate('created_at', $filters['date'])
                    ->orWhereHas('travelDate', fn (Builder $dateQuery) => $dateQuery->whereDate('date', $filters['date']))
                    ->orWhereHas('departure', fn (Builder $departureQuery) => $departureQuery->whereDate('start_date', $filters['date']));
            });
        }

        return view('agent.reservations.index', [
            'reservations' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'canManageReservations' => $this->canManageReservations($user),
            'canEditReservations' => $this->canEditReservations($user),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.create'), 403);

        $request->attributes->set('agent_reservation_mode', true);

        return app(AdminReservationsController::class)->create($request);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.create'), 403);

        $request->attributes->set('agent_reservation_mode', true);

        return app(AdminReservationsController::class)->store($request);
    }

    public function edit(Request $request, Reservation $reservation): View
    {
        $this->authorizeAgentReservationEdit($request, $reservation);

        return app(AdminReservationsController::class)->edit($request, $reservation);
    }

    public function update(Request $request, Reservation $reservation): RedirectResponse|HttpResponse
    {
        $this->authorizeAgentReservationEdit($request, $reservation);

        return app(AdminReservationsController::class)->update($request, $reservation);
    }

    public function show(Request $request, Reservation $reservation): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);
        abort_unless($this->branchScope->userCanAccessReservation($user, $reservation), 403);

        return view('agent.reservations.show', [
            'reservation' => $reservation->load([
                'tour',
                'travelDate',
                'departure',
                'dossier',
                'client',
                'passengers',
                'payments.creator',
                'documents.creator',
                'histories.user',
                'branch',
                'partner',
                'creator',
                'createdBy',
                'agent',
            ]),
            'canManageReservations' => $this->canManageReservations($user),
            'canEditReservations' => $this->canEditReservations($user),
        ]);
    }

    public function storePayment(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeAgentReservationAction($request, $reservation);

        return app(AdminReservationsController::class)->storePayment($request, $reservation);
    }

    public function storeDocument(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeAgentReservationAction($request, $reservation);

        return app(AdminReservationsController::class)->storeDocument($request, $reservation);
    }

    public function storeNote(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeAgentReservationAction($request, $reservation);

        return app(AdminReservationsController::class)->storeNote($request, $reservation);
    }

    public function cancel(Request $request, Reservation $reservation): RedirectResponse
    {
        $this->authorizeAgentReservationAction($request, $reservation);

        return app(AdminReservationsController::class)->cancel($request, $reservation);
    }

    public function validateReservation(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $this->canManageReservations($user), 403);
        abort_unless($this->branchScope->userCanAccessReservation($user, $reservation), 403);

        return app(AdminReservationsController::class)->validateReservation($request, $reservation);
    }

    public function printDossier(Request $request, Reservation $reservation)
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $this->canManageReservations($user), 403);
        abort_unless($this->branchScope->userCanAccessReservation($user, $reservation), 403);
        $request->attributes->set('agent_reservation_mode', true);

        return app(AdminReservationsController::class)->dossierPdf($request, $reservation);
    }

    public function paymentReceiptPdf(Request $request, Reservation $reservation, ReservationPayment $payment)
    {
        $this->authorizeAgentReservationView($request, $reservation);

        return app(AdminReservationsController::class)->paymentReceiptPdf($request, $reservation, $payment);
    }

    public function showReceipt(Request $request)
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);
        $request->attributes->set('agent_reservation_mode', true);

        return app(AdminReservationsController::class)->showReceipt($request);
    }

    private function authorizeAgentReservationAction(Request $request, Reservation $reservation): void
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $this->canManageReservations($user), 403);
        abort_unless($this->branchScope->userCanAccessReservation($user, $reservation), 403);
        $request->attributes->set('agent_reservation_mode', true);
    }

    private function authorizeAgentReservationView(Request $request, Reservation $reservation): void
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);
        abort_unless($this->branchScope->userCanAccessReservation($user, $reservation), 403);
        $request->attributes->set('agent_reservation_mode', true);
    }

    private function authorizeAgentReservationEdit(Request $request, Reservation $reservation): void
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $this->canEditReservations($user), 403);
        abort_unless($this->branchScope->userCanAccessReservation($user, $reservation), 403);
        $request->attributes->set('agent_reservation_mode', true);
        $request->attributes->set('agent_can_manage_reservation_actions', $this->canManageReservations($user));
    }

    private function statusOptions(): array
    {
        return [
            Reservation::STATUS_EN_COURS => 'En cours',
            Reservation::STATUS_OPTION => 'Option',
            Reservation::STATUS_SHARED_ROOM_PENDING => 'Rooming a suivre',
            Reservation::STATUS_SHARED_ROOM_PAIRED => 'Rooming jumele',
            Reservation::STATUS_PARTIALLY_PAID => 'Paiement partiel',
            Reservation::STATUS_PAID => 'Payee',
            Reservation::STATUS_EXPIRED => 'Expiree',
            Reservation::STATUS_REFUNDED => 'Remboursee',
            Reservation::STATUS_VALIDEE => 'Validée',
            Reservation::STATUS_ANNULEE => 'Annulée',
        ];
    }

    private function canManageReservations($user): bool
    {
        return strtolower((string) ($user->email ?? '')) === 'booking@ajinsafro.ma';
    }

    private function canEditReservations($user): bool
    {
        return $this->canManageReservations($user)
            || $user->can('reservations.update')
            || $user->can('reservations.edit')
            || $user->can('reservations.create');
    }
}
