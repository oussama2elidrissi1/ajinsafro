@php
    use App\Models\Reservation;
    use App\Services\ReservationHubTableProfile;

    $hubTableMode = $hubTableMode ?? ReservationHubTableProfile::MODE_OPERATIONS;
    $hubVoyageFiltered = $hubVoyageFiltered ?? false;
    $filterChannel = $filterChannel ?? null;
    $isClientChannel = $filterChannel === 'client';
    $reservationVisibility = is_array($reservationVisibility ?? null) ? $reservationVisibility : [];
    $limitedReservationPresentation = (bool) ($reservationVisibility['limited_presentation'] ?? false);
    $canViewReservationFinancial = (bool) ($reservationVisibility['view_financial'] ?? false);
    $canViewAssignmentContext = (bool) ($reservationVisibility['view_assignment_context'] ?? false);
    $canViewSensitive = (bool) ($reservationVisibility['view_sensitive'] ?? false);
    $showCrossAgencyBranchCol = $hubVoyageFiltered && ($hubTableMode === ReservationHubTableProfile::MODE_AGENCY || $hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS);
    $hubColCount = $limitedReservationPresentation
        ? 7
        : ($isClientChannel
            ? ($canViewReservationFinancial ? 10 : 9)
            : app(ReservationHubTableProfile::class)->tableColumnCount($hubTableMode, $hubVoyageFiltered));

    $sourceLabelFr = static function (string $src): string {
        return match ($src) {
            'agent_id' => 'Agent affecte (agent_id)',
            'created_by' => 'Compte creation (created_by)',
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
        $needsPairing = $reservation->needsSharedRoomPairing();
        $statusClass = match ($reservation->status) {
            Reservation::STATUS_EN_COURS, Reservation::STATUS_PENDING => 'badge res-status-badge res-status-badge--pending',
            Reservation::STATUS_SHARED_ROOM_PENDING => 'badge res-status-badge res-status-badge--pairing',
            Reservation::STATUS_SHARED_ROOM_PAIRED => 'badge res-status-badge res-status-badge--paired',
            Reservation::STATUS_VALIDEE, Reservation::STATUS_CONFIRMED => 'badge res-status-badge res-status-badge--confirmed',
            Reservation::STATUS_ANNULEE, Reservation::STATUS_CANCELLED => 'badge res-status-badge res-status-badge--cancelled',
            default => $needsPairing ? 'badge res-status-badge res-status-badge--pairing' : 'badge res-status-badge res-status-badge--neutral',
        };

        $clientName = $reservation->client
            ? ($reservation->client->full_name ?: '-')
            : (trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? '')) ?: '-');
        $clientCode = $reservation->client?->client_code ?: null;
        $reservationCode = $reservation->catalog_source_code ?: ('RES-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT));
        $offerName = $reservation->offer?->name ?? '-';
        $agencyLabel = $reservation->agency_label ?? '-';
        $names = $reservation->passengers->map(fn ($p) => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')))->filter()->values();
        $passengerPreview = $names->isEmpty() ? '-' : $names->take(3)->join(', ');
        $pendingSharedSeats = $needsPairing ? $reservation->pendingSharedRoomSeats() : 0;
        $depDate = $reservation->travelDate?->date ? $reservation->travelDate->date->format('d/m/Y') : '-';
        $createdAt = optional($reservation->created_at)->format('d/m/Y H:i');
        $paymentType = $reservation->payment_type ?: null;
        $salesManagerName = $reservation->salesManager?->name ?: null;
    @endphp
    <tr @class(['res-hub-row-highlight' => $highlightReservationId && (int) $reservation->id === (int) $highlightReservationId])
        @if($highlightReservationId && (int) $reservation->id === (int) $highlightReservationId) id="res-hub-highlight-row" @endif>
        <td class="ps-3 text-muted small fw-semibold">{{ $reservation->id }}</td>

        <td>
            <div class="fw-semibold text-dark">{{ $clientName }}</div>
            <div class="small text-muted d-flex flex-wrap align-items-center gap-2">
                @if($clientCode)
                    <span>{{ $clientCode }}</span>
                @endif
                @if($isClientChannel)
                    <span>{{ $reservationCode }}</span>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Client web</span>
                @endif
            </div>
            @if(!$clientCode && !$isClientChannel)
                <div class="small text-muted">-</div>
            @endif
        </td>

        <td>
            <div class="fw-semibold text-dark">{{ $offerName }}</div>
            <div class="small text-muted">Reservation #{{ $reservation->id }}</div>
        </td>

        @if(!$isClientChannel && !$limitedReservationPresentation)
            @if($hubTableMode === ReservationHubTableProfile::MODE_NETWORK)
                <td>
                    <div class="small fw-semibold text-dark">{{ $agencyLabel }}</div>
                </td>
            @elseif($showCrossAgencyBranchCol)
                <td>
                    <div class="small fw-semibold text-dark">{{ $agencyLabel }}</div>
                </td>
            @endif
        @endif

        <td>
            <div class="fw-semibold text-dark">{{ $depDate }}</div>
            @if($reservation->travel_date_id)
                <div class="small text-muted">TravelDate #{{ $reservation->travel_date_id }}</div>
            @endif
        </td>

        <td>
            <div class="small text-dark">{{ $passengerPreview }}</div>
            @if($names->count() > 3)
                <div class="small text-muted">+{{ $names->count() - 3 }} autre(s)</div>
            @elseif($names->isEmpty())
                <div class="small text-muted">Aucun passager detaille</div>
            @endif
        </td>

        @if($isClientChannel)
            @if($canViewReservationFinancial)
                <td>
                    @if($paymentType)
                        <span class="badge bg-light text-dark">{{ $paymentType }}</span>
                    @else
                        <span class="text-muted small">-</span>
                    @endif
                </td>
            @endif
            <td>
                <span class="{{ $statusClass }}">{{ $needsPairing ? 'En attente de jumelage' : $reservation->statusLabelFr() }}</span>
                @if($pendingSharedSeats > 0)
                    <div class="small text-muted mt-1">{{ $pendingSharedSeats }} place(s) demi-double en attente</div>
                @endif
            </td>
            @if($canViewAssignmentContext)
                <td>
                    <div class="small text-dark">{{ $createdAt ?: '-' }}</div>
                </td>
                <td>
                    @if($salesManagerName)
                        <div class="fw-semibold text-dark">{{ $salesManagerName }}</div>
                    @else
                        <span class="badge bg-light text-secondary border">Non assigne</span>
                    @endif
                </td>
            @endif
        @else
            @if($limitedReservationPresentation)
                <td>
                    <span class="{{ $statusClass }}">{{ $needsPairing ? 'En attente de jumelage' : $reservation->statusLabelFr() }}</span>
                    @if($pendingSharedSeats > 0)
                        <div class="small text-muted mt-1">{{ $pendingSharedSeats }} place(s) demi-double en attente</div>
                    @endif
                </td>
            @elseif($hubTableMode === ReservationHubTableProfile::MODE_OPERATIONS)
                <td>
                    <span class="{{ $statusClass }}">{{ $needsPairing ? 'En attente de jumelage' : $reservation->statusLabelFr() }}</span>
                    @if($pendingSharedSeats > 0)
                        <div class="small text-muted mt-1">{{ $pendingSharedSeats }} place(s) demi-double en attente</div>
                    @endif
                </td>
                @if($canViewReservationFinancial)
                    <td>
                        @if($paymentType)
                            <span class="badge bg-light text-dark">{{ $paymentType }}</span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                @endif
            @else
                @if($canViewReservationFinancial)
                    <td>
                        @if($paymentType)
                            <span class="badge bg-light text-dark">{{ $paymentType }}</span>
                        @else
                            <span class="text-muted small">-</span>
                        @endif
                    </td>
                @endif
                <td>
                    <span class="{{ $statusClass }}">{{ $needsPairing ? 'En attente de jumelage' : $reservation->statusLabelFr() }}</span>
                    @if($pendingSharedSeats > 0)
                        <div class="small text-muted mt-1">{{ $pendingSharedSeats }} place(s) demi-double en attente</div>
                    @endif
                </td>
            @endif

            @if(!$limitedReservationPresentation && $hubTableMode !== ReservationHubTableProfile::MODE_OPERATIONS && $canViewAssignmentContext)
                <td>
                    <div class="small text-dark">{{ $createdAt ?: '-' }}</div>
                </td>
            @endif

            @if(!$limitedReservationPresentation && $hubTableMode === ReservationHubTableProfile::MODE_NETWORK && $canViewAssignmentContext)
                <td class="small">
                    @if($auditUser)
                        <div class="fw-semibold text-dark">{{ $auditUser->name }}</div>
                        @if($auditUser->email)
                            <div class="text-muted">{{ $auditUser->email }}</div>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="small">
                    @if($opUser)
                        <div class="fw-semibold text-dark">{{ $opUser->name }}</div>
                        @if($opSrc !== '')
                            <div class="text-muted">{{ $sourceLabelFr($opSrc) }}</div>
                        @endif
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="small">
                    @if($reservation->salesManager)
                        <div class="fw-semibold text-dark">{{ $reservation->salesManager->name }}</div>
                        <div class="text-muted">Chef commercial</div>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            @endif
        @endif

        <td class="text-end pe-3">
            <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                @can('reservations.view')
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-res-hub-detail" title="Details" data-res-id="{{ $reservation->id }}">
                        <i class="bx bx-info-circle"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary btn-res-hub-pax" title="Participants" data-res-id="{{ $reservation->id }}">
                        <i class="bx bx-group"></i>
                    </button>
                @endcan
                @can('reservations.edit')
                    <button type="button" class="btn btn-sm btn-outline-primary btn-res-hub-edit" title="Modifier" data-res-id="{{ $reservation->id }}">
                        <i class="bx bx-pencil"></i>
                    </button>
                @endcan
                @can('reservations.update')
                    @if($reservation->status !== Reservation::STATUS_VALIDEE && $reservation->status !== Reservation::STATUS_CONFIRMED)
                        <form action="{{ route('admin.reservations.validate', $reservation) }}" method="post" class="d-inline res-hub-validate-form">
                            @csrf
                            <button
                                type="button"
                                class="btn btn-sm btn-success btn-res-hub-validate"
                                title="Valider"
                                data-res-id="{{ $reservation->id }}"
                                data-res-client="{{ $clientName }}"
                                data-res-offer="{{ $offerName }}"
                                data-res-status="{{ $reservation->statusLabelFr() }}"
                                data-res-date="{{ $depDate }}"
                            >
                                <i class="bx bx-check"></i>
                            </button>
                        </form>
                    @endif
                    @if($needsPairing)
                        <button type="button" class="btn btn-sm btn-outline-info btn-res-hub-pair" title="Jumeler"
                            data-res-id="{{ $reservation->id }}"
                            data-res-code="{{ $reservation->catalog_source_code ?: ('RES-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT)) }}"
                        >
                            <i class="bx bx-link"></i>
                        </button>
                    @endif
                @endcan
                @can('reservations.destroy')
                    <form action="{{ route('admin.reservations.destroy', $reservation) }}" method="post" class="d-inline" onsubmit="return confirm('Supprimer cette reservation ?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                            <i class="bx bx-trash"></i>
                        </button>
                    </form>
                @endcan
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="{{ $hubColCount }}" class="text-center text-muted py-5">Aucune reservation trouvee.</td>
    </tr>
@endforelse
