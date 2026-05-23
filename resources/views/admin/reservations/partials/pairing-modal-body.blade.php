@php
    use App\Models\Reservation;

    $modeLabel = match ($sourceMode ?? '') {
        'half_male' => 'Demi-double homme',
        'half_female' => 'Demi-double femme',
        'shared_double' => 'Demi-double',
        default => ucfirst(str_replace('_', ' ', $sourceMode ?? '')),
    };

    $genderLabel = match ($genderRequirement ?? '') {
        'male' => 'Homme',
        'female' => 'Femme',
        default => '-',
    };

    $resCode = $reservation->catalog_source_code ?: ('RES-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT));
    $clientName = $reservation->client
        ? ($reservation->client->full_name ?: '-')
        : (trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '-');

    $depDate = '-';
    try {
        if ($reservation->travelDate && $reservation->travelDate->date instanceof \Carbon\Carbon) {
            $depDate = $reservation->travelDate->date->format('d/m/Y');
        } elseif ($reservation->travelDate && !empty($reservation->travelDate->date)) {
            $depDate = (string) $reservation->travelDate->date;
        }
    } catch (\Throwable $e) {
        $depDate = '-';
    }

    $offerName = $reservation->offer?->name ?? $reservation->tour?->name ?? '-';
    $roomType = (string) ($sourceRoom->room_type_snapshot ?? 'Double');
    $occupied = (int) ($sourceRoom->passenger_count ?? 0);
    $capacity = (int) ($sourceCapacity ?? 2);
@endphp

<div class="pairing-modal-content">
    <div class="mb-3">
        <div class="fw-semibold mb-2">Resumé de la réservation actuelle</div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
                <tbody>
                    <tr><td class="text-muted" style="width:140px">Dossier</td><td>{{ $resCode }}</td></tr>
                    <tr><td class="text-muted">Client</td><td>{{ $clientName }}</td></tr>
                    <tr><td class="text-muted">Voyage</td><td>{{ $offerName }}</td></tr>
                    <tr><td class="text-muted">Depart</td><td>{{ $depDate }}</td></tr>
                    <tr><td class="text-muted">Chambre</td><td>{{ $roomType }}</td></tr>
                    <tr><td class="text-muted">Mode</td><td>{{ $modeLabel }}</td></tr>
                    <tr><td class="text-muted">Occupation</td><td>{{ $occupied }}/{{ $capacity }}</td></tr>
                    <tr><td class="text-muted">Place restante</td><td><strong>{{ $sourceRemaining }}</strong></td></tr>
                    <tr><td class="text-muted">Sexe requis</td><td>{{ $genderLabel }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <hr class="my-3">

    @if($candidates->isEmpty())
        <div class="alert alert-light border text-center">
            <strong>Aucune reservation compatible pour le moment.</strong>
            <div class="small text-muted mt-1">Cette reservation restera en attente de jumelage jusqu'a l'arrivee d'une autre demande compatible.</div>
        </div>
    @else
        <div class="fw-semibold mb-2">Reservations compatibles trouvees ({{ $candidates->count() }})</div>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Dossier</th>
                        <th>Client</th>
                        <th>Telephone</th>
                        <th>Depart</th>
                        <th class="text-center">Chambre</th>
                        <th class="text-center">Mode</th>
                        <th class="text-center">Sexe</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($candidates as $candidate)
                        @php
                            $cResCode = $candidate->catalog_source_code ?: ('RES-' . str_pad((string) $candidate->id, 6, '0', STR_PAD_LEFT));
                            $cClientName = $candidate->client
                                ? ($candidate->client->full_name ?: '-')
                                : (trim(($candidate->client_first_name ?? '') . ' ' . ($candidate->client_last_name ?? '')) ?: '-');
                            $cPhone = '-';
                            try {
                                $cPhone = $candidate->client?->phone ?? $candidate->client?->whatsapp_number ?? $candidate->client_phone ?? '-';
                            } catch (\Throwable $e) {
                                $cPhone = '-';
                            }

                            $cDepDate = '-';
                            try {
                                if ($candidate->travelDate && $candidate->travelDate->date instanceof \Carbon\Carbon) {
                                    $cDepDate = $candidate->travelDate->date->format('d/m/Y');
                                } elseif ($candidate->travelDate && !empty($candidate->travelDate->date)) {
                                    $cDepDate = (string) $candidate->travelDate->date;
                                }
                            } catch (\Throwable $e) {
                                $cDepDate = '-';
                            }

                            $cRoom = $candidate->reservationRooms->first(function ($rr) {
                                return in_array((string) ($rr->room_mode ?? ''), ['half_male', 'half_female', 'shared_double'], true);
                            });
                            $cRoomType = $cRoom ? (string) ($cRoom->room_type_snapshot ?? 'Double') : '-';
                            $cMode = $cRoom ? (string) ($cRoom->room_mode ?? '') : '';
                            $cModeLabel = match ($cMode) {
                                'half_male' => 'Demi-double homme',
                                'half_female' => 'Demi-double femme',
                                'shared_double' => 'Demi-double',
                                '' => '-',
                                default => ucfirst(str_replace('_', ' ', $cMode)),
                            };
                            $cGender = match ($cMode) {
                                'half_male' => 'Homme',
                                'half_female' => 'Femme',
                                default => '-',
                            };
                            $cStatus = (string) ($candidate->status ?? '');
                            $cStatusLabel = match ($cStatus) {
                                Reservation::STATUS_SHARED_ROOM_PENDING => 'En attente de jumelage',
                                default => $candidate->statusLabelFr(),
                            };
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $cResCode }}</td>
                            <td>{{ $cClientName }}</td>
                            <td>{{ $cPhone }}</td>
                            <td>{{ $cDepDate }}</td>
                            <td class="text-center">{{ $cRoomType }}</td>
                            <td class="text-center">{{ $cModeLabel }}</td>
                            <td class="text-center">{{ $cGender }}</td>
                            <td class="text-center"><span class="badge bg-warning text-dark">{{ $cStatusLabel }}</span></td>
                            <td class="text-end">
                                <form action="{{ route('admin.reservations.pair-shared-room', $reservation) }}" method="post" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="target_reservation_id" value="{{ $candidate->id }}">
                                    <button type="submit" class="btn btn-sm btn-primary">Jumeler</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
