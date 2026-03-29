@php
    $highlightReservationId = $highlightReservationId ?? 0;
@endphp
@forelse($reservations as $reservation)
    <tr @class(['table-info' => $highlightReservationId && (int) $reservation->id === (int) $highlightReservationId])>
        <td class="ps-3 text-muted small">{{ $reservation->id }}</td>
        <td>
            @if($reservation->client)
                <strong>{{ $reservation->client->full_name }}</strong>
                <span class="text-muted small d-block">{{ $reservation->client->client_code }}</span>
            @else
                {{ trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: '—' }}
            @endif
        </td>
        <td>{{ $reservation->tour?->name ?? '—' }}</td>
        <td class="small">
            @if($reservation->travelDate?->date)
                {{ $reservation->travelDate->date->format('d/m/Y') }}
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td>
            @php
                $names = $reservation->passengers->map(fn($p) => trim(($p->first_name ?? '').' '.($p->last_name ?? '')))->filter()->values();
            @endphp
            @if($names->isEmpty())
                <span class="text-muted">—</span>
            @else
                <span class="text-break small">{{ $names->take(3)->join(', ') }}{{ $names->count() > 3 ? '…' : '' }}</span>
            @endif
        </td>
        <td>
            @if($reservation->payment_type)
                <span class="badge bg-light text-dark">{{ $reservation->payment_type }}</span>
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td>
            @php
                $statusClass = match($reservation->status) {
                    \App\Models\Reservation::STATUS_EN_COURS => 'badge bg-warning text-dark',
                    \App\Models\Reservation::STATUS_VALIDEE => 'badge bg-success',
                    \App\Models\Reservation::STATUS_ANNULEE => 'badge bg-danger',
                    default => 'badge bg-secondary',
                };
            @endphp
            <span class="{{ $statusClass }}">{{ $reservation->status }}</span>
        </td>
        <td class="small">{{ optional($reservation->created_at)->format('d/m/Y H:i') }}</td>
        <td class="text-end pe-3">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary btn-res-hub-detail" title="Détails"
                        data-res-id="{{ $reservation->id }}"><i class="bx bx-info-circle"></i></button>
                <button type="button" class="btn btn-outline-secondary btn-res-hub-pax" title="Participants"
                        data-res-id="{{ $reservation->id }}"><i class="bx bx-group"></i></button>
                <button type="button" class="btn btn-outline-primary btn-res-hub-edit" title="Modifier"
                        data-res-id="{{ $reservation->id }}"><i class="bx bx-pencil"></i></button>
            </div>
            @if($reservation->status !== \App\Models\Reservation::STATUS_VALIDEE)
                <form action="{{ route('admin.reservations.validate', $reservation) }}" method="post" class="d-inline ms-1">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success" title="Valider"><i class="bx bx-check"></i></button>
                </form>
            @endif
            <form action="{{ route('admin.reservations.destroy', $reservation) }}" method="post" class="d-inline ms-1" onsubmit="return confirm('Supprimer cette réservation ?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="bx bx-trash"></i></button>
            </form>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="9" class="text-center text-muted py-5">Aucune réservation trouvée.</td>
    </tr>
@endforelse
