<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Models\User;
use App\Services\CustomRequestNotificationService;
use App\Services\View\AgentPortalLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomReservationController extends Controller
{
    public function __construct(private readonly CustomRequestNotificationService $notifications) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $user->can('custom_requests.view'), 403);
        $canCreateRequest = $this->canCreateCustomRequest($user);

        $filters = [
            'client' => trim((string) $request->query('client', '')),
            'destination' => trim((string) $request->query('destination', '')),
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'date' => trim((string) $request->query('date', '')),
        ];

        $query = CustomRequest::query()
            ->with(['latestQuote', 'assignedAgent:id,name'])
            ->visibleTo($user);

        if ($filters['client'] !== '') {
            $like = '%'.$filters['client'].'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('request_number', 'like', $like)
                    ->orWhere('customer_full_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('customer_email', 'like', $like);
            });
        }

        if ($filters['destination'] !== '') {
            $query->where('desired_destination', 'like', '%'.$filters['destination'].'%');
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['priority'] !== '') {
            $query->where('priority', $filters['priority']);
        }

        if ($filters['date'] !== '') {
            $query->whereDate('desired_departure_date', $filters['date']);
        }

        $dashboard = $this->buildDashboardData($user);

        return view('agent.custom-reservations.index', [
            'requests' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => CustomRequest::statusOptions(),
            'priorityOptions' => CustomRequest::priorityOptions(),
            'travelTypeOptions' => CustomRequest::travelTypeOptions(),
            'canCreateRequest' => $canCreateRequest,
            'dashboard' => $dashboard,
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && $this->canCreateCustomRequest($user), 403);

        $selectedClient = null;
        $selectedClientId = (int) old('existing_client_id', 0);
        if ($selectedClientId > 0) {
            $selectedClient = Client::query()
                ->ownedByAgent($user)
                ->find($selectedClientId);
        }

        return view('agent.custom-requests.create', $this->sharedViewData() + [
            'customRequest' => new CustomRequest([
                'customer_type' => 'new_customer',
                'travelers_count' => 1,
                'adults_count' => 1,
                'children_count' => 0,
                'babies_count' => 0,
                'currency' => 'MAD',
                'status' => CustomRequest::STATUS_DRAFT,
                'priority' => 'normal',
                'payment_status' => 'unpaid',
                'paid_amount' => 0,
            ]),
            'selectedExistingClient' => $selectedClient,
            'clientSearchUrl' => route('agent.custom-reservations.clients.search'),
            'formAction' => route('agent.custom-reservations.store'),
        ]);
    }

    public function searchClients(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        abort_unless($user && $this->canCreateCustomRequest($user), 403);

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([
                'q' => $q,
                'count' => 0,
                'items' => [],
            ]);
        }

        $normalized = preg_replace('/[\s\-\.\\/\\\\]+/', '', $q) ?: '';
        $hasNormalized = mb_strlen($normalized) >= 2;

        $query = Client::query()
            ->ownedByAgent($user)
            ->where(function (Builder $builder) use ($q, $normalized, $hasNormalized): void {
                $builder->where('client_code', 'like', '%'.$q.'%')
                    ->orWhere('full_name', 'like', '%'.$q.'%')
                    ->orWhere('first_name', 'like', '%'.$q.'%')
                    ->orWhere('last_name', 'like', '%'.$q.'%')
                    ->orWhere('email', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('whatsapp_number', 'like', '%'.$q.'%')
                    ->orWhere('national_id_number', 'like', '%'.$q.'%')
                    ->orWhere('passport_number', 'like', '%'.$q.'%');

                if ($hasNormalized) {
                    $builder->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%'])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(whatsapp_number, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%'])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(national_id_number, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%'])
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(passport_number, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%']);
                }
            });

        $items = $query
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'client_code', 'full_name', 'first_name', 'last_name', 'phone', 'email', 'city', 'country_of_residence', 'national_id_number', 'passport_number'])
            ->map(static function (Client $client): array {
                return [
                    'id' => $client->id,
                    'client_code' => $client->client_code,
                    'full_name' => $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                    'phone' => $client->phone,
                    'email' => $client->email,
                    'city' => $client->city,
                    'country' => $client->country_of_residence,
                    'identity' => $client->national_id_number ?: $client->passport_number,
                    'label' => trim($client->client_code.' - '.($client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? '')))),
                ];
            })
            ->values();

        return response()->json([
            'q' => $q,
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $this->canCreateCustomRequest($user), 403);

        $data = $this->validatedPayload($request);
        $data['created_by'] = $user->id;
        $data['assigned_to'] = null;
        $data['status'] = $request->input('submit_action') === 'draft'
            ? CustomRequest::STATUS_DRAFT
            : CustomRequest::STATUS_NEW;
        $data = $this->hydrateExistingClient($user, $data);

        $customRequest = DB::transaction(function () use ($request, $user, $data): CustomRequest {
            $customRequest = CustomRequest::query()->create($data);
            $this->syncServices($customRequest, (array) $request->input('services', []));
            $this->storeUploadedDocuments($request, $customRequest);
            $customRequest->statusLogs()->create([
                'user_id' => $user->id,
                'old_status' => null,
                'new_status' => $customRequest->status,
                'note' => $customRequest->status === CustomRequest::STATUS_NEW
                    ? 'Demande créée et soumise depuis l’espace agent.'
                    : 'Brouillon créé depuis l’espace agent.',
            ]);

            return $customRequest;
        });

        if ($customRequest->status === CustomRequest::STATUS_NEW) {
            $this->notifications->notifyNewRequest($customRequest);
        }

        $successMessage = $customRequest->status === CustomRequest::STATUS_NEW
            ? 'Demande à la carte créée.'
            : 'Brouillon enregistré.';

        if ($user->can('custom_requests.view')) {
            return redirect()
                ->route('agent.custom-reservations.show', $customRequest)
                ->with('success', $successMessage);
        }

        return redirect()
            ->route('agent.custom-reservations.create')
            ->with('success', $successMessage.' Réf. '.$customRequest->request_number);
    }

    public function take(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('custom_requests.quote'), 403);
        abort_unless($customRequest->canBeQuotedBy($user), 403);

        $customRequest->forceFill(['assigned_to' => $user->id])->save();
        $customRequest->changeStatus(CustomRequest::STATUS_PROCESSING, $user->id, 'Prise en charge par agent offline.');

        return redirect()
            ->route('agent.custom-reservations.quote', $customRequest)
            ->with('success', 'Demande prise en charge.');
    }

    public function show(Request $request, CustomRequest $customRequest): View
    {
        $user = $request->user();
        abort_unless($user && $user->can('custom_requests.view'), 403);
        abort_unless($this->agentCanAccessRequest($customRequest, $user), 403);

        return view('agent.custom-reservations.show', [
            'customRequest' => $customRequest->load([
                'creator:id,name,email',
                'assignedAgent:id,name,email',
                'client:id,client_code,full_name,phone,email',
                'services',
                'documents',
                'quotes.items',
                'latestQuote.generatedDocument',
                'latestQuote.items',
                'comments.user:id,name',
                'statusLogs.user:id,name',
            ]),
            'travelTypeOptions' => CustomRequest::travelTypeOptions(),
            'statusOptions' => CustomRequest::statusOptions(),
            'quoteStatusOptions' => CustomRequestQuote::statusOptions(),
        ]);
    }

    public function downloadQuote(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('custom_requests.view'), 403);
        abort_unless($this->agentCanAccessRequest($customRequest, $user), 403);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);
        abort_unless($quote->pdf_path && Storage::disk('public')->exists($quote->pdf_path), 404);

        return Storage::disk('public')->download($quote->pdf_path, basename($quote->pdf_path));
    }

    public function requestModification(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->can('custom_requests.view'), 403);
        abort_unless($this->agentCanAccessRequest($customRequest, $user), 403);
        abort_unless((int) ($customRequest->created_by ?? 0) === (int) $user->id || $user->can('custom_requests.view_all'), 403);

        $quote = $customRequest->latestQuote()->first();
        abort_unless($quote, 422, 'Aucun devis a modifier.');
        abort_unless(in_array($customRequest->status, [
            CustomRequest::STATUS_QUOTE_PREPARED,
            CustomRequest::STATUS_QUOTE_SENT,
            CustomRequest::STATUS_WAITING_CUSTOMER,
            CustomRequest::STATUS_MODIFICATION_REQUESTED,
        ], true), 422, 'Ce devis ne peut pas encore etre modifie.');

        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);

        DB::transaction(function () use ($request, $customRequest, $quote, $data): void {
            $customRequest->comments()->create([
                'user_id' => $request->user()->id,
                'comment_type' => 'modification_request',
                'message' => $data['message'],
            ]);
            $quote->update(['status' => CustomRequestQuote::STATUS_MODIFICATION_REQUESTED]);
            $customRequest->changeStatus(CustomRequest::STATUS_MODIFICATION_REQUESTED, $request->user()->id, $data['message']);
        });

        $this->notifications->notifyModificationRequested($customRequest->fresh(['assignedAgent']), $quote);

        return back()->with('success', 'Modification demandee a l agent offline.');
    }

    private function canCreateCustomRequest(User $user): bool
    {
        if ($user->can('custom_requests.create')) {
            return true;
        }

        return $user->hasRole('Agent Offline') || (string) $user->base_role === 'Agent Offline';
    }

    private function buildDashboardData(User $user): array
    {
        $baseQuery = CustomRequest::query()->visibleTo($user);
        $statusCounts = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value)
            ->all();
        $priorityCounts = (clone $baseQuery)
            ->select('priority', DB::raw('count(*) as total'))
            ->groupBy('priority')
            ->pluck('total', 'priority')
            ->map(fn ($value) => (int) $value)
            ->all();

        $inProgressStatuses = [
            CustomRequest::STATUS_ASSIGNED,
            CustomRequest::STATUS_PROCESSING,
            CustomRequest::STATUS_MISSING_INFO,
            CustomRequest::STATUS_MODIFICATION_REQUESTED,
            CustomRequest::STATUS_QUOTE_PREPARED,
            CustomRequest::STATUS_WAITING_CUSTOMER,
        ];
        $pendingStatuses = [
            CustomRequest::STATUS_DRAFT,
            CustomRequest::STATUS_NEW,
            CustomRequest::STATUS_ASSIGNED,
        ];
        $confirmedStatuses = [
            CustomRequest::STATUS_CONFIRMED,
        ];
        $closedStatuses = [
            CustomRequest::STATUS_CANCELLED,
            CustomRequest::STATUS_REFUSED,
        ];

        $actionRequests = (clone $baseQuery)
            ->with(['latestQuote', 'assignedAgent:id,name'])
            ->where(function (Builder $query) use ($user): void {
                $query->whereIn('status', [
                    CustomRequest::STATUS_NEW,
                    CustomRequest::STATUS_PROCESSING,
                    CustomRequest::STATUS_MODIFICATION_REQUESTED,
                    CustomRequest::STATUS_MISSING_INFO,
                ])
                    ->orWhere('priority', 'urgent')
                    ->orWhere('priority', 'very_urgent')
                    ->orWhere('assigned_to', $user->id);
            })
            ->orderByRaw("CASE WHEN priority = 'very_urgent' THEN 0 WHEN priority = 'urgent' THEN 1 ELSE 2 END")
            ->latest()
            ->limit(5)
            ->get();

        return [
            'total' => (clone $baseQuery)->count(),
            'new' => $statusCounts[CustomRequest::STATUS_NEW] ?? 0,
            'in_progress' => array_sum(array_map(fn (string $status) => $statusCounts[$status] ?? 0, $inProgressStatuses)),
            'quote_sent' => $statusCounts[CustomRequest::STATUS_QUOTE_SENT] ?? 0,
            'confirmed' => $statusCounts[CustomRequest::STATUS_CONFIRMED] ?? 0,
            'urgent' => (clone $baseQuery)->whereIn('priority', ['urgent', 'very_urgent'])->count(),
            'assigned_to_me' => (clone $baseQuery)->where('assigned_to', $user->id)->count(),
            'created_by_me' => (clone $baseQuery)->where('created_by', $user->id)->count(),
            'today' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'upcoming_departures' => (clone $baseQuery)
                ->whereNotNull('desired_departure_date')
                ->whereDate('desired_departure_date', '>=', today())
                ->whereDate('desired_departure_date', '<=', today()->addDays(7))
                ->count(),
            'action_requests' => $actionRequests,
            'status_counts' => $statusCounts,
            'priority_counts' => $priorityCounts,
            'status_groups' => [
                'pending' => array_sum(array_map(fn (string $status) => $statusCounts[$status] ?? 0, $pendingStatuses)),
                'processing' => array_sum(array_map(fn (string $status) => $statusCounts[$status] ?? 0, $inProgressStatuses)),
                'quote_sent' => $statusCounts[CustomRequest::STATUS_QUOTE_SENT] ?? 0,
                'confirmed' => array_sum(array_map(fn (string $status) => $statusCounts[$status] ?? 0, $confirmedStatuses)),
                'closed' => array_sum(array_map(fn (string $status) => $statusCounts[$status] ?? 0, $closedStatuses)),
            ],
            'account' => [
                'name' => $user->name,
                'role' => $user->getRoleNames()->first() ?: ($user->job_title ?: 'Agent'),
                'branch' => $user->branch?->name,
                'can_quote' => $user->canQuoteCustomRequests(),
                'can_create' => $this->canCreateCustomRequest($user),
            ],
        ];
    }

    private function scopeOwnedByAgent(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId)
            ->orWhere('assigned_to', $userId);
    }

    private function agentCanAccessRequest(CustomRequest $customRequest, User $user): bool
    {
        if ($user->can('custom_requests.view_all') || $user->can('custom_requests.assign')) {
            return true;
        }

        if ($user->isManager()) {
            $teamIds = User::query()
                ->where('manager_id', $user->id)
                ->when($user->branch_id, fn (Builder $query) => $query->where('branch_id', $user->branch_id))
                ->pluck('id')
                ->push($user->id)
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            return in_array((int) ($customRequest->created_by ?? 0), $teamIds, true)
                || in_array((int) ($customRequest->assigned_to ?? 0), $teamIds, true);
        }

        if ((int) ($customRequest->created_by ?? 0) === (int) $user->id) {
            return true;
        }

        if ((int) ($customRequest->assigned_to ?? 0) === (int) $user->id) {
            return true;
        }

        if ($customRequest->canBeQuotedBy($user)) {
            return true;
        }

        return false;
    }

    private function agentOwnsRequest(CustomRequest $customRequest, int $userId): bool
    {
        return in_array($userId, array_filter([
            (int) ($customRequest->created_by ?? 0),
            (int) ($customRequest->assigned_to ?? 0),
        ]), true);
    }

    private function validatedPayload(Request $request): array
    {
        $data = $request->validate([
            'customer_full_name' => ['required_unless:customer_type,existing_customer', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_unless:customer_type,existing_customer', 'nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_city' => ['nullable', 'string', 'max:255'],
            'customer_country' => ['nullable', 'string', 'max:255'],
            'customer_identity' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['required', Rule::in(['new_customer', 'existing_customer'])],
            'existing_client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(function ($query) use ($request) {
                    $query->where(function ($builder) use ($request): void {
                        $builder->where('created_by', $request->user()->id)
                            ->orWhere('assigned_to', $request->user()->id);
                    });
                }),
            ],
            'customer_notes' => ['nullable', 'string'],
            'desired_destination' => ['required', 'string', 'max:255'],
            'departure_city' => ['required', 'string', 'max:255'],
            'desired_departure_date' => ['required', 'date'],
            'desired_return_date' => ['nullable', 'date', 'after_or_equal:desired_departure_date'],
            'desired_duration' => ['nullable', 'string', 'max:255'],
            'travel_type' => ['required', Rule::in(array_keys(CustomRequest::travelTypeOptions()))],
            'travelers_count' => ['required', 'integer', 'min:1'],
            'children_count' => ['nullable', 'integer', 'min:0'],
            'babies_count' => ['nullable', 'integer', 'min:0'],
            'approximate_budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', Rule::in(['MAD', 'EUR', 'USD'])],
            'desired_level' => ['nullable', Rule::in(['economy', 'standard', 'comfort', 'premium', 'luxury'])],
            'desired_hotel' => ['nullable', 'string', 'max:255'],
            'hotel_category' => ['nullable', Rule::in(['3_stars', '4_stars', '5_stars', 'riad', 'apartment', 'villa', 'unspecified'])],
            'meal_plan' => ['nullable', Rule::in(['room_only', 'breakfast', 'half_board', 'full_board', 'all_inclusive'])],
            'rooms_count' => ['nullable', 'integer', 'min:1'],
            'room_type' => ['nullable', Rule::in(['single', 'double', 'triple', 'quadruple', 'family'])],
            'separate_room_needed' => ['nullable', 'boolean'],
            'accommodation_notes' => ['nullable', 'string'],
            'flight_included' => ['nullable', Rule::in(['yes', 'no', 'to_confirm'])],
            'preferred_airline' => ['nullable', 'string', 'max:255'],
            'departure_airport' => ['nullable', 'string', 'max:255'],
            'arrival_airport' => ['nullable', 'string', 'max:255'],
            'baggage_included' => ['nullable', Rule::in(['yes', 'no', 'to_confirm'])],
            'airport_transfer_included' => ['nullable', Rule::in(['yes', 'no'])],
            'local_transport' => ['nullable', Rule::in(['none', 'bus', 'minibus', 'private_car', 'private_driver'])],
            'transport_notes' => ['nullable', 'string'],
            'requested_services_details' => ['nullable', 'string'],
            'estimated_price' => ['nullable', 'numeric', 'min:0'],
            'requested_deposit' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(['cash', 'transfer', 'card', 'cheque', 'other'])],
            'payment_status' => ['required', Rule::in(array_keys(CustomRequest::paymentStatusOptions()))],
            'priority' => ['required', Rule::in(array_keys(CustomRequest::priorityOptions()))],
            'response_deadline' => ['nullable', 'date'],
            'internal_notes' => ['nullable', 'string'],
            'documents.*' => ['nullable', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $data['children_count'] = (int) ($data['children_count'] ?? 0);
        $data['babies_count'] = (int) ($data['babies_count'] ?? 0);
        $data['paid_amount'] = (float) ($data['paid_amount'] ?? 0);
        $data['separate_room_needed'] = $request->boolean('separate_room_needed');
        $data['adults_count'] = (int) $data['travelers_count'];
        $data['desired_duration'] = $this->computeDesiredDuration(
            $data['desired_departure_date'] ?? null,
            $data['desired_return_date'] ?? null
        );

        if ((int) $data['travelers_count'] < ((int) $data['adults_count'] + $data['children_count'] + $data['babies_count'])) {
            return back()
                ->withErrors(['travelers_count' => 'Le nombre total de voyageurs doit être cohérent avec adultes, enfants et bébés.'])
                ->withInput()
                ->throwResponse();
        }

        return Arr::except($data, ['documents']);
    }

    private function computeDesiredDuration(mixed $departureDate, mixed $returnDate): ?string
    {
        if (! $departureDate || ! $returnDate) {
            return null;
        }

        try {
            $start = \Illuminate\Support\Carbon::parse($departureDate);
            $end = \Illuminate\Support\Carbon::parse($returnDate);
        } catch (\Throwable) {
            return null;
        }

        if ($end->lt($start)) {
            return null;
        }

        $days = max(1, $start->diffInDays($end));

        return $days.' '.($days > 1 ? 'nuits' : 'nuit');
    }

    private function hydrateExistingClient(User $user, array $data): array
    {
        $data['client_id'] = null;

        if (($data['customer_type'] ?? null) !== 'existing_customer') {
            $data['existing_client_id'] = null;

            return $data;
        }

        $clientId = (int) ($data['existing_client_id'] ?? 0);
        if ($clientId <= 0) {
            return back()
                ->withErrors(['existing_client_id' => 'Sélectionnez un client existant.'])
                ->withInput()
                ->throwResponse();
        }

        $client = Client::query()
            ->ownedByAgent($user)
            ->find($clientId);

        if (! $client) {
            return back()
                ->withErrors(['existing_client_id' => 'Le client sélectionné n’appartient pas à votre portefeuille.'])
                ->withInput()
                ->throwResponse();
        }

        $data['client_id'] = $client->id;
        $data['customer_full_name'] = $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? ''));
        $data['customer_phone'] = $client->phone ?: ($data['customer_phone'] ?? '');
        $data['customer_email'] = $client->email;
        $data['customer_city'] = $client->city;
        $data['customer_country'] = $client->country_of_residence;
        $data['customer_identity'] = $client->national_id_number ?: $client->passport_number;

        return $data;
    }

    private function syncServices(CustomRequest $customRequest, array $serviceKeys): void
    {
        $allowed = CustomRequest::serviceOptions();
        $customRequest->services()->delete();

        foreach (array_values(array_unique($serviceKeys)) as $key) {
            if (! isset($allowed[$key])) {
                continue;
            }

            $customRequest->services()->create([
                'service_key' => $key,
                'service_label' => $allowed[$key],
            ]);
        }
    }

    private function storeUploadedDocuments(Request $request, CustomRequest $customRequest): void
    {
        foreach ((array) $request->file('documents', []) as $file) {
            if (! $file) {
                continue;
            }

            $path = $file->store('custom-requests/'.$customRequest->id.'/documents', 'public');
            $customRequest->documents()->create([
                'uploaded_by' => $request->user()?->id,
                'document_type' => 'other',
                'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'file_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'is_auto_generated' => false,
            ]);
        }
    }

    private function sharedViewData(): array
    {
        return [
            'statusOptions' => CustomRequest::statusOptions(),
            'priorityOptions' => CustomRequest::priorityOptions(),
            'paymentStatusOptions' => CustomRequest::paymentStatusOptions(),
            'travelTypeOptions' => CustomRequest::travelTypeOptions(),
            'serviceOptions' => CustomRequest::serviceOptions(),
        ];
    }

}
