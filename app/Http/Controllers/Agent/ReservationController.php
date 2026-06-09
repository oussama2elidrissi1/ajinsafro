<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\ReservationsController as AdminReservationsController;
use App\Models\Reservation;
use App\Services\View\AgentPortalLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
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
            ->where(fn (Builder $builder) => $this->scopeOwnedByAgent($builder, (int) $user->id));

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

    public function show(Request $request, Reservation $reservation): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);
        abort_unless($this->agentOwnsReservation($reservation, (int) $user->id), 403);

        return view('agent.reservations.show', [
            'reservation' => $reservation->load([
                'tour',
                'travelDate',
                'departure',
                'dossier',
                'client',
                'passengers',
                'payments',
                'branch',
                'partner',
                'creator',
                'createdBy',
                'agent',
            ]),
        ]);
    }

    private function scopeOwnedByAgent(Builder $query, int $userId): Builder
    {
        return $query->where('agent_id', $userId)
            ->orWhere('assigned_to', $userId)
            ->orWhere('created_by', $userId)
            ->orWhere('created_by_user_id', $userId);
    }

    private function agentOwnsReservation(Reservation $reservation, int $userId): bool
    {
        return in_array($userId, array_filter([
            (int) ($reservation->agent_id ?? 0),
            (int) ($reservation->assigned_to ?? 0),
            (int) ($reservation->created_by ?? 0),
            (int) ($reservation->created_by_user_id ?? 0),
        ]), true);
    }

    private function statusOptions(): array
    {
        return [
            Reservation::STATUS_EN_COURS => 'En cours',
            Reservation::STATUS_VALIDEE => 'Validée',
            Reservation::STATUS_ANNULEE => 'Annulée',
        ];
    }
}
