<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Reservation;
use App\Models\ReservationDossier;
use App\Models\User;
use App\Models\Voyage;
use App\Services\ReservationListQueryService;
use App\Services\ReservationVisibilityService;
use App\Services\Wp\WpHeroImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ReservationDossierController extends Controller
{
    public function __construct(
        protected ReservationVisibilityService $reservationVisibility,
        protected ReservationListQueryService $reservationListQuery,
    ) {}

    public function index(Request $request): View
    {
        $quickStatus = trim((string) $request->query('status', 'all')) ?: 'all';
        $reservationStatus = trim((string) $request->query('reservation_status', ''));
        $paymentStatus = trim((string) $request->query('payment_status', ''));
        $channel = trim((string) $request->query('channel', ''));
        $search = trim((string) $request->query('search', ''));
        $period = trim((string) $request->query('period', '7d')) ?: '7d';
        $voyageId = (int) $request->query('voyage_id', 0);
        $agentId = (int) $request->query('agent_id', 0);
        $branchId = (int) $request->query('branch_id', 0);
        $clientId = (int) $request->query('client_id', 0);
        $departureDate = trim((string) $request->query('departure_date', ''));
        $travelDateId = (int) $request->query('travel_date_id', 0);

        $reservationQuery = $this->reservationListQuery->baseQuery($request->user(), [
            'channel' => $channel,
            'tour_id' => $voyageId,
            'travel_date_id' => $travelDateId,
        ])->with([
            'client',
            'offer.images',
            'voyage.images',
            'tour.images',
            'departure',
            'assignedTo',
            'agent',
            'creator',
            'branch',
        ]);

        if ($search !== '') {
            $reservationQuery->where(function ($query) use ($search) {
                $like = '%'.$search.'%';
                $query->where('dossier_number', 'like', $like)
                    ->orWhere('client_first_name', 'like', $like)
                    ->orWhere('client_last_name', 'like', $like)
                    ->orWhere('client_phone', 'like', $like)
                    ->orWhere('client_email', 'like', $like)
                    ->orWhereHas('client', function ($clientQuery) use ($like) {
                        $clientQuery->where('full_name', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    })
                    ->orWhereHas('offer', fn ($offerQuery) => $offerQuery->where('name', 'like', $like)->orWhere('destination', 'like', $like))
                    ->orWhereHas('voyage', fn ($offerQuery) => $offerQuery->where('name', 'like', $like)->orWhere('destination', 'like', $like))
                    ->orWhereHas('tour', fn ($offerQuery) => $offerQuery->where('name', 'like', $like)->orWhere('destination', 'like', $like));
            });
        }

        if ($voyageId > 0) {
            $this->reservationListQuery->applyTourFilter($reservationQuery, $voyageId);
        }

        if ($travelDateId > 0) {
            $this->reservationListQuery->applyTravelDateFilter($reservationQuery, $travelDateId);
        }

        if ($departureDate !== '') {
            $reservationQuery->whereHas('departure', fn ($departureQuery) => $departureQuery->whereDate('start_date', $departureDate));
        }

        if ($agentId > 0) {
            $reservationQuery->where(function ($query) use ($agentId) {
                $query->where('assigned_to', $agentId)
                    ->orWhere('agent_id', $agentId)
                    ->orWhere('created_by', $agentId)
                    ->orWhere('created_by_user_id', $agentId);
            });
        }

        if ($branchId > 0) {
            $reservationQuery->where('branch_id', $branchId);
        }

        if ($clientId > 0) {
            $reservationQuery->where('client_external_id', $clientId);
        }

        if ($channel !== '') {
            $this->reservationListQuery->applyChannelFilter($reservationQuery, $channel);

            if ($channel !== 'client' && Schema::connection('mysql')->hasColumn('reservations', 'channel')) {
                $reservationQuery->where('channel', $channel);
            }
        }

        if ($reservationStatus !== '') {
            $this->reservationListQuery->applyStatusFilter($reservationQuery, $reservationStatus);
        }

        if ($paymentStatus !== '') {
            $reservationQuery->where('payment_status', $paymentStatus);
        }

        if ($quickStatus !== 'all') {
            match ($quickStatus) {
                'pending' => $reservationQuery->whereIn('status', [
                    Reservation::STATUS_PENDING,
                    Reservation::STATUS_OPTION,
                    Reservation::STATUS_SHARED_ROOM_PENDING,
                ]),
                'paid' => $reservationQuery->where('payment_status', Reservation::PAYMENT_STATUS_PAID),
                'follow_up' => $reservationQuery->where('remaining_amount', '>', 0),
                default => null,
            };
        }

        match ($period) {
            'today' => $reservationQuery->whereDate('created_at', now()->toDateString()),
            '30d' => $reservationQuery->where('created_at', '>=', now()->subDays(30)),
            'all' => null,
            default => $reservationQuery->where('created_at', '>=', now()->subDays(7)),
        };

        $reservations = $reservationQuery
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn (Reservation $reservation) => $this->resolveReservationOffer($reservation) !== null)
            ->values();

        $stats = [
            'voyages' => 0,
            'reservations' => $reservations->count(),
            'pending' => 0,
            'follow_up' => 0,
            'paid' => 0,
            'remaining_amount' => 0.0,
        ];

        $voyageCards = $reservations
            ->groupBy(fn (Reservation $reservation) => $this->resolveReservationGroupKey($reservation))
            ->map(function (Collection $group) {
                $sorted = $group->sortByDesc(fn (Reservation $reservation) => optional($reservation->created_at)?->timestamp ?? 0)->values();
                $lead = $sorted->first();
                $offer = $this->resolveReservationOffer($lead);
                $imageUrl = $this->resolveOfferImageUrl($offer);
                $pendingCount = $sorted->filter(fn (Reservation $reservation) => in_array($reservation->status, [
                    Reservation::STATUS_PENDING,
                    Reservation::STATUS_OPTION,
                    Reservation::STATUS_SHARED_ROOM_PENDING,
                ], true))->count();
                $confirmedCount = $sorted->filter(fn (Reservation $reservation) => in_array($reservation->status, [
                    Reservation::STATUS_CONFIRMED,
                    Reservation::STATUS_PARTIALLY_PAID,
                    Reservation::STATUS_PAID,
                    Reservation::STATUS_SHARED_ROOM_PAIRED,
                ], true))->count();
                $paidCount = $sorted->filter(fn (Reservation $reservation) => $reservation->payment_status === Reservation::PAYMENT_STATUS_PAID)->count();
                $followUpCount = $sorted->filter(fn (Reservation $reservation) => (float) $reservation->effective_remaining_amount > 0.0 && $reservation->status !== Reservation::STATUS_CANCELLED)->count();
                $cancelledCount = $sorted->filter(fn (Reservation $reservation) => $reservation->status === Reservation::STATUS_CANCELLED)->count();
                $totalAmount = round($sorted->sum(fn (Reservation $reservation) => (float) $reservation->effective_total_amount), 2);
                $paidAmount = round($sorted->sum(fn (Reservation $reservation) => (float) $reservation->effective_paid_amount), 2);
                $remainingAmount = round($sorted->sum(fn (Reservation $reservation) => (float) $reservation->effective_remaining_amount), 2);
                $latestReservation = $sorted->first();

                $globalBadge = ['label' => 'Actif', 'class' => 'is-active'];
                if ($followUpCount > 0) {
                    $globalBadge = ['label' => 'À suivre', 'class' => 'is-follow-up'];
                } elseif ($latestReservation?->created_at && $latestReservation->created_at->greaterThanOrEqualTo(now()->subDays(7))) {
                    $globalBadge = ['label' => 'Réservations récentes', 'class' => 'is-recent'];
                } elseif ($sorted->count() > 0 && $remainingAmount <= 0.0) {
                    $globalBadge = ['label' => 'Complet', 'class' => 'is-complete'];
                }

                return (object) [
                    'key' => $this->resolveReservationGroupKey($lead),
                    'offer' => $offer,
                    'image_url' => $imageUrl,
                    'title' => $offer?->name ?? 'Voyage non renseigné',
                    'destination' => $offer?->destination ?? 'Destination non renseignée',
                    'reservations' => $sorted,
                    'reservations_count' => $sorted->count(),
                    'pending_count' => $pendingCount,
                    'confirmed_count' => $confirmedCount,
                    'paid_count' => $paidCount,
                    'follow_up_count' => $followUpCount,
                    'cancelled_count' => $cancelledCount,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'latest_reservation' => $latestReservation,
                    'global_badge' => $globalBadge,
                ];
            })
            ->sortByDesc(fn ($card) => optional($card->latest_reservation?->created_at)?->timestamp ?? 0)
            ->values();

        $stats['voyages'] = $voyageCards->count();
        $stats['pending'] = (int) $voyageCards->sum('pending_count');
        $stats['follow_up'] = (int) $voyageCards->sum('follow_up_count');
        $stats['paid'] = (int) $voyageCards->sum('paid_count');
        $stats['remaining_amount'] = round((float) $voyageCards->sum('remaining_amount'), 2);

        $perPage = max(1, min(24, (int) $request->query('per_page', 9)));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $voyagePaginator = new LengthAwarePaginator(
            $voyageCards->forPage($currentPage, $perPage)->values(),
            $voyageCards->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.reservation-dossiers.index', [
            'voyages' => $voyagePaginator,
            'stats' => $stats,
            'currentStatus' => $quickStatus,
            'filters' => [
                'search' => $search,
                'voyage_id' => $voyageId > 0 ? $voyageId : null,
                'travel_date_id' => $travelDateId > 0 ? $travelDateId : null,
                'departure_date' => $departureDate,
                'agent_id' => $agentId > 0 ? $agentId : null,
                'branch_id' => $branchId > 0 ? $branchId : null,
                'client_id' => $clientId > 0 ? $clientId : null,
                'reservation_status' => $reservationStatus,
                'payment_status' => $paymentStatus,
                'channel' => $channel,
                'period' => $period,
                'per_page' => $perPage,
            ],
            'voyageOptions' => Voyage::query()->orderBy('name')->limit(300)->get(['id', 'name']),
            'agentOptions' => User::query()->orderBy('name')->limit(200)->get(['id', 'name']),
            'branchOptions' => Branch::query()->orderBy('name')->limit(200)->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, ReservationDossier $reservationDossier): View|RedirectResponse
    {
        $reservationDossier->load([
            'client',
            'creator',
            'assignedTo',
            'payments.creator',
            'documents.creator',
            'histories.user',
            'reservations.client',
            'reservations.offer',
            'reservations.offer.images',
            'reservations.voyage',
            'reservations.voyage.images',
            'reservations.tour',
            'reservations.tour.images',
            'reservations.departure',
            'reservations.passengers',
            'reservations.extras',
            'reservations.reservationRooms.departureHotelRoom',
            'mainReservation.client',
            'mainReservation.offer',
            'mainReservation.offer.images',
            'mainReservation.voyage',
            'mainReservation.voyage.images',
            'mainReservation.tour',
            'mainReservation.tour.images',
            'mainReservation.departure',
            'mainReservation.passengers',
            'mainReservation.extras',
            'mainReservation.payments.creator',
            'mainReservation.documents.creator',
            'mainReservation.histories.user',
            'mainReservation.reservationRooms.departureHotelRoom',
        ]);

        $reservation = $reservationDossier->mainReservation ?: $reservationDossier->reservations->first();
        if (! $reservation) {
            return redirect()->route('admin.reservation-dossiers.index')
                ->with('error', 'Aucune réservation liée à ce dossier.');
        }

        abort_unless($this->reservationVisibility->canAccessReservation($request->user(), $reservation), 403);

        $backUrl = url()->previous();

        if (! $backUrl || str_contains($backUrl, '/admin/reservation-dossiers/'.$reservationDossier->id)) {
            $backUrl = route('admin.reservation-dossiers.index');
        }

        $offer = $reservation->voyage
            ?? $reservation->offer
            ?? $reservation->tour
            ?? null;

        $offerImageUrl = null;

        if ($offer) {
            if (! empty($offer->image_url)) {
                $offerImageUrl = $offer->image_url;
            } elseif (! empty($offer->featured_image_url)) {
                $offerImageUrl = $offer->featured_image_url;
            } elseif (! empty($offer->cover_image)) {
                $offerImageUrl = asset('storage/'.ltrim((string) $offer->cover_image, '/'));
            } elseif (! empty($offer->thumbnail)) {
                $offerImageUrl = asset('storage/'.ltrim((string) $offer->thumbnail, '/'));
            } elseif (! empty($offer->featured_image)) {
                $featuredImage = (string) $offer->featured_image;
                $offerImageUrl = str_starts_with($featuredImage, 'http://') || str_starts_with($featuredImage, 'https://')
                    ? $featuredImage
                    : Storage::disk('public')->url($featuredImage);
            } elseif (! empty($offer->wp_image_url)) {
                $offerImageUrl = $offer->wp_image_url;
            } elseif ((int) ($offer->wp_post_id ?? 0) > 0) {
                $heroImageId = \App\Models\Wp\WpPostMeta::query()
                    ->where('post_id', (int) $offer->wp_post_id)
                    ->whereIn('meta_key', ['_tour_hero_image_id', '_thumbnail_id'])
                    ->orderByRaw("FIELD(meta_key, '_tour_hero_image_id', '_thumbnail_id')")
                    ->value('meta_value');

                if ((int) $heroImageId > 0) {
                    $offerImageUrl = WpHeroImageService::publicUrlForAttachmentId((int) $heroImageId);
                }
            }
        }

        $clientId = $reservationDossier->client_id ?? $reservation->client_external_id ?? null;
        $relatedReservations = Reservation::query()
            ->with(['client', 'departure', 'voyage', 'offer'])
            ->where(function ($query) use ($reservationDossier, $clientId) {
                $query->where('reservation_dossier_id', $reservationDossier->id);

                if ($clientId) {
                    $query->orWhere('client_external_id', $clientId);
                }
            })
            ->where('id', '!=', $reservation->id)
            ->latest()
            ->limit(6)
            ->get()
            ->filter(fn (Reservation $candidate) => $this->reservationVisibility->canAccessReservation($request->user(), $candidate))
            ->values();

        $allClientReservationsUrl = $clientId
            ? route('admin.reservations.index', ['client_id' => $clientId])
            : route('admin.reservations.index');

        $noteEntries = $reservationDossier->histories
            ->filter(fn ($history) => $history->action === 'reservation.note_added')
            ->values();

        $notesContent = (string) (
            $reservationDossier->notes
            ?? $reservationDossier->internal_notes
            ?? $reservationDossier->admin_notes
            ?? $reservation->notes
            ?? ''
        );

        return view('admin.reservation-dossiers.show', [
            'dossier' => $reservationDossier,
            'reservation' => $reservation,
            'offer' => $offer,
            'offerImageUrl' => $offerImageUrl,
            'backUrl' => $backUrl,
            'relatedReservations' => $relatedReservations,
            'allClientReservationsUrl' => $allClientReservationsUrl,
            'noteEntries' => $noteEntries,
            'notesContent' => $notesContent,
        ]);
    }

    private function resolveReservationOffer(Reservation $reservation): ?Voyage
    {
        return $reservation->offer
            ?? $reservation->voyage
            ?? $reservation->tour
            ?? null;
    }

    private function resolveReservationGroupKey(Reservation $reservation): string
    {
        $offer = $this->resolveReservationOffer($reservation);

        if ($offer) {
            if ((int) ($offer->wp_post_id ?? 0) > 0) {
                return 'wp-tour:'.(int) $offer->wp_post_id;
            }

            return 'voyage:'.(int) $offer->id;
        }

        if ((int) ($reservation->wp_tour_post_id ?? 0) > 0) {
            return 'legacy-wp:'.(int) $reservation->wp_tour_post_id;
        }

        return 'reservation:'.(int) $reservation->id;
    }

    private function resolveOfferImageUrl(?Voyage $offer): ?string
    {
        if (! $offer) {
            return null;
        }

        if (! empty($offer->image_url)) {
            return $offer->image_url;
        }

        if (! empty($offer->featured_image_url)) {
            return $offer->featured_image_url;
        }

        if (! empty($offer->cover_image)) {
            return asset('storage/'.ltrim((string) $offer->cover_image, '/'));
        }

        if (! empty($offer->thumbnail)) {
            return asset('storage/'.ltrim((string) $offer->thumbnail, '/'));
        }

        if (! empty($offer->featured_image)) {
            $featuredImage = (string) $offer->featured_image;

            return str_starts_with($featuredImage, 'http://') || str_starts_with($featuredImage, 'https://')
                ? $featuredImage
                : Storage::disk('public')->url($featuredImage);
        }

        if (! empty($offer->wp_image_url)) {
            return $offer->wp_image_url;
        }

        if ((int) ($offer->wp_post_id ?? 0) > 0) {
            $heroImageId = \App\Models\Wp\WpPostMeta::query()
                ->where('post_id', (int) $offer->wp_post_id)
                ->whereIn('meta_key', ['_tour_hero_image_id', '_thumbnail_id'])
                ->orderByRaw("FIELD(meta_key, '_tour_hero_image_id', '_thumbnail_id')")
                ->value('meta_value');

            if ((int) $heroImageId > 0) {
                return WpHeroImageService::publicUrlForAttachmentId((int) $heroImageId);
            }
        }

        return null;
    }
}
