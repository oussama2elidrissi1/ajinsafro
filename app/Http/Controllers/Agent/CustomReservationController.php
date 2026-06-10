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
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.view'), 403);

        $filters = [
            'client' => trim((string) $request->query('client', '')),
            'destination' => trim((string) $request->query('destination', '')),
            'status' => trim((string) $request->query('status', '')),
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

        if ($filters['date'] !== '') {
            $query->whereDate('desired_departure_date', $filters['date']);
        }

        return view('agent.custom-reservations.index', [
            'requests' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => CustomRequest::statusOptions(),
            'travelTypeOptions' => CustomRequest::travelTypeOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.create'), 403);

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
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.create'), 403);

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
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.create'), 403);

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

        if ($request->input('submit_action') === 'submit') {
            $this->generateSummaryQuote($customRequest, $user);
        }

        if ($customRequest->status === CustomRequest::STATUS_NEW) {
            $this->notifications->notifyNewRequest($customRequest);
        }

        return redirect()
            ->route('agent.custom-reservations.show', $customRequest)
            ->with('success', $customRequest->status === CustomRequest::STATUS_NEW ? 'Demande à la carte créée.' : 'Brouillon enregistré.');
    }

    public function show(Request $request, CustomRequest $customRequest): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.view'), 403);
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
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.view'), 403);
        abort_unless($this->agentCanAccessRequest($customRequest, $user), 403);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);
        abort_unless($quote->pdf_path && Storage::disk('public')->exists($quote->pdf_path), 404);

        return Storage::disk('public')->download($quote->pdf_path, basename($quote->pdf_path));
    }

    private function scopeOwnedByAgent(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId)
            ->orWhere('assigned_to', $userId);
    }

    private function agentCanAccessRequest(CustomRequest $customRequest, User $user): bool
    {
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
            'adults_count' => ['required', 'integer', 'min:1'],
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

        if ((int) $data['travelers_count'] < ((int) $data['adults_count'] + $data['children_count'] + $data['babies_count'])) {
            return back()
                ->withErrors(['travelers_count' => 'Le nombre total de voyageurs doit être cohérent avec adultes, enfants et bébés.'])
                ->withInput()
                ->throwResponse();
        }

        return Arr::except($data, ['documents']);
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

    private function generateSummaryQuote(CustomRequest $customRequest, User $user): void
    {
        $quote = $customRequest->quotes()->create([
            'created_by' => $user->id,
            'version' => 1,
            'currency' => $customRequest->currency ?: 'MAD',
            'summary_mode' => true,
            'paid_amount' => 0,
        ]);

        $path = $quote->generatePdf();
        $quote->markAsPrepared();

        $customRequest->documents()->updateOrCreate(
            ['quote_id' => $quote->id, 'document_type' => 'quote'],
            [
                'uploaded_by' => $user->id,
                'title' => 'Devis '.$quote->quote_number.' v'.$quote->version,
                'file_path' => $path,
                'original_name' => basename($path),
                'mime_type' => 'application/pdf',
                'size' => Storage::disk('public')->size($path),
                'is_auto_generated' => true,
            ]
        );
    }

}
