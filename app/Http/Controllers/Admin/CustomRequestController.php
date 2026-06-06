<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Models\CustomRequestDocument;
use App\Models\CustomRequestQuote;
use App\Models\User;
use App\Services\CustomRequestNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomRequestController extends Controller
{
    public function __construct(private readonly CustomRequestNotificationService $notifications) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('custom_requests.view'), 403);

        $query = CustomRequest::query()
            ->with(['creator:id,name', 'assignedAgent:id,name', 'latestQuote'])
            ->visibleTo($request->user());

        $assignedToFilter = $request->query('assigned_to');
        $assignedTo = $assignedToFilter === 'me' ? (int) $request->user()->id : (int) $assignedToFilter;

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'created_by' => (int) $request->query('created_by', 0),
            'assigned_to' => $assignedTo,
            'destination' => trim((string) $request->query('destination', '')),
            'date' => trim((string) $request->query('date', '')),
            'search' => trim((string) $request->query('search', '')),
        ];

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if ($filters['priority'] !== '') {
            $query->where('priority', $filters['priority']);
        }
        if ($filters['created_by'] > 0) {
            $query->where('created_by', $filters['created_by']);
        }
        if ($filters['assigned_to'] > 0) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
        if ($filters['destination'] !== '') {
            $query->where('desired_destination', 'like', '%'.$filters['destination'].'%');
        }
        if ($filters['date'] !== '') {
            $query->whereDate('desired_departure_date', $filters['date']);
        }
        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('request_number', 'like', $like)
                    ->orWhere('customer_full_name', 'like', $like)
                    ->orWhere('customer_phone', 'like', $like)
                    ->orWhere('customer_email', 'like', $like);
            });
        }

        $base = CustomRequest::query()->visibleTo($request->user());
        $stats = [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', CustomRequest::STATUS_NEW)->count(),
            'urgent' => (clone $base)->whereIn('priority', ['urgent', 'very_urgent'])->count(),
            'modification_requested' => (clone $base)->where('status', CustomRequest::STATUS_MODIFICATION_REQUESTED)->count(),
            'quote_sent' => (clone $base)->where('status', CustomRequest::STATUS_QUOTE_SENT)->count(),
            'confirmed' => (clone $base)->where('status', CustomRequest::STATUS_CONFIRMED)->count(),
        ];

        return view('admin.custom-requests.index', $this->sharedViewData($request) + [
            'customRequests' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('custom_requests.create'), 403);

        return view('admin.custom-requests.create', $this->sharedViewData($request) + [
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
            'formAction' => route('admin.custom-requests.store'),
            'formMethod' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('custom_requests.create'), 403);

        $data = $this->validatedPayload($request);
        $data['created_by'] = $request->user()->id;
        $data['status'] = $request->input('submit_action') === 'submit'
            ? CustomRequest::STATUS_NEW
            : CustomRequest::STATUS_DRAFT;

        $customRequest = DB::transaction(function () use ($request, $data): CustomRequest {
            $customRequest = CustomRequest::query()->create($data);
            $this->syncServices($customRequest, (array) $request->input('services', []));
            $this->storeUploadedDocuments($request, $customRequest);
            $customRequest->statusLogs()->create([
                'user_id' => $request->user()->id,
                'old_status' => null,
                'new_status' => $customRequest->status,
                'note' => $customRequest->status === CustomRequest::STATUS_NEW ? 'Demande créée et soumise.' : 'Brouillon créé.',
            ]);

            return $customRequest;
        });

        if ($customRequest->status === CustomRequest::STATUS_NEW) {
            $this->notifications->notifyNewRequest($customRequest);
        }

        return redirect()
            ->route('admin.custom-requests.show', $customRequest)
            ->with('success', $customRequest->status === CustomRequest::STATUS_NEW ? 'Demande à la carte créée.' : 'Brouillon enregistré.');
    }

    public function show(Request $request, CustomRequest $customRequest): View
    {
        $this->authorizeVisible($request, $customRequest);

        return view('admin.custom-requests.show', $this->sharedViewData($request) + [
            'customRequest' => $customRequest->load([
                'creator:id,name,email',
                'assignedAgent:id,name,email',
                'services',
                'documents',
                'quotes.items',
                'comments.user:id,name',
                'statusLogs.user:id,name',
                'latestQuote.items',
            ]),
        ]);
    }

    public function edit(Request $request, CustomRequest $customRequest): View
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($customRequest->canBeEditedBy($request->user()), 403);

        return view('admin.custom-requests.edit', $this->sharedViewData($request) + [
            'customRequest' => $customRequest->load('services'),
            'formAction' => route('admin.custom-requests.update', $customRequest),
            'formMethod' => 'PUT',
        ]);
    }

    public function update(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($customRequest->canBeEditedBy($request->user()), 403);

        $data = $this->validatedPayload($request, $customRequest);

        DB::transaction(function () use ($request, $customRequest, $data): void {
            $customRequest->update($data);
            $this->syncServices($customRequest, (array) $request->input('services', []));
            $this->storeUploadedDocuments($request, $customRequest);
        });

        return redirect()->route('admin.custom-requests.show', $customRequest)->with('success', 'Demande mise à jour.');
    }

    public function destroy(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($request->user()?->can('custom_requests.delete'), 403);

        $customRequest->delete();

        return redirect()->route('admin.custom-requests.index')->with('success', 'Demande archivée.');
    }

    public function submit(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless((int) $customRequest->created_by === (int) $request->user()->id || $request->user()->can('custom_requests.view_all'), 403);
        abort_unless($customRequest->status === CustomRequest::STATUS_DRAFT, 422);

        $customRequest->changeStatus(CustomRequest::STATUS_NEW, $request->user()->id, 'Brouillon soumis.');
        $this->notifications->notifyNewRequest($customRequest);

        return back()->with('success', 'Demande soumise aux agents offline.');
    }

    public function assign(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($request->user()?->can('custom_requests.assign'), 403);

        $data = $request->validate([
            'assigned_to' => ['required', Rule::exists('users', 'id')],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $customRequest->forceFill(['assigned_to' => $data['assigned_to']])->save();
        $customRequest->changeStatus(CustomRequest::STATUS_ASSIGNED, $request->user()->id, $data['note'] ?? 'Demande assignée.');
        $this->notifications->notifyAssigned($customRequest->fresh('assignedAgent'));

        return back()->with('success', 'Demande assignée.');
    }

    public function take(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($request->user()?->can('custom_requests.quote'), 403);

        $customRequest->forceFill(['assigned_to' => $request->user()->id])->save();
        $customRequest->changeStatus(CustomRequest::STATUS_PROCESSING, $request->user()->id, 'Prise en charge par agent offline.');

        return redirect()->route('admin.custom-requests.quote', $customRequest)->with('success', 'Demande prise en charge.');
    }

    public function requestModification(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless((int) $customRequest->created_by === (int) $request->user()->id || $request->user()->can('custom_requests.view_all'), 403);

        $data = $request->validate(['message' => ['required', 'string', 'max:4000']]);
        $quote = $customRequest->latestQuote;
        abort_unless($quote, 422, 'Aucun devis à modifier.');

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

        return back()->with('success', 'Modification demandée à l’agent offline.');
    }

    public function confirm(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($request->user()?->can('custom_requests.confirm'), 403);

        DB::transaction(function () use ($request, $customRequest): void {
            $customRequest->latestQuote?->update(['status' => CustomRequestQuote::STATUS_ACCEPTED]);
            $customRequest->changeStatus(CustomRequest::STATUS_CONFIRMED, $request->user()->id, 'Client confirmé.');
        });

        return back()->with('success', 'Demande confirmée.');
    }

    public function cancel(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($request->user()?->can('custom_requests.cancel'), 403);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $customRequest->changeStatus(CustomRequest::STATUS_CANCELLED, $request->user()->id, $data['note'] ?? 'Demande annulée.');

        return back()->with('success', 'Demande annulée.');
    }

    private function validatedPayload(Request $request, ?CustomRequest $customRequest = null): array
    {
        $data = $request->validate([
            'customer_full_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_city' => ['nullable', 'string', 'max:255'],
            'customer_country' => ['nullable', 'string', 'max:255'],
            'customer_identity' => ['nullable', 'string', 'max:255'],
            'customer_type' => ['required', Rule::in(['new_customer', 'existing_customer'])],
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
            abort(422, 'Le nombre total de voyageurs doit être cohérent avec adultes, enfants et bébés.');
        }

        return Arr::except($data, ['documents']);
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

    private function authorizeVisible(Request $request, CustomRequest $customRequest): void
    {
        abort_unless($request->user()?->can('custom_requests.view'), 403);
        abort_unless(CustomRequest::query()->visibleTo($request->user())->whereKey($customRequest->getKey())->exists(), 403);
    }

    private function sharedViewData(Request $request): array
    {
        return [
            'statusOptions' => CustomRequest::statusOptions(),
            'priorityOptions' => CustomRequest::priorityOptions(),
            'paymentStatusOptions' => CustomRequest::paymentStatusOptions(),
            'travelTypeOptions' => CustomRequest::travelTypeOptions(),
            'serviceOptions' => CustomRequest::serviceOptions(),
            'quoteStatusOptions' => CustomRequestQuote::statusOptions(),
            'agents' => User::query()->canLogin()->orderBy('name')->get(['id', 'name', 'email']),
        ];
    }
}
