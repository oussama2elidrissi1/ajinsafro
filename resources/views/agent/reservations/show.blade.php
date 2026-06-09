@extends('layouts.master-ajinsafro')

@section('title', 'Detail reservation')

@push('styles')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .aj-agent-res-show {
            padding: 0 18px 28px;
        }

        .aj-agent-res-head {
            max-width: 1320px;
            margin: 0 auto 20px;
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
        }

        .aj-agent-res-head h1 {
            margin: 0;
            color: #0f172a;
            font-size: 31px;
            font-weight: 800;
            line-height: 1.05;
        }

        .aj-agent-res-head p {
            margin: 8px 0 0;
            color: #64748b;
            font-size: 14px;
            font-weight: 600;
        }

        .aj-agent-res-shell {
            max-width: 1320px;
            margin: 0 auto;
        }

        .aj-agent-res-hero {
            position: relative;
            overflow: hidden;
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(320px, .9fr);
            gap: 18px;
            padding: 24px 26px;
            border-radius: 22px;
            border: 1px solid #d7e4ef;
            background:
                radial-gradient(circle at top right, rgba(255, 122, 26, .12), transparent 28%),
                linear-gradient(135deg, #083b5b 0%, #0b537f 52%, #0f7db4 100%);
            box-shadow: 0 18px 40px rgba(14, 58, 90, .16);
            margin-bottom: 22px;
        }

        .aj-agent-res-hero::after {
            content: "";
            position: absolute;
            right: -44px;
            bottom: -58px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .08);
        }

        .aj-agent-res-hero-main,
        .aj-agent-res-hero-side {
            position: relative;
            z-index: 1;
            min-width: 0;
        }

        .aj-agent-res-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            color: #d9efff;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .08em;
        }

        .aj-agent-res-code {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
        }

        .aj-agent-res-title {
            margin: 0;
            color: #fff;
            font-size: 32px;
            font-weight: 800;
            line-height: 1.08;
            max-width: 16ch;
        }

        .aj-agent-res-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 14px;
        }

        .aj-agent-res-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
            color: #eef7ff;
            font-size: 12px;
            font-weight: 700;
        }

        .aj-agent-res-chip i {
            font-size: 14px;
        }

        .aj-agent-res-summary {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .aj-agent-res-stat {
            padding: 14px 15px;
            border-radius: 16px;
            background: rgba(255, 255, 255, .95);
            border: 1px solid rgba(255, 255, 255, .45);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .10);
        }

        .aj-agent-res-stat span {
            display: block;
            margin-bottom: 6px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .aj-agent-res-stat strong {
            display: block;
            color: #0f172a;
            font-size: 19px;
            font-weight: 800;
            line-height: 1.1;
            overflow-wrap: anywhere;
        }

        .aj-agent-res-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, .85fr);
            gap: 22px;
            align-items: start;
        }

        .aj-agent-res-card {
            background: linear-gradient(180deg, #ffffff 0%, #f9fbfe 100%);
            border: 1px solid #dbe7f1;
            border-radius: 20px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, .06);
            overflow: hidden;
        }

        .aj-agent-res-card-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 20px 14px;
            border-bottom: 1px solid #e9f0f6;
        }

        .aj-agent-res-card-head h2 {
            margin: 0;
            color: #0f172a;
            font-size: 16px;
            font-weight: 800;
        }

        .aj-agent-res-card-head p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 13px;
        }

        .aj-agent-res-card-body {
            padding: 18px 20px 20px;
        }

        .aj-agent-res-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .aj-agent-res-field {
            min-width: 0;
            padding: 14px 15px;
            border-radius: 16px;
            border: 1px solid #e3edf5;
            background: #fff;
        }

        .aj-agent-res-field--full {
            grid-column: 1 / -1;
        }

        .aj-agent-res-field label {
            display: block;
            margin-bottom: 7px;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .aj-agent-res-field div {
            color: #0f172a;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        .aj-agent-res-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .aj-agent-res-badge.is-pending {
            background: #fff4e8;
            color: #c25b06;
            border-color: #fed7aa;
        }

        .aj-agent-res-badge.is-success {
            background: #ecfdf5;
            color: #15803d;
            border-color: #bbf7d0;
        }

        .aj-agent-res-badge.is-danger {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .aj-agent-res-badge.is-neutral {
            background: #eef4fa;
            color: #30506e;
            border-color: #d7e4ef;
        }

        .aj-agent-res-pax-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .aj-agent-res-pax {
            display: grid;
            grid-template-columns: 46px minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 12px 14px;
            border-radius: 16px;
            border: 1px solid #e3edf5;
            background: #fff;
        }

        .aj-agent-res-pax-avatar {
            width: 46px;
            height: 46px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #e7f4fb 0%, #f6fbff 100%);
            color: #0b537f;
            font-size: 14px;
            font-weight: 900;
        }

        .aj-agent-res-pax-name {
            color: #0f172a;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.3;
        }

        .aj-agent-res-pax-meta {
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .aj-agent-res-pax-type {
            display: inline-flex;
            align-items: center;
            padding: 7px 10px;
            border-radius: 999px;
            background: #eff6ff;
            color: #0b537f;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .aj-agent-res-empty {
            padding: 24px 18px;
            border: 1px dashed #d7e4ef;
            border-radius: 16px;
            background: #fff;
            color: #64748b;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
        }

        .aj-agent-res-note {
            padding: 14px 15px;
            border-radius: 16px;
            background: #fffdf7;
            border: 1px solid #f8e7b0;
            color: #6a4f08;
            font-size: 13px;
            line-height: 1.55;
            white-space: pre-line;
        }

        @media (max-width: 1100px) {
            .aj-agent-res-hero,
            .aj-agent-res-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .aj-agent-res-show {
                padding: 0 12px 24px;
            }

            .aj-agent-res-head {
                flex-direction: column;
                align-items: stretch;
            }

            .aj-agent-res-title {
                font-size: 26px;
                max-width: none;
            }

            .aj-agent-res-summary,
            .aj-agent-res-info-grid {
                grid-template-columns: 1fr;
            }

            .aj-agent-res-pax {
                grid-template-columns: 42px minmax(0, 1fr);
            }

            .aj-agent-res-pax-type {
                grid-column: 1 / -1;
                justify-self: flex-start;
            }
        }
    </style>
@endpush

@section('content')
@php
    use App\Models\Reservation;

    $reservationCode = $reservation->dossier_number ?: ('RES-' . str_pad((string) $reservation->id, 6, '0', STR_PAD_LEFT));
    $clientName = trim(($reservation->client_first_name ?? '') . ' ' . ($reservation->client_last_name ?? ''));
    $clientName = $clientName !== '' ? $clientName : ($reservation->client?->full_name ?: 'Client non renseigne');
    $tourName = $reservation->tour?->name ?: 'Voyage non renseigne';
    $travelDate = $reservation->travelDate?->date ?? $reservation->departure?->start_date ?? $reservation->created_at;
    $returnDate = $reservation->departure?->end_date;
    $travelerCount = max(1, (int) ($reservation->passengers_count ?? $reservation->passengers->count() ?: 1));
    $totalAmount = (float) ($reservation->effective_total_amount ?? $reservation->total_amount ?? 0);
    $paidAmount = (float) ($reservation->effective_paid_amount ?? $reservation->paid_amount ?? 0);
    $remainingAmount = (float) ($reservation->effective_remaining_amount ?? max(0, $totalAmount - $paidAmount));
    $paymentStatus = method_exists($reservation, 'paymentStatusLabelFr') ? $reservation->paymentStatusLabelFr() : ($reservation->payment_status ?: 'Non paye');
    $statusLabel = method_exists($reservation, 'statusLabelFr') ? $reservation->statusLabelFr() : ($reservation->status ?: '-');
    $createdBy = $reservation->resolveOperationalActorUser();
    $agencyLabel = $reservation->agency_label ?: 'Agence non renseignee';
    $statusClass = match ((string) $reservation->status) {
        Reservation::STATUS_CONFIRMED, Reservation::STATUS_PAID => 'is-success',
        Reservation::STATUS_CANCELLED, Reservation::STATUS_REFUNDED, Reservation::STATUS_EXPIRED => 'is-danger',
        Reservation::STATUS_PENDING, Reservation::STATUS_OPTION, Reservation::STATUS_SHARED_ROOM_PENDING, Reservation::STATUS_PARTIALLY_PAID => 'is-pending',
        default => 'is-neutral',
    };
    $paymentClass = match ((string) $reservation->payment_status) {
        Reservation::PAYMENT_STATUS_PAID => 'is-success',
        Reservation::PAYMENT_STATUS_PARTIAL, Reservation::PAYMENT_STATUS_DEPOSIT => 'is-pending',
        default => 'is-neutral',
    };
@endphp

<div class="aj-agent-res-show">
    <div class="aj-agent-res-head">
        <div>
            <h1>Detail reservation</h1>
            <p>Consultation complete du dossier, du client, des voyageurs et du suivi paiement.</p>
        </div>
        <a href="{{ route('agent.reservations.index') }}" class="aj-agent-action-btn">
            <i class="bx bx-left-arrow-alt"></i>
            <span>Retour</span>
        </a>
    </div>

    <div class="aj-agent-res-shell">
        <section class="aj-agent-res-hero">
            <div class="aj-agent-res-hero-main">
                <div class="aj-agent-res-kicker">Reservation agent</div>
                <div class="aj-agent-res-code">
                    <i class="bx bx-hash"></i>
                    <span>{{ $reservationCode }}</span>
                </div>
                <h2 class="aj-agent-res-title">{{ $tourName }}</h2>
                <div class="aj-agent-res-meta">
                    <span class="aj-agent-res-chip"><i class="bx bx-user"></i>{{ $clientName }}</span>
                    <span class="aj-agent-res-chip"><i class="bx bx-calendar"></i>{{ $travelDate ? $travelDate->format('d/m/Y') : '-' }}</span>
                    <span class="aj-agent-res-chip"><i class="bx bx-group"></i>{{ $travelerCount }} voyageur(s)</span>
                </div>
            </div>

            <div class="aj-agent-res-hero-side">
                <div class="aj-agent-res-summary">
                    <div class="aj-agent-res-stat">
                        <span>Statut dossier</span>
                        <strong>{{ $statusLabel }}</strong>
                    </div>
                    <div class="aj-agent-res-stat">
                        <span>Paiement</span>
                        <strong>{{ $paymentStatus }}</strong>
                    </div>
                    <div class="aj-agent-res-stat">
                        <span>Total</span>
                        <strong>{{ number_format($totalAmount, 0, ',', ' ') }} DH</strong>
                    </div>
                    <div class="aj-agent-res-stat">
                        <span>Reste</span>
                        <strong>{{ number_format($remainingAmount, 0, ',', ' ') }} DH</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="aj-agent-res-grid">
            <div class="aj-agent-res-card">
                <div class="aj-agent-res-card-head">
                    <div>
                        <h2>Informations reservation</h2>
                        <p>Client principal, voyage, dates et contexte de creation.</p>
                    </div>
                    <span class="aj-agent-res-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </div>
                <div class="aj-agent-res-card-body">
                    <div class="aj-agent-res-info-grid">
                        <div class="aj-agent-res-field">
                            <label>Client</label>
                            <div>{{ $clientName }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Agence</label>
                            <div>{{ $agencyLabel }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Telephone</label>
                            <div>{{ $reservation->client?->phone ?: $reservation->client_phone ?: '-' }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Email</label>
                            <div>{{ $reservation->client?->email ?: $reservation->client_email ?: '-' }}</div>
                        </div>
                        <div class="aj-agent-res-field aj-agent-res-field--full">
                            <label>Voyage</label>
                            <div>{{ $tourName }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Date depart</label>
                            <div>{{ $travelDate ? $travelDate->format('d/m/Y') : '-' }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Date retour</label>
                            <div>{{ $returnDate ? $returnDate->format('d/m/Y') : '-' }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Voyageurs declares</label>
                            <div>{{ $travelerCount }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Operateur</label>
                            <div>{{ $createdBy?->name ?: 'Non renseigne' }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Mode paiement</label>
                            <div>{{ $reservation->payment_type ?: '-' }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Creation</label>
                            <div>{{ $reservation->created_at?->format('d/m/Y H:i') ?: '-' }}</div>
                        </div>
                        @if(filled($reservation->notes))
                            <div class="aj-agent-res-field aj-agent-res-field--full">
                                <label>Notes</label>
                                <div class="aj-agent-res-note">{{ $reservation->notes }}</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="aj-agent-res-card">
                <div class="aj-agent-res-card-head">
                    <div>
                        <h2>Suivi financier</h2>
                        <p>Lecture rapide du total, du paye et du solde restant.</p>
                    </div>
                    <span class="aj-agent-res-badge {{ $paymentClass }}">{{ $paymentStatus }}</span>
                </div>
                <div class="aj-agent-res-card-body">
                    <div class="aj-agent-res-info-grid">
                        <div class="aj-agent-res-field">
                            <label>Total dossier</label>
                            <div>{{ number_format($totalAmount, 0, ',', ' ') }} DH</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Montant paye</label>
                            <div>{{ number_format($paidAmount, 0, ',', ' ') }} DH</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Reste a payer</label>
                            <div>{{ number_format($remainingAmount, 0, ',', ' ') }} DH</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Nombre de paiements</label>
                            <div>{{ $reservation->payments->count() }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="aj-agent-res-card">
                <div class="aj-agent-res-card-head">
                    <div>
                        <h2>Voyageurs</h2>
                        <p>Liste des participants attaches a cette reservation.</p>
                    </div>
                </div>
                <div class="aj-agent-res-card-body">
                    @if($reservation->passengers->isEmpty())
                        <div class="aj-agent-res-empty">Aucun voyageur detaille n'a encore ete ajoute au dossier.</div>
                    @else
                        <div class="aj-agent-res-pax-list">
                            @foreach($reservation->passengers as $passenger)
                                @php
                                    $passengerName = trim(($passenger->first_name ?? '') . ' ' . ($passenger->last_name ?? '')) ?: 'Voyageur';
                                    $passengerInitials = collect(explode(' ', $passengerName))
                                        ->filter()
                                        ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
                                        ->take(2)
                                        ->implode('');
                                    $passengerMeta = collect([
                                        $passenger->gender === 'male' ? 'Homme' : ($passenger->gender === 'female' ? 'Femme' : null),
                                        $passenger->birth_date ? \Illuminate\Support\Carbon::parse($passenger->birth_date)->format('d/m/Y') : null,
                                    ])->filter()->implode(' · ');
                                @endphp
                                <div class="aj-agent-res-pax">
                                    <div class="aj-agent-res-pax-avatar">{{ $passengerInitials }}</div>
                                    <div>
                                        <div class="aj-agent-res-pax-name">{{ $passengerName }}</div>
                                        <div class="aj-agent-res-pax-meta">{{ $passengerMeta !== '' ? $passengerMeta : 'Informations partielles' }}</div>
                                    </div>
                                    <span class="aj-agent-res-pax-type">{{ $passenger->type ?: 'adulte' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="aj-agent-res-card">
                <div class="aj-agent-res-card-head">
                    <div>
                        <h2>Repères dossier</h2>
                        <p>Elements de suivi utiles pour l'agent.</p>
                    </div>
                </div>
                <div class="aj-agent-res-card-body">
                    <div class="aj-agent-res-info-grid">
                        <div class="aj-agent-res-field">
                            <label>Reference interne</label>
                            <div>#{{ $reservation->id }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Dossier</label>
                            <div>{{ $reservation->dossier?->dossier_number ?: ($reservation->dossier_number ?: '-') }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Statut paiement</label>
                            <div>{{ $paymentStatus }}</div>
                        </div>
                        <div class="aj-agent-res-field">
                            <label>Client lie</label>
                            <div>{{ $reservation->client?->client_code ? ($reservation->client->client_code . ' · ' . $reservation->client->full_name) : 'Aucun client lie' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
