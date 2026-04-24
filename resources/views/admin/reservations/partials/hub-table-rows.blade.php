@php
    use App\Models\Reservation;
    use App\Services\ReservationHubTableProfile;
    $hubTableMode = $hubTableMode ?? ReservationHubTableProfile::MODE_OPERATIONS;
    $hubVoyageFiltered = $hubVoyageFiltered ?? false;
    $showCrossAgencyBranchCol = $hubVoyageFiltered && ($hubTableMode === ReservationHubTableProfile::MODE_AGENCY || $hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS);
    $hubColCount = app(ReservationHubTableProfile::class)->tableColumnCount($hubTableMode, $hubVoyageFiltered);

    $sourceLabelFr = static function (string $src): string {
        return match ($src) {
            'agent_id' => 'Agent affecté (agent_id)',
            'created_by' => 'Compte création (created_by)',
            'created_by_user_id' => 'Compte saisie (created_by_user_id)',
            default => '',
        };
    };
@endphp
@forelse($reservations as $reservation)
    @php
        $highlightReservationId = $highlightReservationId ?? 0;
        $auditUser = $reservation->resolveAuditCreatorUser();
        $opUser = $reservation->resolveOperationalActorUser();
        $opSrc = $reservation->operationalActorDataSourceLabel();
        $statusClass = match ($reservation->status) {
            Reservation::STATUS_EN_COURS, Reservation::STATUS_PENDING => 'badge bg-warning text-dark',
            Reservation::STATUS_SHARED_ROOM_PENDING => 'badge bg-warning text-dark',
            Reservation::STATUS_SHARED_ROOM_PAIRED => 'badge bg-info text-dark',
            Reservation::STATUS_VALIDEE, Reservation::STATUS_CONFIRMED => 'badge bg-success',
            Reservation::STATUS_ANNULEE, Reservation::STATUS_CANCELLED => 'badge bg-danger',
            default => 'badge bg-secondary',
        };
        $clientCell = $reservation->client
            ? '<strong>'.e($reservation->client->full_name).'</strong><span class="text-muted small d-block">'.e($reservation->client->client_code).'</span>'
            : e(trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—');
        $names = $reservation->passengers->map(fn ($p) => trim(($p->first_name ?? '').' '.($p->last_name ?? '')))->filter()->values();
        $paxCell = $names->isEmpty()
            ? '<span class="text-muted">—</span>'
            : '<span class="text-break small">'.e($names->take(3)->join(', ')).($names->count() > 3 ? '…' : '').'</span>';
        $pendingSharedSeats = $reservation->status === Reservation::STATUS_SHARED_ROOM_PENDING
            ? (int) $reservation->reservationRooms
                ->filter(function ($rr) {
                    $mode = (string) ($rr->room_mode ?? '');
                    $state = (string) ($rr->shared_room_status ?? 'pending');
                    if ($mode === 'shared_double' && $state !== 'paired') {
                        return true;
                    }

                    return $mode === '' && (string) ($rr->source_room_type ?? '') === 'double' && (int) ($rr->passenger_count ?? 0) === 1;
                })
                ->sum(fn ($rr) => (int) ($rr->passenger_count ?? 0))
            : 0;
        $depCell = $reservation->travelDate?->date
            ? e($reservation->travelDate->date->format('d/m/Y'))
            : '<span class="text-muted">—</span>';
        $payCell = $reservation->payment_type
            ? '<span class="badge bg-light text-dark">'.e($reservation->payment_type).'</span>'
            : '<span class="text-muted">—</span>';
    @endphp
    <tr @class(['res-hub-row-highlight' => $highlightReservationId && (int) $reservation->id === (int) $highlightReservationId])
        @if($highlightReservationId && (int) $reservation->id === (int) $highlightReservationId) id="res-hub-highlight-row" @endif>
        <td class="ps-3 text-muted small">{{ $reservation->id }}</td>
        <td>{!! $clientCell !!}</td>
        <td>{{ $reservation->offer?->name ?? '—' }}</td>
        @if($hubTableMode === ReservationHubTableProfile::MODE_NETWORK)
            <td>{{ $reservation->agency_label ?? '—' }}</td>
        @elseif($showCrossAgencyBranchCol)
            <td>{{ $reservation->agency_label ?? '—' }}</td>
        @endif
        <td class="small">{!! $depCell !!}</td>
        <td>{!! $paxCell !!}</td>
        @if($hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS)
            <td>
                <span class="{{ $statusClass }}">{{ $reservation->statusLabelFr() }}</span>
                @if($pendingSharedSeats > 0)
                    <span class="text-muted d-block" style="font-size:0.72rem;">{{ $pendingSharedSeats }} place(s) demi-double en attente</span>
                @endif
            </td>
            <td>{!! $payCell !!}</td>
        @else
            <td>{!! $payCell !!}</td>
            <td>
                <span class="{{ $statusClass }}">{{ $reservation->statusLabelFr() }}</span>
                @if($pendingSharedSeats > 0)
                    <span class="text-muted d-block" style="font-size:0.72rem;">{{ $pendingSharedSeats }} place(s) demi-double en attente</span>
                @endif
            </td>
        @endif
        @if($hubTableMode !== ReservationHubTableProfile::MODE_OPERATIONS)
            <td class="small">{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
        @endif
        @if($hubTableMode === ReservationHubTableProfile::MODE_NETWORK)
            <td class="small">
                @if($auditUser)
                    <strong>{{ $auditUser->name }}</strong>
                    @if($auditUser->email)
                        <span class="text-muted d-block" style="font-size:0.72rem;">{{ $auditUser->email }}</span>
                    @endif
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td class="small">
                @if($opUser)
                    <strong>{{ $opUser->name }}</strong>
                    @if($opSrc !== '')
                        <span class="text-muted d-block" style="font-size:0.68rem;">{{ $sourceLabelFr($opSrc) }}</span>
                    @endif
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
            <td class="small">
                @if($reservation->salesManager)
                    <strong>{{ $reservation->salesManager->name }}</strong>
                    <span class="text-muted d-block" style="font-size:0.68rem;">Chef commercial (sales_manager_id)</span>
                @else
                    <span class="text-muted">—</span>
                @endif
            </td>
        @endif
        <td class="text-end pe-3">
            <div class="btn-group btn-group-sm" role="group">
                @can('reservations.view')
                    <button type="button" class="btn btn-outline-secondary btn-res-hub-detail" title="Détails"
                            data-res-id="{{ $reservation->id }}"><i class="bx bx-info-circle"></i></button>
                    <button type="button" class="btn btn-outline-secondary btn-res-hub-pax" title="Participants"
                            data-res-id="{{ $reservation->id }}"><i class="bx bx-group"></i></button>
                @endcan
                @can('reservations.edit')
                    <button type="button" class="btn btn-outline-primary btn-res-hub-edit" title="Modifier"
                            data-res-id="{{ $reservation->id }}"><i class="bx bx-pencil"></i></button>
                @endcan
            </div>
            @can('reservations.update')
                @if($reservation->status !== Reservation::STATUS_VALIDEE && $reservation->status !== Reservation::STATUS_CONFIRMED)
                    <form action="{{ route('admin.reservations.validate', $reservation) }}" method="post" class="d-inline ms-1">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success" title="Valider"><i class="bx bx-check"></i></button>
                    </form>
                @endif
                @if($reservation->status === Reservation::STATUS_SHARED_ROOM_PENDING)
                    <form action="{{ route('admin.reservations.pair-shared-room', $reservation) }}" method="post" class="d-inline ms-1">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-info" title="Jumeler demi-double"><i class="bx bx-link"></i></button>
                    </form>
                @endif
            @endcan
            @can('reservations.destroy')
                <form action="{{ route('admin.reservations.destroy', $reservation) }}" method="post" class="d-inline ms-1" onsubmit="return confirm('Supprimer cette réservation ?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
                </form>
            @endcan
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $hubColCount }}" class="text-center text-muted py-5">Aucune réservation trouvée.</td>
    </tr>
@endforelse
