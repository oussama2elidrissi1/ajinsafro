<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ReservationDossier;
use App\Models\User;
use App\Models\Voyage;
use App\Services\ReservationVisibilityService;
use App\Services\Wp\WpHeroImageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReservationDossierController extends Controller
{
    public function __construct(
        protected ReservationVisibilityService $reservationVisibility,
    ) {}

    public function index(Request $request): View
    {
        $status = trim((string) $request->query('status', ''));

        // Global stats (unfiltered base) for KPI cards
        $globalStats = [
            'total' => ReservationDossier::count(),
            'pending' => ReservationDossier::whereIn('dossier_status', ['draft', 'pending'])->count(),
            'remaining' => ReservationDossier::where('remaining_amount', '>', 0)->count(),
            'paid' => ReservationDossier::where('payment_status', 'paid')->count(),
        ];

        $query = ReservationDossier::query()
            ->with([
                'client',
                'creator',
                'assignedTo',
                'mainReservation.offer',
                'mainReservation.departure',
                'mainReservation.passengers',
            ]);

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('dossier_number', 'like', '%'.$search.'%')
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('full_name', 'like', '%'.$search.'%')
                            ->orWhere('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%')
                            ->orWhere('national_id_number', 'like', '%'.$search.'%')
                            ->orWhere('passport_number', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('mainReservation.offer', fn ($offerQuery) => $offerQuery->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('mainReservation', function ($reservationQuery) use ($search) {
                        $reservationQuery->where('client_phone', 'like', '%'.$search.'%')
                            ->orWhere('client_first_name', 'like', '%'.$search.'%')
                            ->orWhere('client_last_name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($voyageId = (int) $request->query('voyage_id', 0)) {
            $query->whereHas('mainReservation', fn ($reservationQuery) => $reservationQuery->where('tour_id', $voyageId));
        }

        if ($departureDate = trim((string) $request->query('departure_date', ''))) {
            $query->whereHas('mainReservation.departure', fn ($departureQuery) => $departureQuery->whereDate('start_date', $departureDate));
        }

        if ($agentId = (int) $request->query('agent_id', 0)) {
            $query->where(function ($builder) use ($agentId) {
                $builder->where('assigned_to', $agentId)->orWhere('created_by', $agentId);
            });
        }

        if ($dossierStatus = trim((string) $request->query('dossier_status', ''))) {
            $query->where('dossier_status', $dossierStatus);
        }

        if ($paymentStatus = trim((string) $request->query('payment_status', ''))) {
            $query->where('payment_status', $paymentStatus);
        }

        // Gestion du parametre status (prioritaire)
        if ($status !== '' && $status !== 'all') {
            match ($status) {
                'pending' => $query->whereIn('dossier_status', ['draft', 'pending']),
                'paid' => $query->where('payment_status', 'paid'),
                'follow_up' => $query->where('remaining_amount', '>', 0),
                default => null,
            };
        } else {
            // Fallback: legacy boolean params
            if ($request->boolean('remaining_only')) {
                $query->where('remaining_amount', '>', 0);
            }
            if ($request->boolean('payment_complete')) {
                $query->where('remaining_amount', '<=', 0);
            }
            if ($request->boolean('pending_only')) {
                $query->whereIn('dossier_status', ['draft', 'pending']);
            }
        }

        if ($request->boolean('today')) {
            $query->whereDate('created_at', now()->toDateString());
        }

        $dossiers = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $stats = $globalStats;

        return view('admin.reservation-dossiers.index', [
            'dossiers' => $dossiers,
            'stats' => $stats,
            'currentStatus' => $status !== '' && $status !== 'all' ? $status : 'all',
            'filters' => [
                'search' => $request->query('search'),
                'voyage_id' => $request->query('voyage_id'),
                'departure_date' => $request->query('departure_date'),
                'agent_id' => $request->query('agent_id'),
                'dossier_status' => $request->query('dossier_status'),
                'payment_status' => $request->query('payment_status'),
                'remaining_only' => $request->boolean('remaining_only'),
                'payment_complete' => $request->boolean('payment_complete'),
                'today' => $request->boolean('today'),
                'pending_only' => $request->boolean('pending_only'),
            ],
            'voyageOptions' => Voyage::query()->orderBy('name')->limit(300)->get(['id', 'name']),
            'agentOptions' => User::query()->orderBy('name')->limit(200)->get(['id', 'name']),
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

        return view('admin.reservation-dossiers.show', [
            'dossier' => $reservationDossier,
            'reservation' => $reservation,
            'offer' => $offer,
            'offerImageUrl' => $offerImageUrl,
            'backUrl' => $backUrl,
            'relatedReservations' => $relatedReservations,
            'allClientReservationsUrl' => $allClientReservationsUrl,
        ]);
    }
}
