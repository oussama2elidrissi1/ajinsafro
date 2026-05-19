<?php $__env->startSection('title', 'Dossier de réservation'); ?>

<?php
    use App\Models\Reservation;
    use App\Models\Voyage;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $backUrl = $backUrl ?? route('admin.reservation-dossiers.index');
    $offerImageUrl = $offerImageUrl ?? null;
    $offer = $offer ?? null;
    $relatedReservations = $relatedReservations ?? collect();
    $allClientReservationsUrl = $allClientReservationsUrl ?? url('/admin/reservations');
    $noteEntries = $noteEntries ?? collect();
    $notesContent = $notesContent ?? '';
    $client = $dossier->client ?: $reservation->client;
    $offer = $offer ?: ($reservation->offer ?? $reservation->voyage ?? $reservation->tour ?? null);
    $departure = $reservation->departure;
    $payments = $dossier->payments->isNotEmpty() ? $dossier->payments : ($reservation->payments ?? collect());
    $documents = $dossier->documents->isNotEmpty() ? $dossier->documents : ($reservation->documents ?? collect());
    $histories = $dossier->histories->isNotEmpty() ? $dossier->histories : ($reservation->histories ?? collect());
    $passengers = $reservation->passengers ?? collect();
    $rawTotal = $dossier->total_amount ?? $reservation->total_amount ?? null;
    $rawPaid = $dossier->paid_amount ?? $reservation->paid_amount ?? null;
    $hasCalculatedFinancials = $rawTotal !== null && $rawTotal !== '';
    $totalAmount = (float) ($rawTotal ?? 0);
    $paidAmount = (float) ($rawPaid ?? 0);
    $remainingAmount = (float) ($dossier->remaining_amount ?? $reservation->remaining_amount ?? max(0, $totalAmount - $paidAmount));
    $paymentProgress = $totalAmount > 0 ? (int) min(100, round(($paidAmount / $totalAmount) * 100)) : 0;
    $dossierStatus = $dossier->dossier_status ?: $reservation->dossier_status ?: $reservation->status;
    $paymentStatus = $dossier->payment_status ?: $reservation->payment_status ?: Reservation::PAYMENT_STATUS_NON_PAID;
    $clientName = $client?->full_name ?: trim(($reservation->client_first_name ?? '').' '.($reservation->client_last_name ?? '')) ?: 'Client non renseigné';
    $clientEmail = $client?->email ?: $reservation->client_email;
    $clientPhone = $client?->phone ?: $client?->whatsapp_number ?: $reservation->client_phone;
    $clientAddress = collect([
        $client?->address_line_1,
        $client?->address_line_2,
        $client?->city,
        $client?->country_of_residence,
    ])->filter()->implode(', ');
    $clientProfileUrl = $client ? route('admin.customers.clients.show', $client) : null;
    $allClientReservationsUrl = $client
        ? route('admin.reservations.index', ['client_id' => $client->id])
        : route('admin.reservations.index', ['search' => $clientName]);
    $mailToUrl = $clientEmail ? 'mailto:'.$clientEmail.'?subject='.rawurlencode('Dossier '.$dossier->dossier_number) : null;
    $normalizedPhone = preg_replace('/\D+/', '', (string) $clientPhone);
    if ($normalizedPhone && str_starts_with($normalizedPhone, '0')) {
        $normalizedPhone = '212'.ltrim($normalizedPhone, '0');
    }
    if ($normalizedPhone && str_starts_with($normalizedPhone, '00')) {
        $normalizedPhone = substr($normalizedPhone, 2);
    }
    $whatsAppUrl = $normalizedPhone ? 'https://wa.me/'.$normalizedPhone : null;
    $notesContent = trim((string) $notesContent);

    $statusMap = [
        'draft' => ['label' => 'Brouillon', 'class' => 'is-draft'],
        'pending' => ['label' => 'En attente', 'class' => 'is-pending'],
        'confirmed' => ['label' => 'Confirmé', 'class' => 'is-confirmed'],
        'cancelled' => ['label' => 'Annulé', 'class' => 'is-cancelled'],
        'completed' => ['label' => 'Clôturé', 'class' => 'is-completed'],
        'paid' => ['label' => 'Payé', 'class' => 'is-confirmed'],
        'partially_paid' => ['label' => 'Partiel', 'class' => 'is-pending'],
        'refunded' => ['label' => 'Remboursé', 'class' => 'is-completed'],
        'shared_room_pending' => ['label' => 'Jumelage en attente', 'class' => 'is-pending'],
        'shared_room_paired' => ['label' => 'Jumelage confirmé', 'class' => 'is-confirmed'],
    ];
    $paymentMap = [
        'paid' => ['label' => 'Payé', 'class' => 'is-paid'],
        'partial' => ['label' => 'Partiel', 'class' => 'is-partial'],
        'deposit' => ['label' => 'Acompte', 'class' => 'is-partial'],
        'unpaid' => ['label' => 'Non payé', 'class' => 'is-unpaid'],
        'non_paid' => ['label' => 'Non payé', 'class' => 'is-unpaid'],
        'refunded' => ['label' => 'Remboursé', 'class' => 'is-refunded'],
    ];
    $dossierBadge = $statusMap[$dossierStatus] ?? ['label' => ucfirst((string) $dossierStatus), 'class' => 'is-draft'];
    $paymentBadge = $paymentMap[$paymentStatus] ?? ['label' => ucfirst((string) $paymentStatus), 'class' => 'is-unpaid'];

    $durationLabel = $offer?->duration_text;
    if (! $durationLabel && $departure?->start_date && $departure?->end_date) {
        $days = max(1, $departure->start_date->diffInDays($departure->end_date) + 1);
        $nights = max(0, $days - 1);
        $durationLabel = $days.' jour'.($days > 1 ? 's' : '').($nights > 0 ? ' / '.$nights.' nuit'.($nights > 1 ? 's' : '') : '');
    }

    $documentUrl = function ($document) {
        if (! $document || empty($document->file_path)) {
            return null;
        }
        if (str_starts_with($document->file_path, 'http://') || str_starts_with($document->file_path, 'https://')) {
            return $document->file_path;
        }

        return asset('storage/'.ltrim((string) $document->file_path, '/'));
    };

    $historyMeta = function ($history) {
        $newValue = [];
        if (! empty($history->new_value)) {
            $decoded = json_decode((string) $history->new_value, true);
            $newValue = is_array($decoded) ? $decoded : [];
        }

        $labels = [
            'reservation.created' => 'Dossier créé',
            'reservation.confirmed' => 'Dossier confirmé',
            'reservation.cancelled' => 'Dossier annulé',
            'reservation.payment_added' => 'Paiement enregistré',
            'reservation.document_added' => 'Document ajouté',
            'reservation.note_added' => 'Note interne ajoutée',
        ];

        $details = collect([
            isset($newValue['amount']) ? 'Montant : '.number_format((float) $newValue['amount'], 2, ',', ' ').' DH' : null,
            isset($newValue['payment_method']) ? 'Mode : '.$newValue['payment_method'] : null,
            isset($newValue['status']) ? 'Statut : '.$newValue['status'] : null,
            isset($newValue['title']) ? 'Document : '.$newValue['title'] : null,
            isset($newValue['note_excerpt']) ? $newValue['note_excerpt'] : null,
            $history->note ?: null,
        ])->filter()->implode(' • ');

        return [
            'label' => $labels[$history->action] ?? str_replace('_', ' ', (string) $history->action),
            'details' => $details,
        ];
    };

    $voyages = $voyages ?? Voyage::query()
        ->orderBy('name')
        ->limit(200)
        ->get(['id', 'name', 'slug']);

    $currentTourId = (int) ($reservation->tour_id ?? $offer?->id ?? 0);
    $currentDepartureId = (int) ($reservation->departure_id ?? $departure?->id ?? 0);
    $currentTravelDateId = (int) ($reservation->travel_date_id ?? 0);

    $mainTraveler = [
        'key' => '__main',
        'is_main' => true,
        'type' => 'adult',
        'type_label' => 'Principal',
        'first_name' => $client?->first_name ?: $reservation->client_first_name ?: $client?->full_name,
        'last_name' => $client?->last_name ?: $reservation->client_last_name,
        'birth_date' => optional($client?->birth_date)->format('Y-m-d') ?: optional($reservation->client_birth_date)->format('Y-m-d'),
        'document_type' => $client?->document_type ?: $reservation->client_document_type,
        'document_number' => $client?->document_number ?: $reservation->client_document_number,
        'gender' => $client?->gender ?? null,
        'relationship_to_main' => 'main',
        'consumes_bed' => true,
    ];

    $companionTravelers = $passengers->map(function ($passenger, $index) {
        return [
            'key' => (string) ($passenger->id ?? $index),
            'id' => $passenger->id ?? null,
            'is_main' => false,
            'type' => $passenger->type ?: $passenger->traveler_type ?: 'adult',
            'type_label' => ucfirst((string) ($passenger->type ?: $passenger->traveler_type ?: 'adult')),
            'first_name' => $passenger->first_name,
            'last_name' => $passenger->last_name,
            'birth_date' => optional($passenger->birth_date)->format('Y-m-d') ?: ($passenger->birth_date ?: null),
            'document_type' => $passenger->document_type,
            'document_number' => $passenger->document_number,
            'gender' => $passenger->gender ?? null,
            'relationship_to_main' => $passenger->relationship_to_main ?? null,
            'consumes_bed' => $passenger->consumes_bed ?? true,
        ];
    })->values();

    $travelerRows = collect([$mainTraveler])->merge($companionTravelers)->values();
    $travelersCount = max(1, $travelerRows->count());

    $roomAllocationRows = ($reservation->reservationRooms ?? collect())
        ->map(function ($roomRow) {
            $room = $roomRow->departureHotelRoom;
            $hotel = $roomRow->tourHotel ?? $roomRow->departureHotel?->tourHotel ?? null;

            $roomCount = (int) ($roomRow->room_count ?? 0);
            $capacity = (int) (
                $roomRow->passenger_count
                ?? $room?->capacity_total
                ?? $room?->capacity
                ?? $roomRow->capacity
                ?? 0
            );
            $supplement = (float) (
                $roomRow->supplement_unit
                ?? $roomRow->supplement_total
                ?? $room?->supplement
                ?? $room?->supplement_unit
                ?? 0
            );

            return [
                'id' => $roomRow->id,
                'hotel_name' => $hotel?->hotel_name ?: $hotel?->name ?: $room?->hotel_name ?: 'Hôtel',
                'room_type' => $room?->room_type ?: $roomRow->source_room_type ?: $roomRow->room_type_snapshot ?: 'Chambre',
                'room_label' => $room?->room_label ?: $room?->name ?: null,
                'capacity' => $capacity,
                'room_count' => $roomCount,
                'supplement' => $supplement,
                'subtotal' => (float) ($roomRow->supplement_total ?? ($supplement * $roomCount)),
                'departure_hotel_room_id' => (int) ($roomRow->departure_hotel_room_id ?? 0),
                'tour_hotel_id' => (int) ($roomRow->tour_hotel_id ?? 0),
                'tour_hotel_room_id' => (int) ($roomRow->tour_hotel_room_id ?? 0),
            ];
        })
        ->filter(fn (array $row) => $row['room_count'] > 0 || $row['capacity'] > 0 || trim((string) $row['room_type']) !== '')
        ->groupBy('hotel_name')
        ->sortKeys();

    $selectedRoomCapacity = (int) $roomAllocationRows
        ->flatten(1)
        ->sum(fn (array $row) => max(0, (int) $row['capacity']) * max(0, (int) $row['room_count']));
    $selectedRoomCount = (int) $roomAllocationRows
        ->flatten(1)
        ->sum(fn (array $row) => max(0, (int) $row['room_count']));

    $baseTotal = (float) ($dossier->total_base ?? $reservation->total_base ?? 0);
    $roomSupplementTotal = (float) ($dossier->room_supplement_total ?? $reservation->room_supplement_total ?? $roomAllocationRows->flatten(1)->sum('subtotal'));
    $extrasTotal = (float) ($dossier->extras_total ?? $reservation->extras_total ?? 0);
    $totalAmount = (float) ($dossier->total_amount ?? $reservation->total_amount ?? 0);
    $paidAmount = (float) ($dossier->paid_amount ?? $reservation->paid_amount ?? 0);
    $remainingAmount = (float) ($dossier->remaining_amount ?? $reservation->remaining_amount ?? max(0, $totalAmount - $paidAmount));
    $hasFinancialData = $payments->isNotEmpty() || $baseTotal > 0 || $roomSupplementTotal > 0 || $extrasTotal > 0 || $totalAmount > 0 || $paidAmount > 0;
    $roomCoverageIncomplete = $selectedRoomCapacity < $travelersCount;
    $visaRequired = ! in_array(strtolower((string) ($reservation->visa_status ?? '')), ['not_required', 'not required', 'none'], true)
        && ! ((bool) ($offer?->requires_visa ?? true) === false);

    $paymentRows = $payments->sortByDesc(fn ($payment) => optional($payment->payment_date)->timestamp ?? optional($payment->created_at)?->timestamp ?? 0)->values();
    $documentRows = $documents->sortByDesc(fn ($document) => optional($document->created_at)?->timestamp ?? 0)->values();
    $historyRows = $histories->sortByDesc(fn ($history) => optional($history->created_at)?->timestamp ?? 0)->values();
    $extraRows = ($reservation->extras ?? collect())->sortByDesc(fn ($extra) => optional($extra->created_at)?->timestamp ?? 0)->values();

    $paymentReceiptName = $reservation->payment_receipt ?? $dossier->payment_receipt ?? null;
    $visaDocumentName = $reservation->visa_document ?? $dossier->visa_document ?? null;

    $currentRoomingPayload = $roomAllocationRows->flatten(1)->values()->map(function (array $row) {
        return [
            'departure_hotel_room_id' => $row['departure_hotel_room_id'],
            'tour_hotel_id' => $row['tour_hotel_id'],
            'tour_hotel_room_id' => $row['tour_hotel_room_id'],
            'room_count' => $row['room_count'],
        ];
    })->values();

    $currentExtrasPayload = $extraRows->values()->map(function ($extra) {
        return [
            'voyage_extra_id' => $extra->voyage_extra_id ?? null,
            'name' => $extra->name ?? 'Extra',
            'description' => $extra->description ?? null,
            'unit_price' => (float) ($extra->unit_price ?? $extra->price ?? 0),
            'quantity' => (int) ($extra->quantity ?? 1),
            'total_price' => (float) ($extra->total_price ?? 0),
            'application_scope' => $extra->application_scope ?? 'dossier',
            'traveler_keys' => $extra->traveler_keys ?? [],
        ];
    })->values();
?>

<?php $__env->startPush('styles'); ?>
<style>
    body.aj-admin-compact .reservation-dossier-page {
        max-width: 1400px;
        margin: 0 auto;
        padding-bottom: 110px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-page {
        display: grid;
        gap: 18px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-panel,
    body.aj-admin-compact .reservation-dossier-page .rd-header {
        background: #fff;
        border: 1px solid #e5edf6;
        border-radius: 18px;
        box-shadow: 0 10px 24px rgba(16, 42, 67, 0.06);
    }

    body.aj-admin-compact .reservation-dossier-page .rd-header {
        padding: 18px 20px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-header-top {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-title {
        margin: 0 0 6px;
        font-size: clamp(24px, 2.8vw, 34px);
        line-height: 1.05;
        letter-spacing: -0.04em;
        font-weight: 800;
        color: #0b2545;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-lead {
        margin: 0;
        color: #6b7a90;
        font-weight: 600;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-meta {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
        margin-top: 14px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-meta-card,
    body.aj-admin-compact .reservation-dossier-page .rd-summary-card,
    body.aj-admin-compact .reservation-dossier-page .rd-mini-card {
        border: 1px solid #e5edf6;
        border-radius: 14px;
        background: #f8fbff;
        padding: 12px 14px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-meta-card span,
    body.aj-admin-compact .reservation-dossier-page .rd-mini-card span,
    body.aj-admin-compact .reservation-dossier-page .rd-summary-card span {
        display: block;
        color: #6b7a90;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: 4px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-meta-card strong,
    body.aj-admin-compact .reservation-dossier-page .rd-mini-card strong,
    body.aj-admin-compact .reservation-dossier-page .rd-summary-card strong {
        color: #102a43;
        font-weight: 800;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 10px;
        padding: 9px 12px;
        font-weight: 700;
        text-decoration: none;
        white-space: nowrap;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-btn-sm {
        padding: 7px 11px;
        font-size: 13px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-btn-primary {
        background: linear-gradient(135deg, #0877bd, #073b63);
        color: #fff !important;
        border: 0;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-btn-soft {
        background: #fff;
        color: #073b63;
        border: 1px solid #e5edf6;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-draft { background: #eef2f7; color: #4b5d73; }
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-pending { background: #fff4e8; color: #c25b06; }
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-confirmed,
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-paid { background: #e8fff4; color: #0e8b55; }
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-cancelled,
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-unpaid { background: #fff1f2; color: #d12f45; }
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-completed,
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-refunded { background: #eef4ff; color: #2454d6; }
    body.aj-admin-compact .reservation-dossier-page .rd-pill.is-partial { background: #edf4ff; color: #2454d6; }

    body.aj-admin-compact .reservation-dossier-page .rd-tabs {
        gap: 8px;
        border: 0;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-tabs .nav-link {
        border-radius: 999px;
        border: 1px solid #e5edf6;
        color: #4f647b;
        font-weight: 700;
        background: #fff;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-tabs .nav-link.active {
        background: #073b63;
        color: #fff;
        border-color: #073b63;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-tab-content {
        margin-top: 16px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-card-grid {
        display: grid;
        gap: 16px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-summary-grid {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 10px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-summary-card.is-blue { background: linear-gradient(180deg, #eef7ff, #fff); }
    body.aj-admin-compact .reservation-dossier-page .rd-summary-card.is-green { background: linear-gradient(180deg, #ecfff5, #fff); }
    body.aj-admin-compact .reservation-dossier-page .rd-summary-card.is-orange { background: linear-gradient(180deg, #fff7eb, #fff); }
    body.aj-admin-compact .reservation-dossier-page .rd-summary-card.is-red { background: linear-gradient(180deg, #fff1f2, #fff); }

    body.aj-admin-compact .reservation-dossier-page .rd-panel-head {
        padding: 16px 18px 0;
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-panel-title {
        margin: 0;
        font-size: 17px;
        font-weight: 800;
        color: #102a43;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-panel-subtitle {
        margin: 4px 0 0;
        color: #6b7a90;
        font-size: 13px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-panel-body {
        padding: 18px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-empty {
        border: 1px dashed #cdd8e6;
        border-radius: 14px;
        padding: 16px;
        text-align: center;
        color: #6b7a90;
        background: #fbfdff;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-table {
        width: 100%;
        font-size: 13px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-table thead th {
        background: #f8fbff;
        color: #102a43;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .03em;
        font-weight: 800;
        border-bottom: 1px solid #e5edf6;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-table td,
    body.aj-admin-compact .reservation-dossier-page .rd-table th {
        padding: 11px 12px;
        border-color: #e5edf6;
        vertical-align: middle;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-label {
        display: block;
        color: #6b7a90;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        margin-bottom: 4px;
        letter-spacing: .03em;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-room-alert {
        border-radius: 14px;
        border: 1px solid #f3d39f;
        background: #fff8ea;
        color: #9a5800;
        padding: 14px 16px;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-sticky-actions {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 1050;
        display: flex;
        gap: 10px;
        align-items: center;
    }

    body.aj-admin-compact .reservation-dossier-page .rd-sticky-actions .btn {
        box-shadow: 0 14px 30px rgba(16, 42, 67, 0.16);
    }

    @media (max-width: 1199.98px) {
        body.aj-admin-compact .reservation-dossier-page .rd-meta,
        body.aj-admin-compact .reservation-dossier-page .rd-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        body.aj-admin-compact .reservation-dossier-page .rd-header-top,
        body.aj-admin-compact .reservation-dossier-page .rd-actions {
            justify-content: stretch;
        }

        body.aj-admin-compact .reservation-dossier-page .rd-actions > * {
            flex: 1 1 auto;
            justify-content: center;
        }

        body.aj-admin-compact .reservation-dossier-page .rd-meta,
        body.aj-admin-compact .reservation-dossier-page .rd-summary-grid {
            grid-template-columns: 1fr;
        }

        body.aj-admin-compact .reservation-dossier-page .rd-sticky-actions {
            left: 16px;
            right: 16px;
        }

        body.aj-admin-compact .reservation-dossier-page .rd-sticky-actions .btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
    <div class="reservation-dossier-page">
        <div class="rd-page">
            <?php if(session('success')): ?>
                <div class="alert alert-success shadow-sm border-0"><?php echo e(session('success')); ?></div>
            <?php endif; ?>

            <?php if(session('error')): ?>
                <div class="alert alert-danger shadow-sm border-0"><?php echo e(session('error')); ?></div>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger shadow-sm border-0 mb-0">
                    <strong>Des champs sont à corriger.</strong>
                    <ul class="mb-0 mt-2 ps-3">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="rd-header">
                <div class="rd-header-top">
                    <div class="flex-grow-1">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="rd-pill <?php echo e($dossierBadge['class']); ?>"><?php echo e($dossierBadge['label']); ?></span>
                            <span class="rd-pill <?php echo e($paymentBadge['class']); ?>"><?php echo e($paymentBadge['label']); ?></span>
                            <?php if($roomCoverageIncomplete): ?>
                                <span class="rd-pill is-pending">Affectation chambre incomplète</span>
                            <?php endif; ?>
                        </div>
                        <h1 class="rd-title">Dossier de réservation #<?php echo e($dossier->dossier_number ?: $dossier->id); ?></h1>
                        <p class="rd-lead">Client principal <?php echo e($clientName); ?> • Voyage <?php echo e($offer?->name ?? 'non renseigné'); ?> • Départ <?php echo e($departure?->start_date?->format('d/m/Y') ?? '—'); ?></p>

                        <div class="rd-meta">
                            <div class="rd-meta-card">
                                <span>Client principal</span>
                                <strong><?php echo e($clientName); ?></strong>
                            </div>
                            <div class="rd-meta-card">
                                <span>Voyage</span>
                                <strong><?php echo e($offer?->name ?? '—'); ?></strong>
                            </div>
                            <div class="rd-meta-card">
                                <span>Date départ</span>
                                <strong><?php echo e($departure?->start_date?->format('d/m/Y') ?? '—'); ?></strong>
                            </div>
                            <div class="rd-meta-card">
                                <span>Voyageurs</span>
                                <strong><?php echo e($travelersCount); ?></strong>
                            </div>
                        </div>
                    </div>

                    <div class="rd-actions">
                        <a href="#dossier-update-card" class="rd-btn rd-btn-soft rd-btn-sm"><i class="bx bx-edit"></i><span>Modifier dossier</span></a>
                        <a href="#payments-panel" data-bs-toggle="pill" class="rd-btn rd-btn-soft rd-btn-sm"><i class="bx bx-wallet"></i><span>Ajouter paiement</span></a>
                        <a href="#documents-panel" data-bs-toggle="pill" class="rd-btn rd-btn-soft rd-btn-sm"><i class="bx bx-file"></i><span>Ajouter justificatif</span></a>
                        <?php if($mailToUrl): ?>
                            <a href="<?php echo e($mailToUrl); ?>" class="rd-btn rd-btn-soft rd-btn-sm"><i class="bx bx-envelope"></i><span>Envoyer par email</span></a>
                        <?php endif; ?>
                        <form action="<?php echo e(route('admin.reservations.cancel', $reservation)); ?>" method="POST" onsubmit="return confirm('Annuler ce dossier ?');" class="d-inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="rd-btn rd-btn-soft rd-btn-sm text-danger"><i class="bx bx-x-circle"></i><span>Annuler réservation</span></button>
                        </form>
                    </div>
                </div>
            </section>

            <ul class="nav nav-pills rd-tabs" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#overview-panel" type="button" role="tab">Vue d’ensemble</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#payments-panel" type="button" role="tab">Paiements & documents</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#travelers-panel" type="button" role="tab">Voyageurs</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#rooms-panel" type="button" role="tab">Chambres</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#visa-panel" type="button" role="tab">Visa / Reçu</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#history-panel" type="button" role="tab">Historique</button></li>
            </ul>

            <div class="tab-content rd-tab-content">
                <div class="tab-pane fade show active" id="overview-panel" role="tabpanel">
                    <div class="rd-card-grid">
                        <section class="rd-panel">
                            <div class="rd-panel-head">
                                <div>
                                    <h2 class="rd-panel-title">Résumé financier</h2>
                                    <p class="rd-panel-subtitle">Totaux compactés pour lecture rapide et suivi du reste à payer.</p>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="rd-pill <?php echo e($paymentBadge['class']); ?>"><?php echo e($paymentBadge['label']); ?></span>
                                    <span class="rd-pill is-completed"><?php echo e($paymentProgress); ?>%</span>
                                </div>
                            </div>
                            <div class="rd-panel-body">
                                <?php if(! $hasFinancialData): ?>
                                    <div class="alert alert-warning border-0 mb-3">Aucun paiement enregistré pour ce dossier.</div>
                                <?php endif; ?>

                                <div class="rd-summary-grid mb-3">
                                    <div class="rd-summary-card is-blue"><span>Total base</span><strong><?php echo e($baseTotal > 0 ? number_format($baseTotal, 2, ',', ' ').' DH' : '—'); ?></strong></div>
                                    <div class="rd-summary-card"><span>Suppléments chambres</span><strong><?php echo e($roomSupplementTotal > 0 ? number_format($roomSupplementTotal, 2, ',', ' ').' DH' : '—'); ?></strong></div>
                                    <div class="rd-summary-card"><span>Extras</span><strong><?php echo e($extrasTotal > 0 ? number_format($extrasTotal, 2, ',', ' ').' DH' : '—'); ?></strong></div>
                                    <div class="rd-summary-card is-blue"><span>Total dossier</span><strong><?php echo e($totalAmount > 0 ? number_format($totalAmount, 2, ',', ' ').' DH' : '—'); ?></strong></div>
                                    <div class="rd-summary-card is-green"><span>Total payé</span><strong><?php echo e($paidAmount > 0 ? number_format($paidAmount, 2, ',', ' ').' DH' : '—'); ?></strong></div>
                                    <div class="rd-summary-card <?php echo e($remainingAmount > 0 ? 'is-orange' : 'is-green'); ?>"><span>Reste à payer</span><strong><?php echo e($remainingAmount > 0 ? number_format($remainingAmount, 2, ',', ' ').' DH' : '—'); ?></strong></div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                                    <div class="flex-grow-1" style="min-width: 260px;">
                                        <div class="d-flex justify-content-between mb-1"><small class="rd-muted">Progression paiement</small><strong><?php echo e($paymentProgress); ?>%</strong></div>
                                        <div class="progress" style="height:10px;border-radius:999px;">
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo e($paymentProgress); ?>%; background: linear-gradient(135deg, #0877bd, #12b76a);"></div>
                                        </div>
                                    </div>
                                    <a href="#payments-panel" data-bs-toggle="pill" class="rd-btn rd-btn-soft rd-btn-sm"><i class="bx bx-wallet"></i><span>Aller aux paiements</span></a>
                                </div>
                            </div>
                        </section>

                        <section class="rd-panel" id="dossier-update-card">
                            <div class="rd-panel-head">
                                <div>
                                    <h2 class="rd-panel-title">Informations dossier</h2>
                                    <p class="rd-panel-subtitle">Zone compacte de modification du dossier et des voyageurs.</p>
                                </div>
                                <a href="<?php echo e(route('admin.reservations.edit', $reservation)); ?>" class="rd-btn rd-btn-soft rd-btn-sm"><i class="bx bx-external-link"></i><span>Ouvrir l’édition complète</span></a>
                            </div>
                            <div class="rd-panel-body">
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.update')): ?>
                                    <form id="dossier-update-form" action="<?php echo e(route('admin.reservations.update', $reservation)); ?>" method="POST" enctype="multipart/form-data">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>

                                        <input type="hidden" name="accommodation_mode" value="rooms">
                                        <input type="hidden" name="departure_id" value="<?php echo e($currentDepartureId); ?>">
                                        <input type="hidden" name="travel_date_id" value="<?php echo e($currentTravelDateId); ?>">
                                        <input type="hidden" name="client_mode" value="new">
                                        <input type="hidden" name="client_traveler_type" value="adult">
                                        <input type="hidden" name="extras_json" value='<?php echo json_encode($currentExtrasPayload, JSON_UNESCAPED_UNICODE, 512) ?>'>

                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="rd-label">Offre / voyage</label>
                                                <select name="tour_id" class="form-select" required>
                                                    <option value="">Sélectionner un voyage…</option>
                                                    <?php $__currentLoopData = $voyages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voyage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($voyage->id); ?>" <?php if((int) old('tour_id', $currentTourId) === (int) $voyage->id): echo 'selected'; endif; ?>><?php echo e($voyage->name ?? $voyage->slug); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="rd-label">Statut dossier</label>
                                                <div class="form-control d-flex align-items-center justify-content-between bg-light">
                                                    <span><?php echo e($dossierBadge['label']); ?></span>
                                                    <span class="rd-pill <?php echo e($dossierBadge['class']); ?>"><?php echo e($dossierBadge['label']); ?></span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="rd-label">Mode de paiement</label>
                                                <select name="payment_type" class="form-select">
                                                    <option value="">—</option>
                                                    <?php $__currentLoopData = ['Espèces', 'Virement bancaire', 'Carte bancaire', 'Chèque', 'TPE', 'Autre']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $paymentType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <option value="<?php echo e($paymentType); ?>" <?php if(old('payment_type', $reservation->payment_type) === $paymentType): echo 'selected'; endif; ?>><?php echo e($paymentType); ?></option>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <div class="rd-room-alert">
                                                    <strong>Voyageurs couverts :</strong> <?php echo e(min($selectedRoomCapacity, $travelersCount)); ?>/<?php echo e($travelersCount); ?> voyageurs.
                                                    <?php if($roomCoverageIncomplete): ?>
                                                        Affectation chambre incomplète : <?php echo e($selectedRoomCapacity); ?>/<?php echo e($travelersCount); ?> voyageurs couverts.
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="rd-label">Prénom du client principal</label>
                                                        <input type="text" name="client_first_name" class="form-control" value="<?php echo e(old('client_first_name', $client?->first_name ?? $reservation->client_first_name ?? '')); ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="rd-label">Nom du client principal</label>
                                                        <input type="text" name="client_last_name" class="form-control" value="<?php echo e(old('client_last_name', $client?->last_name ?? $reservation->client_last_name ?? '')); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="rd-label">Téléphone</label>
                                                        <input type="text" name="client_phone" class="form-control" value="<?php echo e(old('client_phone', $client?->phone ?? $reservation->client_phone ?? '')); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="rd-label">Email</label>
                                                        <input type="email" name="client_email" class="form-control" value="<?php echo e(old('client_email', $client?->email ?? $reservation->client_email ?? '')); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="rd-label">Date de naissance</label>
                                                        <input type="date" name="client_birth_date" class="form-control" value="<?php echo e(old('client_birth_date', optional($client?->birth_date)->format('Y-m-d') ?? optional($reservation->client_birth_date)->format('Y-m-d'))); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="rd-label">Type document</label>
                                                        <input type="text" name="client_document_type" class="form-control" value="<?php echo e(old('client_document_type', $client?->document_type ?? $reservation->client_document_type ?? '')); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="rd-label">Numéro document</label>
                                                        <input type="text" name="client_document_number" class="form-control" value="<?php echo e(old('client_document_number', $client?->document_number ?? $reservation->client_document_number ?? '')); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="rd-label">Nationalité</label>
                                                        <input type="text" name="client_nationality" class="form-control" value="<?php echo e(old('client_nationality', $client?->nationality ?? '')); ?>">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="rd-label">Adresse</label>
                                                        <input type="text" name="client_address" class="form-control" value="<?php echo e(old('client_address', $client?->address_line_1 ?? $reservation->client?->address_line_1 ?? '')); ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2 flex-wrap">
                                                    <div>
                                                        <h3 class="rd-panel-title mb-0">Accompagnants</h3>
                                                        <p class="rd-panel-subtitle mb-0">Chaque voyageur occupe une seule ligne claire.</p>
                                                    </div>
                                                    <button type="button" class="rd-btn rd-btn-soft rd-btn-sm" id="btn-add-companion"><i class="bx bx-plus"></i><span>Ajouter accompagnant</span></button>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-bordered align-middle rd-table mb-0" id="companions-table">
                                                        <thead>
                                                            <tr>
                                                                <th>Type</th>
                                                                <th>Prénom</th>
                                                                <th>Nom</th>
                                                                <th>Date naissance</th>
                                                                <th>Type document</th>
                                                                <th>Numéro document</th>
                                                                <th class="text-end">Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="companions-container">
                                                            <?php $__currentLoopData = $companionTravelers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $traveler): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <tr class="companion-row">
                                                                    <td style="width: 120px;">
                                                                        <input type="hidden" name="passengers[<?php echo e($index); ?>][id]" value="<?php echo e($traveler['id'] ?? ''); ?>">
                                                                        <select name="passengers[<?php echo e($index); ?>][type]" class="form-select form-select-sm">
                                                                            <option value="adult" <?php if(($traveler['type'] ?? '') === 'adult'): echo 'selected'; endif; ?>>Adulte</option>
                                                                            <option value="child" <?php if(($traveler['type'] ?? '') === 'child'): echo 'selected'; endif; ?>>Enfant</option>
                                                                            <option value="infant" <?php if(($traveler['type'] ?? '') === 'infant'): echo 'selected'; endif; ?>>Bébé</option>
                                                                        </select>
                                                                    </td>
                                                                    <td><input type="text" name="passengers[<?php echo e($index); ?>][first_name]" class="form-control form-control-sm" value="<?php echo e($traveler['first_name'] ?? ''); ?>"></td>
                                                                    <td><input type="text" name="passengers[<?php echo e($index); ?>][last_name]" class="form-control form-control-sm" value="<?php echo e($traveler['last_name'] ?? ''); ?>"></td>
                                                                    <td><input type="date" name="passengers[<?php echo e($index); ?>][birth_date]" class="form-control form-control-sm" value="<?php echo e($traveler['birth_date'] ?? ''); ?>"></td>
                                                                    <td><input type="text" name="passengers[<?php echo e($index); ?>][document_type]" class="form-control form-control-sm" value="<?php echo e($traveler['document_type'] ?? ''); ?>"></td>
                                                                    <td><input type="text" name="passengers[<?php echo e($index); ?>][document_number]" class="form-control form-control-sm" value="<?php echo e($traveler['document_number'] ?? ''); ?>"></td>
                                                                    <td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-companion">Supprimer</button></td>
                                                                </tr>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <?php $__currentLoopData = $currentRoomingPayload; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roomIndex => $roomPayload): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][departure_hotel_room_id]" value="<?php echo e($roomPayload['departure_hotel_room_id']); ?>">
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][tour_hotel_id]" value="<?php echo e($roomPayload['tour_hotel_id']); ?>">
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][tour_hotel_room_id]" value="<?php echo e($roomPayload['tour_hotel_room_id']); ?>">
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][room_count]" value="<?php echo e($roomPayload['room_count']); ?>">
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </form>
                                <?php else: ?>
                                    <div class="rd-empty">Vous n’avez pas l’autorisation de modifier ce dossier.</div>
                                <?php endif; ?>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="tab-pane fade" id="payments-panel" role="tabpanel">
                    <div class="rd-card-grid">
                        <section class="rd-panel">
                            <div class="rd-panel-head">
                                <div>
                                    <h2 class="rd-panel-title">Paiements</h2>
                                    <p class="rd-panel-subtitle">Liste des encaissements puis formulaire compact en bloc pliable.</p>
                                </div>
                                <button class="rd-btn rd-btn-primary rd-btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#payment-form-collapse"><i class="bx bx-wallet"></i><span>Ajouter paiement</span></button>
                            </div>
                            <div class="rd-panel-body">
                                <?php if($paymentRows->isNotEmpty()): ?>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-hover align-middle rd-table mb-0">
                                            <thead>
                                                <tr><th>Date</th><th>Mode</th><th>Référence</th><th>Justificatif</th><th class="text-end">Montant</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php $__currentLoopData = $paymentRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td><?php echo e(optional($payment->payment_date ?? $payment->created_at)->format('d/m/Y') ?? '—'); ?></td>
                                                        <td><?php echo e($payment->payment_method ?? '—'); ?></td>
                                                        <td><?php echo e($payment->reference ?? '—'); ?></td>
                                                        <td><?php echo e($payment->proof_file ?? '—'); ?></td>
                                                        <td class="text-end fw-bold"><?php echo e(number_format((float) ($payment->amount ?? 0), 2, ',', ' ')); ?> DH</td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="rd-empty mb-3">Aucun paiement enregistré pour ce dossier.</div>
                                <?php endif; ?>

                                <div class="collapse" id="payment-form-collapse">
                                    <form action="<?php echo e(route('admin.reservations.payments.store', $reservation)); ?>" method="POST" enctype="multipart/form-data" class="row g-3 pt-2 border-top">
                                        <?php echo csrf_field(); ?>
                                        <div class="col-md-3"><label class="rd-label">Date paiement</label><input type="date" name="payment_date" class="form-control" value="<?php echo e(now()->toDateString()); ?>" required></div>
                                        <div class="col-md-3"><label class="rd-label">Mode paiement</label><select name="payment_method" class="form-select" required><option value="ESPECE">Espèce</option><option value="VIREMENT">Virement</option><option value="CASHPLUS">Cash Plus</option><option value="CARTE">Carte</option><option value="AUTRE">Autre</option></select></div>
                                        <div class="col-md-2"><label class="rd-label">Montant payé</label><input type="number" step="0.01" min="0.01" name="amount" class="form-control" required></div>
                                        <div class="col-md-2"><label class="rd-label">Référence</label><input type="text" name="reference" class="form-control"></div>
                                        <div class="col-md-2"><label class="rd-label">Justificatif</label><input type="file" name="proof_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
                                        <div class="col-12"><label class="rd-label">Note interne</label><textarea name="note" class="form-control" rows="3"></textarea></div>
                                        <div class="col-12 text-end"><button type="submit" class="rd-btn rd-btn-primary rd-btn-sm"><i class="bx bx-save"></i><span>Enregistrer le paiement</span></button></div>
                                    </form>
                                </div>
                            </div>
                        </section>

                        <section class="rd-panel">
                            <div class="rd-panel-head">
                                <div>
                                    <h2 class="rd-panel-title">Documents</h2>
                                    <p class="rd-panel-subtitle">Pièces du dossier séparées du paiement.</p>
                                </div>
                                <button class="rd-btn rd-btn-primary rd-btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#document-form-collapse"><i class="bx bx-file"></i><span>Ajouter document</span></button>
                            </div>
                            <div class="rd-panel-body">
                                <?php if($documentRows->isNotEmpty()): ?>
                                    <div class="table-responsive mb-3">
                                        <table class="table table-hover align-middle rd-table mb-0">
                                            <thead><tr><th>Type</th><th>Titre</th><th>Date</th><th class="text-end">Action</th></tr></thead>
                                            <tbody>
                                                <?php $__currentLoopData = $documentRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <tr>
                                                        <td><?php echo e(ucfirst(str_replace('_', ' ', (string) $document->type))); ?></td>
                                                        <td><?php echo e($document->title); ?></td>
                                                        <td><?php echo e(optional($document->created_at)->format('d/m/Y H:i') ?? '—'); ?></td>
                                                        <td class="text-end">
                                                            <?php if($documentUrl($document)): ?>
                                                                <a href="<?php echo e($documentUrl($document)); ?>" target="_blank" rel="noopener" class="rd-btn rd-btn-soft rd-btn-sm"><i class="bx bx-download"></i><span>Ouvrir</span></a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <div class="rd-empty mb-3">Aucun document chargé pour ce dossier.</div>
                                <?php endif; ?>

                                <div class="collapse" id="document-form-collapse">
                                    <form action="<?php echo e(route('admin.reservations.documents.store', $reservation)); ?>" method="POST" enctype="multipart/form-data" class="row g-3 pt-2 border-top">
                                        <?php echo csrf_field(); ?>
                                        <div class="col-md-4">
                                            <label class="rd-label">Type</label>
                                            <select name="type" class="form-select" required>
                                                <option value="invoice">Facture</option>
                                                <option value="payment_receipt">Reçu paiement</option>
                                                <option value="passport">Passeport</option>
                                                <option value="booking_voucher">Bon de réservation</option>
                                                <option value="visa">Visa</option>
                                                <option value="voucher">Voucher</option>
                                                <option value="other">Autre fichier</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4"><label class="rd-label">Titre</label><input type="text" name="title" class="form-control" placeholder="Ex. Reçu acompte" required></div>
                                        <div class="col-md-4"><label class="rd-label">Fichier</label><input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required></div>
                                        <div class="col-12 text-end"><button type="submit" class="rd-btn rd-btn-primary rd-btn-sm"><i class="bx bx-upload"></i><span>Ajouter document</span></button></div>
                                    </form>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="tab-pane fade" id="travelers-panel" role="tabpanel">
                    <section class="rd-panel">
                        <div class="rd-panel-head">
                            <div>
                                <h2 class="rd-panel-title">Voyageurs</h2>
                                <p class="rd-panel-subtitle">Client principal et accompagnants, affichés ligne par ligne.</p>
                            </div>
                            <span class="rd-pill is-completed"><?php echo e($travelersCount); ?> voyageurs</span>
                        </div>
                        <div class="rd-panel-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle rd-table mb-0">
                                    <thead>
                                        <tr><th>Type voyageur</th><th>Prénom</th><th>Nom</th><th>Date naissance</th><th>Type document</th><th>Numéro document</th><th>Action</th></tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Principal</td>
                                            <td><?php echo e($client?->first_name ?? $reservation->client_first_name ?? '—'); ?></td>
                                            <td><?php echo e($client?->last_name ?? $reservation->client_last_name ?? '—'); ?></td>
                                            <td><?php echo e(optional($client?->birth_date)->format('d/m/Y') ?? optional($reservation->client_birth_date)->format('d/m/Y') ?? '—'); ?></td>
                                            <td><?php echo e($client?->document_type ?? $reservation->client_document_type ?? '—'); ?></td>
                                            <td><?php echo e($client?->document_number ?? $reservation->client_document_number ?? '—'); ?></td>
                                            <td class="text-end"><span class="rd-pill is-completed">Client principal</span></td>
                                        </tr>
                                        <?php $__empty_1 = true; $__currentLoopData = $companionTravelers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $traveler): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                            <tr>
                                                <td><?php echo e(ucfirst((string) ($traveler['type'] ?? 'adult'))); ?></td>
                                                <td><?php echo e($traveler['first_name'] ?? '—'); ?></td>
                                                <td><?php echo e($traveler['last_name'] ?? '—'); ?></td>
                                                <td><?php echo e(! empty($traveler['birth_date']) ? \Illuminate\Support\Carbon::parse($traveler['birth_date'])->format('d/m/Y') : '—'); ?></td>
                                                <td><?php echo e($traveler['document_type'] ?? '—'); ?></td>
                                                <td><?php echo e($traveler['document_number'] ?? '—'); ?></td>
                                                <td class="text-end"><span class="text-muted small">—</span></td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                            <tr><td colspan="7"><div class="rd-empty">Aucun accompagnant enregistré.</div></td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade" id="rooms-panel" role="tabpanel">
                    <section class="rd-panel">
                        <div class="rd-panel-head">
                            <div>
                                <h2 class="rd-panel-title">Hôtels et chambres</h2>
                                <p class="rd-panel-subtitle">Chambres réelles seulement, sans blocs vides ni debug en production.</p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <span class="rd-pill is-completed"><?php echo e($selectedRoomCapacity); ?>/<?php echo e($travelersCount); ?> couverts</span>
                                <span class="rd-pill <?php echo e($roomCoverageIncomplete ? 'is-pending' : 'is-paid'); ?>"><?php echo e($selectedRoomCount); ?> chambres sélectionnées</span>
                            </div>
                        </div>
                        <div class="rd-panel-body">
                            <?php if($roomCoverageIncomplete): ?>
                                <div class="alert alert-warning border-0 mb-3">Affectation chambre incomplète : <?php echo e($selectedRoomCapacity); ?>/<?php echo e($travelersCount); ?> voyageurs couverts.</div>
                            <?php endif; ?>

                            <?php if($roomAllocationRows->isEmpty()): ?>
                                <div class="rd-empty">Aucune chambre disponible/configurée pour ce départ.</div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php $__currentLoopData = $roomAllocationRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hotelName => $hotelRows): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="col-12">
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                    <strong><?php echo e($hotelName); ?></strong>
                                                    <span class="rd-pill is-completed"><?php echo e($hotelRows->count()); ?> ligne<?php echo e($hotelRows->count() > 1 ? 's' : ''); ?></span>
                                                </div>
                                                <div class="card-body p-0">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm align-middle rd-table mb-0">
                                                            <thead>
                                                                <tr><th>Type chambre</th><th class="text-center">Places dispo</th><th class="text-center">Chambres dispo</th><th class="text-center">Capacité</th><th class="text-end">Supplément</th><th class="text-end">Sous-total</th></tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php $__currentLoopData = $hotelRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roomRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <tr>
                                                                        <td><?php echo e($roomRow['room_type']); ?><?php echo e($roomRow['room_label'] ? ' — '.$roomRow['room_label'] : ''); ?></td>
                                                                        <td class="text-center"><?php echo e($roomRow['capacity'] > 0 ? $roomRow['capacity'].' pers.' : '—'); ?></td>
                                                                        <td class="text-center"><?php echo e($roomRow['room_count']); ?></td>
                                                                        <td class="text-center"><?php echo e($roomRow['capacity'] > 0 ? $roomRow['capacity'].' pers.' : '—'); ?></td>
                                                                        <td class="text-end"><?php echo e($roomRow['supplement'] > 0 ? number_format((float) $roomRow['supplement'], 2, ',', ' ').' DH' : '—'); ?></td>
                                                                        <td class="text-end fw-bold"><?php echo e($roomRow['subtotal'] > 0 ? number_format((float) $roomRow['subtotal'], 2, ',', ' ').' DH' : '—'); ?></td>
                                                                    </tr>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>

                            <?php if(config('app.debug')): ?>
                                <details class="mt-3">
                                    <summary class="rd-label mb-2">Debug technique chambres</summary>
                                    <pre class="small mb-0"><?php echo json_encode($roomAllocationRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE, 512) ?></pre>
                                </details>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade" id="visa-panel" role="tabpanel">
                    <section class="rd-panel">
                        <div class="rd-panel-head">
                            <div>
                                <h2 class="rd-panel-title">Visa / Reçu</h2>
                                <p class="rd-panel-subtitle">Section réduite si le voyage ne nécessite pas de visa.</p>
                            </div>
                            <?php if(! $visaRequired): ?>
                                <span class="rd-pill is-paid">Visa non requis</span>
                            <?php endif; ?>
                        </div>
                        <div class="rd-panel-body">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="rd-mini-card h-100"><span>Reçu principal</span><strong><?php echo e($paymentReceiptName ?: 'Aucun fichier'); ?></strong></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="rd-mini-card h-100"><span>Document visa</span><strong><?php echo e($visaDocumentName ?: 'Aucun fichier'); ?></strong></div>
                                </div>
                            </div>

                            <?php if($visaRequired): ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.update')): ?>
                                    <form action="<?php echo e(route('admin.reservations.update', $reservation)); ?>" method="POST" enctype="multipart/form-data" class="row g-3">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PUT'); ?>
                                        <input type="hidden" name="tour_id" value="<?php echo e($currentTourId); ?>">
                                        <input type="hidden" name="departure_id" value="<?php echo e($currentDepartureId); ?>">
                                        <input type="hidden" name="travel_date_id" value="<?php echo e($currentTravelDateId); ?>">
                                        <input type="hidden" name="client_mode" value="new">
                                        <input type="hidden" name="client_first_name" value="<?php echo e($client?->first_name ?? $reservation->client_first_name ?? ''); ?>">
                                        <input type="hidden" name="client_last_name" value="<?php echo e($client?->last_name ?? $reservation->client_last_name ?? ''); ?>">
                                        <input type="hidden" name="client_phone" value="<?php echo e($client?->phone ?? $reservation->client_phone ?? ''); ?>">
                                        <input type="hidden" name="client_email" value="<?php echo e($client?->email ?? $reservation->client_email ?? ''); ?>">
                                        <input type="hidden" name="client_document_type" value="<?php echo e($client?->document_type ?? $reservation->client_document_type ?? ''); ?>">
                                        <input type="hidden" name="client_document_number" value="<?php echo e($client?->document_number ?? $reservation->client_document_number ?? ''); ?>">
                                        <input type="hidden" name="client_birth_date" value="<?php echo e(optional($client?->birth_date)->format('Y-m-d') ?? optional($reservation->client_birth_date)->format('Y-m-d')); ?>">
                                        <input type="hidden" name="client_nationality" value="<?php echo e($client?->nationality ?? ''); ?>">
                                        <input type="hidden" name="client_address" value="<?php echo e($client?->address_line_1 ?? ''); ?>">
                                        <input type="hidden" name="payment_type" value="<?php echo e($reservation->payment_type ?? ''); ?>">
                                        <input type="hidden" name="client_traveler_type" value="adult">
                                        <input type="hidden" name="accommodation_mode" value="rooms">
                                        <input type="hidden" name="extras_json" value='<?php echo json_encode($currentExtrasPayload, JSON_UNESCAPED_UNICODE, 512) ?>'>
                                        <?php $__currentLoopData = $currentRoomingPayload; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roomIndex => $roomPayload): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][departure_hotel_room_id]" value="<?php echo e($roomPayload['departure_hotel_room_id']); ?>">
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][tour_hotel_id]" value="<?php echo e($roomPayload['tour_hotel_id']); ?>">
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][tour_hotel_room_id]" value="<?php echo e($roomPayload['tour_hotel_room_id']); ?>">
                                            <input type="hidden" name="hotel_rooms[<?php echo e($roomIndex); ?>][room_count]" value="<?php echo e($roomPayload['room_count']); ?>">
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

                                        <div class="col-md-6"><label class="rd-label">Remplacer reçu principal</label><input type="file" name="payment_receipt" class="form-control" accept="image/*,.pdf"></div>
                                        <div class="col-md-6"><label class="rd-label">Remplacer document visa</label><input type="file" name="visa_document" class="form-control" accept="image/*,.pdf"></div>
                                        <div class="col-md-3"><label class="rd-label">Visa OK</label><input type="hidden" name="visa_ok" value="0"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="visa_ok" id="visa_ok" value="1" <?php echo e(old('visa_ok', $reservation->visa_ok ?? true) ? 'checked' : ''); ?>><label class="form-check-label" for="visa_ok">Visa OK</label></div></div>
                                        <div class="col-md-9"><label class="rd-label">Notes visa</label><textarea name="visa_notes" class="form-control" rows="3"><?php echo e(old('visa_notes', $reservation->visa_notes)); ?></textarea></div>
                                        <div class="col-12 text-end"><button type="submit" class="rd-btn rd-btn-primary rd-btn-sm"><i class="bx bx-save"></i><span>Enregistrer la zone visa</span></button></div>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="rd-empty">Le voyage ne nécessite pas de visa. La section reste réduite.</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade" id="history-panel" role="tabpanel">
                    <section class="rd-panel">
                        <div class="rd-panel-head">
                            <div>
                                <h2 class="rd-panel-title">Historique du dossier</h2>
                                <p class="rd-panel-subtitle">Journal des événements, paiements, notes et changements.</p>
                            </div>
                        </div>
                        <div class="rd-panel-body">
                            <?php if($historyRows->isNotEmpty()): ?>
                                <div class="list-group list-group-flush">
                                    <?php $__currentLoopData = $historyRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $history): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <?php ($timeline = $historyMeta($history)); ?>
                                        <div class="list-group-item px-0 border-0 border-bottom">
                                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                                <div>
                                                    <div class="fw-bold text-dark"><?php echo e($timeline['label']); ?></div>
                                                    <?php if($timeline['details'] !== ''): ?>
                                                        <div class="small text-muted mt-1"><?php echo e($timeline['details']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-end small text-muted">
                                                    <div><?php echo e(optional($history->created_at)->format('d/m/Y H:i') ?? '—'); ?></div>
                                                    <div><?php echo e($history->user?->name ?? 'Système'); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php else: ?>
                                <div class="rd-empty">Aucun historique disponible pour ce dossier.</div>
                            <?php endif; ?>
                        </div>
                    </section>
                </div>
            </div>

            <div class="rd-sticky-actions">
                <a href="<?php echo e($backUrl ?? route('admin.reservation-dossiers.index')); ?>" class="btn btn-outline-secondary btn-sm">Retour liste</a>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('reservations.update')): ?>
                    <button type="submit" form="dossier-update-form" class="btn btn-primary btn-sm" id="rd-save-button">Enregistrer les modifications</button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php $__env->startPush('scripts'); ?>
        <script>
            (function () {
                var table = document.getElementById('companions-table');
                var addButton = document.getElementById('btn-add-companion');
                if (!table || !addButton) {
                    return;
                }

                var body = document.getElementById('companions-container');

                function nextIndex() {
                    return body ? body.querySelectorAll('.companion-row').length : 0;
                }

                addButton.addEventListener('click', function () {
                    if (!body) {
                        return;
                    }

                    var index = nextIndex();
                    var row = document.createElement('tr');
                    row.className = 'companion-row';
                    row.innerHTML = '' +
                        '<td><select name="passengers[' + index + '][type]" class="form-select form-select-sm"><option value="adult">Adulte</option><option value="child">Enfant</option><option value="infant">Bébé</option></select></td>' +
                        '<td><input type="text" name="passengers[' + index + '][first_name]" class="form-control form-control-sm"></td>' +
                        '<td><input type="text" name="passengers[' + index + '][last_name]" class="form-control form-control-sm"></td>' +
                        '<td><input type="date" name="passengers[' + index + '][birth_date]" class="form-control form-control-sm"></td>' +
                        '<td><input type="text" name="passengers[' + index + '][document_type]" class="form-control form-control-sm"></td>' +
                        '<td><input type="text" name="passengers[' + index + '][document_number]" class="form-control form-control-sm"></td>' +
                        '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger btn-remove-companion">Supprimer</button></td>';
                    body.appendChild(row);
                });

                body.addEventListener('click', function (event) {
                    var button = event.target.closest('.btn-remove-companion');
                    if (!button) {
                        return;
                    }

                    var row = button.closest('.companion-row');
                    if (row) {
                        row.remove();
                    }
                });
            })();

            (function () {
                var saveButton = document.getElementById('rd-save-button');
                if (!saveButton) {
                    return;
                }

                saveButton.addEventListener('click', function () {
                    saveButton.disabled = true;
                    saveButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Enregistrement...';
                });
            })();
        </script>
    <?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin-v2', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\Users\oussa\Desktop\themeforest-uMqxCtcU-qovex-laravel-admin-dashboard-template\Qovex_Laravel_v3.0.0\Admin\resources\views\admin\reservation-dossiers\show.blade.php ENDPATH**/ ?>