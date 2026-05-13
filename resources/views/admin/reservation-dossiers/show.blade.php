@extends('layouts.admin-v2')

@section('title', 'Dossier de réservation')

@php
    use App\Models\Reservation;
    use Illuminate\Support\Facades\Storage;

    $backUrl = $backUrl ?? route('admin.reservation-dossiers.index');
    $offerImageUrl = $offerImageUrl ?? null;
    $offer = $offer ?? null;
    $relatedReservations = $relatedReservations ?? collect();
    $allClientReservationsUrl = $allClientReservationsUrl ?? url('/admin/reservations');
    $client = $dossier->client ?: $reservation->client;
    $offer = $offer ?: ($reservation->offer ?? $reservation->voyage ?? $reservation->tour ?? null);
    $departure = $reservation->departure;
    $payments = $dossier->payments->isNotEmpty() ? $dossier->payments : ($reservation->payments ?? collect());
    $documents = $dossier->documents->isNotEmpty() ? $dossier->documents : ($reservation->documents ?? collect());
    $histories = $dossier->histories->isNotEmpty() ? $dossier->histories : ($reservation->histories ?? collect());
    $passengers = $reservation->passengers ?? collect();
    $totalAmount = (float) ($dossier->total_amount ?? $reservation->total_amount ?? 0);
    $paidAmount = (float) ($dossier->paid_amount ?? $reservation->paid_amount ?? 0);
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
    $notesContent = trim((string) ($reservation->notes ?? ''));

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

        return Storage::disk('public')->url($document->file_path);
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
@endphp

@push('styles')
<style>
    :root {
        --dossier-blue-900: #073b63;
        --dossier-blue-800: #07598f;
        --dossier-blue-700: #0877bd;
        --dossier-blue-100: #e8f4ff;
        --dossier-orange: #f97316;
        --dossier-green: #12b76a;
        --dossier-red: #ef4444;
        --dossier-amber: #f59e0b;
        --dossier-slate: #102a43;
        --dossier-muted: #6b7a90;
        --dossier-line: #e5edf6;
        --dossier-bg: #f5f8fc;
        --dossier-white: #ffffff;
        --dossier-shadow: 0 18px 36px rgba(16, 42, 67, 0.08);
        --dossier-shadow-soft: 0 10px 24px rgba(16, 42, 67, 0.06);
        --dossier-radius-xl: 24px;
        --dossier-radius-lg: 18px;
        --dossier-radius-md: 14px;
    }

    .dossier-shell {
        display: grid;
        gap: 24px;
    }

    .dossier-page-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 18px;
    }

    .dossier-page-head h1 {
        font-size: clamp(28px, 3vw, 38px);
        line-height: 1.05;
        letter-spacing: -0.04em;
        color: #0b2545;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .dossier-page-head p {
        color: var(--dossier-muted);
        margin-bottom: 0;
    }

    .dossier-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .dossier-btn {
        border: 0;
        border-radius: 12px;
        padding: 11px 16px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        cursor: pointer;
    }

    .dossier-btn-primary {
        color: #fff !important;
        background: linear-gradient(135deg, var(--dossier-blue-700), var(--dossier-blue-900));
        box-shadow: 0 10px 20px rgba(8, 119, 189, 0.22);
    }

    .dossier-btn-soft {
        color: var(--dossier-blue-900);
        background: #fff;
        border: 1px solid var(--dossier-line);
        box-shadow: var(--dossier-shadow-soft);
    }

    .dossier-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: 999px;
        padding: 7px 12px;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .dossier-pill.is-draft { background: #eef2f7; color: #4b5d73; }
    .dossier-pill.is-pending { background: #fff4e8; color: #c25b06; }
    .dossier-pill.is-confirmed, .dossier-pill.is-paid { background: #e8fff4; color: #0e8b55; }
    .dossier-pill.is-cancelled, .dossier-pill.is-unpaid { background: #fff1f2; color: #d12f45; }
    .dossier-pill.is-completed, .dossier-pill.is-refunded { background: #eef4ff; color: #2454d6; }
    .dossier-pill.is-partial { background: #edf4ff; color: #2454d6; }

    .dossier-card {
        background: var(--dossier-white);
        border: 1px solid var(--dossier-line);
        border-radius: var(--dossier-radius-xl);
        box-shadow: var(--dossier-shadow-soft);
    }

    .dossier-card-header {
        padding: 20px 22px 0;
    }

    .dossier-card-body {
        padding: 22px;
    }

    .dossier-card-title {
        font-size: 18px;
        color: var(--dossier-slate);
        font-weight: 800;
        margin-bottom: 4px;
    }

    .dossier-card-subtitle {
        color: var(--dossier-muted);
        font-size: 13px;
        margin-bottom: 0;
    }

    .dossier-hero {
        display: grid;
        grid-template-columns: minmax(280px, 360px) 1fr;
        overflow: hidden;
    }

    .dossier-hero-media {
        min-height: 280px;
        position: relative;
        background: linear-gradient(135deg, #dcecff, #f5f8fc);
    }

    .dossier-hero-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .dossier-hero-placeholder {
        height: 100%;
        display: grid;
        place-items: center;
        text-align: center;
        color: #7c8ea6;
        font-weight: 700;
        padding: 24px;
    }

    .dossier-hero-copy {
        padding: 28px;
        display: grid;
        gap: 18px;
        background:
            radial-gradient(circle at top right, rgba(8, 119, 189, 0.12), transparent 26%),
            linear-gradient(180deg, #ffffff, #fbfdff);
    }

    .dossier-hero-title {
        font-size: clamp(24px, 3vw, 34px);
        line-height: 1.08;
        color: #08233f;
        font-weight: 800;
        letter-spacing: -0.04em;
        margin-bottom: 8px;
    }

    .dossier-hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .dossier-meta-card {
        min-width: 140px;
        background: #f8fbff;
        border: 1px solid var(--dossier-line);
        border-radius: 16px;
        padding: 14px 16px;
    }

    .dossier-meta-card span {
        display: block;
        color: var(--dossier-muted);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        margin-bottom: 6px;
    }

    .dossier-meta-card strong {
        display: block;
        color: var(--dossier-slate);
        font-size: 16px;
        font-weight: 800;
    }

    .dossier-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.75fr) minmax(320px, 0.95fr);
        gap: 24px;
    }

    .dossier-stack {
        display: grid;
        gap: 24px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px 18px;
    }

    .info-item {
        padding: 14px 16px;
        border-radius: 16px;
        background: #f8fbff;
        border: 1px solid var(--dossier-line);
    }

    .info-item span {
        display: block;
        color: var(--dossier-muted);
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    .info-item strong, .info-item div {
        color: var(--dossier-slate);
        font-weight: 700;
    }

    .passenger-list, .related-list, .document-list, .notes-list, .timeline-list, .actions-list {
        display: grid;
        gap: 14px;
    }

    .passenger-row, .related-row, .document-row, .note-row, .timeline-row, .action-row {
        border: 1px solid var(--dossier-line);
        border-radius: 16px;
        background: #fff;
        padding: 16px;
    }

    .passenger-head, .related-head, .document-head, .timeline-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
    }

    .passenger-name, .related-title, .document-title {
        color: var(--dossier-slate);
        font-weight: 800;
    }

    .passenger-meta, .related-meta, .document-meta, .note-meta, .timeline-meta {
        color: var(--dossier-muted);
        font-size: 13px;
    }

    .passenger-badge {
        background: #edf4ff;
        color: #2454d6;
        border-radius: 999px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .related-price {
        font-weight: 800;
        color: var(--dossier-slate);
        white-space: nowrap;
    }

    .summary-grid {
        display: grid;
        gap: 16px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: center;
        color: var(--dossier-slate);
        font-weight: 700;
    }

    .summary-row span {
        color: var(--dossier-muted);
        font-weight: 700;
    }

    .summary-highlight {
        padding: 18px;
        border-radius: 18px;
        background: linear-gradient(135deg, #eef7ff, #ffffff);
        border: 1px solid #dcecff;
    }

    .progress-shell {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: #edf2f7;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(135deg, var(--dossier-blue-700), var(--dossier-green));
    }

    .nav-pills.dossier-tabs {
        gap: 10px;
        margin-bottom: 18px;
    }

    .nav-pills.dossier-tabs .nav-link {
        border-radius: 999px;
        padding: 9px 14px;
        border: 1px solid var(--dossier-line);
        color: #4f647b;
        font-weight: 700;
        background: #fff;
    }

    .nav-pills.dossier-tabs .nav-link.active {
        background: var(--dossier-blue-800);
        border-color: var(--dossier-blue-800);
        color: #fff;
    }

    .dossier-form-grid {
        display: grid;
        gap: 12px;
        margin-top: 16px;
    }

    .timeline-list {
        position: relative;
        gap: 18px;
    }

    .timeline-row {
        position: relative;
        padding-left: 28px;
    }

    .timeline-row::before {
        content: "";
        position: absolute;
        left: 0;
        top: 8px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--dossier-blue-700), var(--dossier-orange));
        box-shadow: 0 0 0 5px #eef6ff;
    }

    .timeline-row::after {
        content: "";
        position: absolute;
        left: 5px;
        top: 24px;
        bottom: -18px;
        width: 2px;
        background: #e7eef7;
    }

    .timeline-row:last-child::after {
        display: none;
    }

    .empty-state {
        border: 1px dashed #cdd8e6;
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        color: var(--dossier-muted);
        background: #fbfdff;
    }

    @media (max-width: 1199.98px) {
        .dossier-grid, .dossier-hero {
            grid-template-columns: 1fr;
        }

        .dossier-hero-media {
            min-height: 220px;
        }
    }

    @media (max-width: 767.98px) {
        .dossier-page-head, .passenger-head, .related-head, .document-head, .timeline-head {
            flex-direction: column;
            align-items: stretch;
        }

        .dossier-actions {
            width: 100%;
            justify-content: stretch;
        }

        .dossier-actions > * {
            flex: 1 1 auto;
            justify-content: center;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .dossier-card-body, .dossier-card-header, .dossier-hero-copy {
            padding: 18px;
        }
    }

    @media print {
        .dossier-page-head .dossier-actions,
        .dossier-form-grid,
        .dropdown,
        .nav-pills.dossier-tabs,
        .btn,
        .dossier-btn {
            display: none !important;
        }

        .dossier-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
    <div class="container-fluid dossier-shell">
        <div class="dossier-page-head">
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                    <span class="dossier-pill {{ $dossierBadge['class'] }}">{{ $dossierBadge['label'] }}</span>
                    <span class="dossier-pill {{ $paymentBadge['class'] }}">{{ $paymentBadge['label'] }}</span>
                </div>
                <h1>Dossier {{ $dossier->dossier_number ?? ('#'.$dossier->id) }}</h1>
                <p>Vue détaillée du dossier client, du voyage, des paiements, documents et actions opérationnelles.</p>
            </div>

            <div class="dossier-actions">
                <a href="{{ $backUrl ?? route('admin.reservation-dossiers.index') }}" class="dossier-btn dossier-btn-soft">
                    <i class="bx bx-arrow-back"></i>
                    <span>Retour</span>
                </a>
                <button type="button" class="dossier-btn dossier-btn-soft" onclick="window.print()">
                    <i class="bx bx-printer"></i>
                    <span>Imprimer</span>
                </button>
                <div class="dropdown">
                    <button class="dossier-btn dossier-btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bx bx-dots-horizontal-rounded"></i>
                        <span>Actions</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item" href="#payment-form"><i class="bx bx-wallet me-2"></i>Ajouter paiement</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.reservations.invoice', $reservation) }}" target="_blank"><i class="bx bx-receipt me-2"></i>Voir facture</a></li>
                        @if($mailToUrl)
                            <li><a class="dropdown-item" href="{{ $mailToUrl }}"><i class="bx bx-envelope me-2"></i>Contacter le client</a></li>
                        @endif
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('admin.reservations.cancel', $reservation) }}" method="POST" onsubmit="return confirm('Annuler ce dossier ?');">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bx bx-x-circle me-2"></i>Annuler dossier</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success shadow-sm border-0">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger shadow-sm border-0">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger shadow-sm border-0 mb-0">
                <strong>Des champs sont à corriger.</strong>
                <ul class="mb-0 mt-2 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="dossier-card dossier-hero">
            <div class="dossier-hero-media">
                @if($offerImageUrl)
                    <img src="{{ $offerImageUrl }}" alt="{{ $offer?->name ?? 'Offre liée' }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='grid';">
                    <div class="dossier-hero-placeholder" style="display:none;">
                        <div>
                            <i class="bx bx-image-alt fs-1 d-block mb-2"></i>
                            <div>Visuel indisponible</div>
                        </div>
                    </div>
                @else
                    <div class="dossier-hero-placeholder">
                        <div>
                            <i class="bx bx-image-alt fs-1 d-block mb-2"></i>
                            <div>Visuel indisponible</div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="dossier-hero-copy">
                <div>
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="dossier-pill {{ $dossierBadge['class'] }}">{{ $dossierBadge['label'] }}</span>
                        <span class="dossier-pill {{ $paymentBadge['class'] }}">{{ $paymentBadge['label'] }}</span>
                    </div>
                    <h2 class="dossier-hero-title">{{ $offer?->name ?? 'Offre non renseignée' }}</h2>
                    <p class="text-muted mb-0">{{ $offer?->destination ?? 'Destination non renseignée' }}</p>
                </div>

                <div class="dossier-hero-meta">
                    <div class="dossier-meta-card">
                        <span>Départ</span>
                        <strong>{{ $departure?->start_date?->format('d/m/Y') ?? '—' }}</strong>
                    </div>
                    <div class="dossier-meta-card">
                        <span>Durée</span>
                        <strong>{{ $durationLabel ?: '—' }}</strong>
                    </div>
                    <div class="dossier-meta-card">
                        <span>Client</span>
                        <strong>{{ $clientName }}</strong>
                    </div>
                    <div class="dossier-meta-card">
                        <span>Dossier</span>
                        <strong>{{ $dossier->dossier_number ?? '—' }}</strong>
                    </div>
                </div>
            </div>
        </section>

        <div class="dossier-grid">
            <div class="dossier-stack">
                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <h3 class="dossier-card-title">Informations client</h3>
                        <p class="dossier-card-subtitle">Coordonnées et fiche du client principal rattaché au dossier.</p>
                    </div>
                    <div class="dossier-card-body">
                        <div class="info-grid mb-3">
                            <div class="info-item">
                                <span>Nom complet</span>
                                <strong>{{ $clientName }}</strong>
                            </div>
                            <div class="info-item">
                                <span>Email</span>
                                <div>{{ $clientEmail ?: '—' }}</div>
                            </div>
                            <div class="info-item">
                                <span>Téléphone</span>
                                <div>{{ $clientPhone ?: '—' }}</div>
                            </div>
                            <div class="info-item">
                                <span>Adresse</span>
                                <div>{{ $clientAddress ?: '—' }}</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            @if($clientProfileUrl)
                                <a href="{{ $clientProfileUrl }}" class="dossier-btn dossier-btn-soft">
                                    <i class="bx bx-user-circle"></i>
                                    <span>Voir le profil client</span>
                                </a>
                            @endif
                            @if($mailToUrl)
                                <a href="{{ $mailToUrl }}" class="dossier-btn dossier-btn-soft">
                                    <i class="bx bx-envelope"></i>
                                    <span>Envoyer un email</span>
                                </a>
                            @endif
                            @if($whatsAppUrl)
                                <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="dossier-btn dossier-btn-soft">
                                    <i class="bx bxl-whatsapp"></i>
                                    <span>WhatsApp</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <h3 class="dossier-card-title">Offre & départ</h3>
                        <p class="dossier-card-subtitle">Synthèse voyage, commercial et point de vente.</p>
                    </div>
                    <div class="dossier-card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <span>Destination</span>
                                <strong>{{ $offer?->destination ?? '—' }}</strong>
                            </div>
                            <div class="info-item">
                                <span>Date de départ</span>
                                <div>{{ $departure?->start_date?->format('d/m/Y') ?? '—' }}</div>
                            </div>
                            <div class="info-item">
                                <span>Durée</span>
                                <div>{{ $durationLabel ?: '—' }}</div>
                            </div>
                            <div class="info-item">
                                <span>Agence</span>
                                <div>{{ $reservation->partner?->name ?? 'Ajinsafro' }}</div>
                            </div>
                            <div class="info-item">
                                <span>Point de vente</span>
                                <div>{{ $reservation->branch?->name ?? '—' }}</div>
                            </div>
                            <div class="info-item">
                                <span>Agent / commercial</span>
                                <div>{{ $reservation->assignedTo?->name ?? $reservation->agent?->name ?? $reservation->creator?->name ?? '—' }}</div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <h3 class="dossier-card-title">Passagers</h3>
                                <p class="dossier-card-subtitle">Voyageurs rattachés à cette réservation.</p>
                            </div>
                            <span class="passenger-badge">{{ max(1, $passengers->count()) }} passager{{ max(1, $passengers->count()) > 1 ? 's' : '' }}</span>
                        </div>
                    </div>
                    <div class="dossier-card-body">
                        <div class="passenger-list">
                            @if($passengers->isNotEmpty())
                                @foreach($passengers as $passenger)
                                    <div class="passenger-row">
                                        <div class="passenger-head">
                                            <div>
                                                <div class="passenger-name">{{ trim(($passenger->first_name ?? '').' '.($passenger->last_name ?? '')) ?: 'Passager' }}</div>
                                                <div class="passenger-meta">
                                                    {{ ucfirst((string) ($passenger->type ?: $passenger->traveler_type ?: 'adulte')) }}
                                                    @if($passenger->phone)
                                                        • {{ $passenger->phone }}
                                                    @endif
                                                </div>
                                            </div>
                                            <span class="dossier-pill is-completed">{{ $passenger->document_type ?: 'Document' }}</span>
                                        </div>
                                        <div class="passenger-meta mt-2">
                                            {{ $passenger->document_number ?: 'Document non renseigné' }}
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="passenger-row">
                                    <div class="passenger-head">
                                        <div>
                                            <div class="passenger-name">{{ $clientName }}</div>
                                            <div class="passenger-meta">Passager principal</div>
                                        </div>
                                        <span class="dossier-pill is-completed">Principal</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <h3 class="dossier-card-title">Réservations liées</h3>
                                <p class="dossier-card-subtitle">Autres réservations du même dossier ou du même client.</p>
                            </div>
                            <a href="{{ $allClientReservationsUrl }}" class="dossier-btn dossier-btn-soft">
                                <i class="bx bx-link-external"></i>
                                <span>Voir toutes les réservations liées</span>
                            </a>
                        </div>
                    </div>
                    <div class="dossier-card-body">
                        @if($relatedReservations->isNotEmpty())
                            <div class="related-list">
                                @foreach($relatedReservations as $related)
                                    @php
                                        $relatedStatus = $statusMap[$related->status] ?? ['label' => ucfirst((string) $related->status), 'class' => 'is-draft'];
                                    @endphp
                                    <div class="related-row">
                                        <div class="related-head">
                                            <div>
                                                <div class="related-title">#{{ $related->id }} • {{ $related->dossier_number ?? 'Sans numéro' }}</div>
                                                <div class="related-meta">
                                                    {{ $related->client?->full_name ?: trim(($related->client_first_name ?? '').' '.($related->client_last_name ?? '')) ?: '—' }}
                                                    • {{ $related->departure?->start_date?->format('d/m/Y') ?? optional($related->created_at)->format('d/m/Y') ?? '—' }}
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="dossier-pill {{ $relatedStatus['class'] }}">{{ $relatedStatus['label'] }}</span>
                                                <div class="related-price mt-2">{{ number_format((float) ($related->total_amount ?? 0), 2, ',', ' ') }} DH</div>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <a href="{{ route('admin.reservations.show', $related) }}" class="dossier-btn dossier-btn-soft">
                                                <i class="bx bx-show"></i>
                                                <span>Voir</span>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">Aucune autre réservation liée n’a été trouvée pour ce client.</div>
                        @endif
                    </div>
                </section>

                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <h3 class="dossier-card-title">Documents / Notes</h3>
                        <p class="dossier-card-subtitle">Pièces du dossier et suivi interne de l’équipe.</p>
                    </div>
                    <div class="dossier-card-body">
                        <ul class="nav nav-pills dossier-tabs" id="dossier-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="documents-tab" data-bs-toggle="pill" data-bs-target="#documents-panel" type="button" role="tab">Documents</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="notes-tab" data-bs-toggle="pill" data-bs-target="#notes-panel" type="button" role="tab">Notes</button>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="documents-panel" role="tabpanel" aria-labelledby="documents-tab">
                                @if($documents->isNotEmpty())
                                    <div class="document-list mb-3">
                                        @foreach($documents as $document)
                                            <div class="document-row">
                                                <div class="document-head">
                                                    <div>
                                                        <div class="document-title">{{ $document->title }}</div>
                                                        <div class="document-meta">
                                                            {{ ucfirst(str_replace('_', ' ', (string) $document->type)) }}
                                                            • {{ optional($document->created_at)->format('d/m/Y H:i') ?? '—' }}
                                                            • {{ $document->creator?->name ?? 'Système' }}
                                                        </div>
                                                    </div>
                                                    @if($documentUrl($document))
                                                        <a href="{{ $documentUrl($document) }}" target="_blank" rel="noopener" class="dossier-btn dossier-btn-soft">
                                                            <i class="bx bx-download"></i>
                                                            <span>Ouvrir</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-state mb-3">Aucun document chargé pour ce dossier.</div>
                                @endif

                                <form action="{{ route('admin.reservations.documents.store', $reservation) }}" method="POST" enctype="multipart/form-data" class="dossier-form-grid">
                                    @csrf
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Type</label>
                                            <select name="type" class="form-select" required>
                                                <option value="invoice">Facture</option>
                                                <option value="payment_receipt">Reçu paiement</option>
                                                <option value="passport">Passeport</option>
                                                <option value="booking_voucher">Bon de réservation</option>
                                                <option value="other">Autre fichier</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Titre</label>
                                            <input type="text" name="title" class="form-control" placeholder="Ex. Facture acompte" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Fichier</label>
                                            <input type="file" name="file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                                        </div>
                                    </div>
                                    <div>
                                        <button type="submit" class="dossier-btn dossier-btn-primary">
                                            <i class="bx bx-upload"></i>
                                            <span>Ajouter un document</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <div class="tab-pane fade" id="notes-panel" role="tabpanel" aria-labelledby="notes-tab">
                                @if($noteEntries->isNotEmpty())
                                    <div class="notes-list mb-3">
                                        @foreach($noteEntries as $noteEntry)
                                            <div class="note-row">
                                                <div class="note-meta mb-2">
                                                    {{ optional($noteEntry->created_at)->format('d/m/Y H:i') ?? '—' }}
                                                    • {{ $noteEntry->user?->name ?? 'Système' }}
                                                </div>
                                                <div class="text-dark" style="white-space: pre-line;">{{ $noteEntry->note }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($notesContent !== '')
                                    <div class="note-row mb-3">
                                        <div class="note-meta mb-2">Notes internes existantes</div>
                                        <div class="text-dark" style="white-space: pre-line;">{{ $notesContent }}</div>
                                    </div>
                                @else
                                    <div class="empty-state mb-3">Aucune note interne enregistrée.</div>
                                @endif

                                @if(auth()->user()->can('reservations.view_internal_notes') || auth()->user()->can('reservations.update'))
                                    <form action="{{ route('admin.reservations.notes.store', $reservation) }}" method="POST" class="dossier-form-grid">
                                        @csrf
                                        <div>
                                            <label class="form-label">Nouvelle note interne</label>
                                            <textarea name="note" class="form-control" rows="5" placeholder="Suivi commercial, précision client, prochaine action..." required></textarea>
                                        </div>
                                        <div>
                                            <button type="submit" class="dossier-btn dossier-btn-primary">
                                                <i class="bx bx-message-square-add"></i>
                                                <span>Ajouter la note</span>
                                            </button>
                                        </div>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <h3 class="dossier-card-title">Historique</h3>
                        <p class="dossier-card-subtitle">Timeline des événements clés du dossier.</p>
                    </div>
                    <div class="dossier-card-body">
                        @if($histories->isNotEmpty())
                            <div class="timeline-list">
                                @foreach($histories as $history)
                                    @php($timeline = $historyMeta($history))
                                    <div class="timeline-row">
                                        <div class="timeline-head">
                                            <div>
                                                <div class="document-title">{{ $timeline['label'] }}</div>
                                                @if($timeline['details'] !== '')
                                                    <div class="timeline-meta mt-1">{{ $timeline['details'] }}</div>
                                                @endif
                                            </div>
                                            <div class="timeline-meta text-end">
                                                <div>{{ optional($history->created_at)->format('d/m/Y H:i') ?? '—' }}</div>
                                                <div>{{ $history->user?->name ?? 'Système' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="empty-state">Aucun historique disponible pour ce dossier.</div>
                        @endif
                    </div>
                </section>
            </div>

            <div class="dossier-stack">
                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <h3 class="dossier-card-title">Résumé financier</h3>
                        <p class="dossier-card-subtitle">Total, encaissement et progression du paiement.</p>
                    </div>
                    <div class="dossier-card-body summary-grid">
                        <div class="summary-highlight">
                            <div class="summary-row mb-3">
                                <span>Statut paiement</span>
                                <strong class="dossier-pill {{ $paymentBadge['class'] }}">{{ $paymentBadge['label'] }}</strong>
                            </div>
                            <div class="summary-row">
                                <span>Progression</span>
                                <strong>{{ $paymentProgress }}%</strong>
                            </div>
                            <div class="progress-shell mt-3">
                                <div class="progress-bar" style="width: {{ $paymentProgress }}%;"></div>
                            </div>
                        </div>

                        <div class="summary-row">
                            <span>Total du dossier</span>
                            <strong>{{ number_format($totalAmount, 2, ',', ' ') }} DH</strong>
                        </div>
                        <div class="summary-row">
                            <span>Payé</span>
                            <strong>{{ number_format($paidAmount, 2, ',', ' ') }} DH</strong>
                        </div>
                        <div class="summary-row">
                            <span>Restant à payer</span>
                            <strong>{{ number_format($remainingAmount, 2, ',', ' ') }} DH</strong>
                        </div>
                        <div class="summary-row">
                            <span>Suppléments</span>
                            <strong>{{ number_format((float) ($dossier->room_supplement_total ?? $reservation->room_supplement_total ?? 0), 2, ',', ' ') }} DH</strong>
                        </div>
                        <div class="summary-row">
                            <span>Extras</span>
                            <strong>{{ number_format((float) ($dossier->extras_total ?? $reservation->extras_total ?? 0), 2, ',', ' ') }} DH</strong>
                        </div>
                    </div>
                </section>

                <section class="dossier-card">
                    <div class="dossier-card-header">
                        <h3 class="dossier-card-title">Actions rapides</h3>
                        <p class="dossier-card-subtitle">Raccourcis utiles pour gérer le dossier au quotidien.</p>
                    </div>
                    <div class="dossier-card-body">
                        <div class="actions-list">
                            <div class="action-row">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="document-title">Voir facture</div>
                                        <div class="document-meta">Ouvre la version imprimable / PDF du dossier.</div>
                                    </div>
                                    <a href="{{ route('admin.reservations.invoice', $reservation) }}" target="_blank" class="dossier-btn dossier-btn-soft">
                                        <i class="bx bx-receipt"></i>
                                        <span>Ouvrir</span>
                                    </a>
                                </div>
                            </div>
                            <div class="action-row">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="document-title">Ajouter paiement</div>
                                        <div class="document-meta">Enregistrement immédiat avec mise à jour des soldes.</div>
                                    </div>
                                    <a href="#payment-form" class="dossier-btn dossier-btn-soft">
                                        <i class="bx bx-wallet"></i>
                                        <span>Ajouter</span>
                                    </a>
                                </div>
                            </div>
                            <div class="action-row">
                                <div class="d-flex justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="document-title">Contacter le client</div>
                                        <div class="document-meta">Email direct et WhatsApp si disponible.</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($mailToUrl)
                                            <a href="{{ $mailToUrl }}" class="dossier-btn dossier-btn-soft"><i class="bx bx-envelope"></i><span>Email</span></a>
                                        @endif
                                        @if($whatsAppUrl)
                                            <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="dossier-btn dossier-btn-soft"><i class="bx bxl-whatsapp"></i><span>WhatsApp</span></a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dossier-card" id="payment-form">
                    <div class="dossier-card-header">
                        <h3 class="dossier-card-title">Ajouter un paiement</h3>
                        <p class="dossier-card-subtitle">Met à jour le payé, le restant et l’historique du dossier.</p>
                    </div>
                    <div class="dossier-card-body">
                        <form action="{{ route('admin.reservations.payments.store', $reservation) }}" method="POST" enctype="multipart/form-data" class="dossier-form-grid">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Montant</label>
                                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date paiement</label>
                                    <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mode de paiement</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="ESPECE">Espèce</option>
                                        <option value="VIREMENT">Virement</option>
                                        <option value="CASHPLUS">Cash Plus</option>
                                        <option value="CARTE">Carte</option>
                                        <option value="AUTRE">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Référence</label>
                                    <input type="text" name="reference" class="form-control" placeholder="Référence paiement">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Justificatif</label>
                                    <input type="file" name="proof_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Note</label>
                                    <textarea name="note" class="form-control" rows="3" placeholder="Commentaire interne lié au paiement"></textarea>
                                </div>
                            </div>
                            <div>
                                <button type="submit" class="dossier-btn dossier-btn-primary">
                                    <i class="bx bx-save"></i>
                                    <span>Enregistrer le paiement</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
