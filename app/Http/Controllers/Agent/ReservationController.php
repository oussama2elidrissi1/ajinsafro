<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Departure;
use App\Models\Reservation;
use App\Models\Voyage;
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

        $voyage = Voyage::query()
            ->where('status', 'actif')
            ->when((int) $request->query('voyage_id') > 0, fn (Builder $query) => $query->whereKey((int) $request->query('voyage_id')))
            ->first();

        $departure = Departure::query()
            ->with('voyage:id,name,destination,duration_text,price_from,currency')
            ->when((int) $request->query('departure_id') > 0, fn (Builder $query) => $query->whereKey((int) $request->query('departure_id')))
            ->when($voyage, fn (Builder $query) => $query->where('voyage_id', $voyage->id))
            ->first();

        abort_unless($voyage || $departure, 404);

        return view('agent.reservations.create', [
            'voyage' => $voyage ?: $departure?->voyage,
            'departure' => $departure,
            'travelDateId' => $request->query('travel_date_id'),
        ]);
    }

    public function show(Request $request, Reservation $reservation): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);
        abort_unless($this->agentOwnsReservation($reservation, (int) $user->id), 403);

        return view('agent.reservations.show', [
            'reservation' => $reservation->load(['tour', 'travelDate', 'departure', 'dossier']),
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
