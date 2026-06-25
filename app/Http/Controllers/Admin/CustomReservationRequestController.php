<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCustomReservationRequestRequest;
use App\Http\Requests\Admin\UpdateCustomReservationRequestRequest;
use App\Models\CustomReservationRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect;

class CustomReservationRequestController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->can('reservations.view'), 403);

        if ($user->canQuoteCustomRequests()) {
            return redirect()->route('agent.custom-reservations.index');
        }

        $query = CustomReservationRequest::query()
            ->with(['assignedTo:id,name', 'createdBy:id,name'])
            ->visibleTo($user);

        $filters = [
            'status' => trim((string) $request->query('status', '')),
            'priority' => trim((string) $request->query('priority', '')),
            'assigned_to' => (int) $request->query('assigned_to', 0),
            'destination' => trim((string) $request->query('destination', '')),
            'search' => trim((string) $request->query('search', '')),
            'created_from' => trim((string) $request->query('created_from', '')),
            'created_to' => trim((string) $request->query('created_to', '')),
        ];

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if ($filters['priority'] !== '') {
            $query->where('priority', $filters['priority']);
        }
        if ($filters['assigned_to'] > 0) {
            $query->where('assigned_to', $filters['assigned_to']);
        }
        if ($filters['destination'] !== '') {
            $query->where('destination_text', 'like', '%'.$filters['destination'].'%');
        }
        if ($filters['created_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['created_from']);
        }
        if ($filters['created_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['created_to']);
        }
        if ($filters['search'] !== '') {
            $like = '%'.$filters['search'].'%';
            $query->where(function ($builder) use ($like): void {
                $builder->where('reference', 'like', $like)
                    ->orWhere('client_name', 'like', $like)
                    ->orWhere('client_phone', 'like', $like)
                    ->orWhere('client_whatsapp', 'like', $like)
                    ->orWhere('client_email', 'like', $like)
                    ->orWhere('destination_text', 'like', $like);
            });
        }

        $baseStats = CustomReservationRequest::query()->visibleTo($user);
        $stats = [
            'total' => (clone $baseStats)->count(),
            'new' => (clone $baseStats)->where('status', CustomReservationRequest::STATUS_NEW)->count(),
            'in_review' => (clone $baseStats)->where('status', CustomReservationRequest::STATUS_IN_REVIEW)->count(),
            'quoted' => (clone $baseStats)->where('status', CustomReservationRequest::STATUS_QUOTED)->count(),
            'converted' => (clone $baseStats)->where('status', CustomReservationRequest::STATUS_CONVERTED)->count(),
        ];

        return view('admin.reservations.custom-requests.index', [
            'requests' => $query->latest()->paginate(20)->withQueryString(),
            'stats' => $stats,
            'filters' => $filters,
            'agents' => $this->agents($user),
            'statusOptions' => CustomReservationRequest::statusOptions(),
            'priorityOptions' => CustomReservationRequest::priorityOptions(),
            'serviceOptions' => CustomReservationRequest::serviceOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can('reservations.view'), 403);

        return $this->formView('admin.reservations.custom-requests.create', new CustomReservationRequest([
            'status' => CustomReservationRequest::STATUS_NEW,
            'priority' => CustomReservationRequest::PRIORITY_NORMAL,
            'source' => 'admin',
            'client_type' => 'particular',
            'adults' => 1,
            'currency' => 'MAD',
            'whatsapp_same_as_phone' => true,
        ]), $request);
    }

    public function store(StoreCustomReservationRequestRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $data['created_by'] = $request->user()?->id;

        if ($request->input('submit_action') === 'draft') {
            $data['status'] = CustomReservationRequest::STATUS_DRAFT;
        }

        $customRequest = CustomReservationRequest::query()->create($data);

        if ($request->input('submit_action') === 'create_open') {
            return redirect()
                ->route('admin.reservations.custom-requests.show', $customRequest)
                ->with('success', 'Demande a la carte creee.');
        }

        return redirect()
            ->route('admin.reservations.custom-requests.index')
            ->with('success', 'Demande a la carte enregistree.');
    }

    public function show(Request $request, CustomReservationRequest $customRequest): View
    {
        $this->authorizeVisible($request, $customRequest);

        return view('admin.reservations.custom-requests.show', [
            'customRequest' => $customRequest->load(['assignedTo:id,name', 'createdBy:id,name', 'convertedReservation:id,dossier_number']),
            'statusOptions' => CustomReservationRequest::statusOptions(),
            'priorityOptions' => CustomReservationRequest::priorityOptions(),
            'serviceOptions' => CustomReservationRequest::serviceOptions(),
        ]);
    }

    public function edit(Request $request, CustomReservationRequest $customRequest): View
    {
        $this->authorizeVisible($request, $customRequest);

        return $this->formView('admin.reservations.custom-requests.edit', $customRequest, $request);
    }

    public function update(UpdateCustomReservationRequestRequest $request, CustomReservationRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        $customRequest->update($this->payload($request));

        return redirect()
            ->route('admin.reservations.custom-requests.show', $customRequest)
            ->with('success', 'Demande a la carte mise a jour.');
    }

    public function updateStatus(Request $request, CustomReservationRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($request->user()?->can('reservations.update') || $request->user()?->can('reservations.view'), 403);

        $validated = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(CustomReservationRequest::statusOptions()))],
            'admin_response' => ['nullable', 'string'],
            'quoted_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $customRequest->update(Arr::only($validated, ['status', 'admin_response', 'quoted_amount']));

        return back()->with('success', 'Statut mis a jour.');
    }

    public function take(Request $request, CustomReservationRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($this->canTakeRequest($request->user()), 403);

        $status = in_array($customRequest->status, [
            CustomReservationRequest::STATUS_DRAFT,
            CustomReservationRequest::STATUS_NEW,
        ], true)
            ? CustomReservationRequest::STATUS_IN_REVIEW
            : $customRequest->status;

        $customRequest->forceFill([
            'assigned_to' => $request->user()->id,
            'status' => $status,
        ])->save();

        return back()->with('success', 'Demande prise en charge par '.$request->user()->name.'.');
    }

    public function convertToReservation(Request $request, CustomReservationRequest $customRequest): RedirectResponse
    {
        $this->authorizeVisible($request, $customRequest);
        abort_unless($request->user()?->can('reservations.create') || $request->user()?->can('reservations.view'), 403);

        $nameParts = preg_split('/\s+/', trim((string) $customRequest->client_name)) ?: [];
        $firstName = array_shift($nameParts) ?: $customRequest->client_name;
        $lastName = trim(implode(' ', $nameParts));

        session()->flash('custom_reservation_prefill', [
            'custom_request_id' => $customRequest->id,
            'reference' => $customRequest->reference,
            'client_first_name' => $firstName,
            'client_last_name' => $lastName,
            'client_phone' => $customRequest->client_phone,
            'client_email' => $customRequest->client_email,
            'adults' => $customRequest->adults,
            'children' => $customRequest->children,
            'infants' => $customRequest->infants,
            'destination' => $customRequest->destination_text,
            'departure_city' => $customRequest->departure_city_text,
            'departure_date' => optional($customRequest->departure_date)->toDateString(),
            'return_date' => optional($customRequest->return_date)->toDateString(),
            'notes' => trim((string) $customRequest->client_notes."\n".$customRequest->internal_notes),
        ]);

        if ($customRequest->status !== CustomReservationRequest::STATUS_CONVERTED) {
            $customRequest->update(['status' => CustomReservationRequest::STATUS_ACCEPTED]);
        }

        return Redirect::route('admin.reservations.create', ['custom_request_id' => $customRequest->id])
            ->withInput([
                'client_mode' => 'new',
                'client_first_name' => $firstName,
                'client_last_name' => $lastName,
                'client_phone' => $customRequest->client_phone,
                'client_email' => $customRequest->client_email,
                'notes' => trim((string) $customRequest->client_notes."\n".$customRequest->internal_notes),
            ])
            ->with('success', 'Donnees client preparees pour la reservation. Choisissez une offre puis finalisez le dossier.');
    }

    private function formView(string $view, CustomReservationRequest $customRequest, Request $request): View
    {
        $user = $request->user();

        return view($view, [
            'customRequest' => $customRequest,
            'agents' => $this->agents($user),
            'statusOptions' => CustomReservationRequest::statusOptions(),
            'priorityOptions' => CustomReservationRequest::priorityOptions(),
            'sourceOptions' => CustomReservationRequest::sourceOptions(),
            'serviceOptions' => CustomReservationRequest::serviceOptions(),
        ]);
    }

    private function agents(User $user)
    {
        $query = User::query()->canLogin()->orderBy('name');
        if (! ($user->isSuperAdmin() || $user->isSiegeAdmin()) && $user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query->get(['id', 'name', 'email', 'branch_id']);
    }

    private function payload(Request $request): array
    {
        $data = $request->validated();
        $data['whatsapp_same_as_phone'] = $request->boolean('whatsapp_same_as_phone');
        $data['flexible_dates'] = $request->boolean('flexible_dates');
        $data['client_whatsapp'] = $data['whatsapp_same_as_phone'] ? ($data['client_phone'] ?? null) : ($data['client_whatsapp'] ?? null);
        $data['preferred_channels'] = array_values($data['preferred_channels'] ?? []);
        $data['children'] = $this->cleanPeopleRows($data['children'] ?? []);
        $data['infants'] = $this->cleanPeopleRows($data['infants'] ?? []);
        $data['services'] = $this->cleanServices($data['services'] ?? []);

        return Arr::except($data, ['submit_action']);
    }

    private function cleanPeopleRows(array $rows): array
    {
        return collect($rows)
            ->filter(fn ($row) => is_array($row) && (filled($row['age'] ?? null) || filled($row['birth_date'] ?? null)))
            ->map(fn ($row) => Arr::only($row, ['age', 'birth_date']))
            ->values()
            ->all();
    }

    private function cleanServices(array $services): array
    {
        $allowed = array_keys(CustomReservationRequest::serviceOptions());

        return collect($services)
            ->filter(fn ($config, $key) => in_array($key, $allowed, true) && is_array($config) && (bool) ($config['enabled'] ?? false))
            ->map(function (array $config): array {
                unset($config['enabled']);
                return collect($config)
                    ->filter(fn ($value) => is_array($value) ? count(array_filter($value, fn ($v) => filled($v))) > 0 : filled($value))
                    ->all();
            })
            ->all();
    }

    private function authorizeVisible(Request $request, CustomReservationRequest $customRequest): void
    {
        abort_unless($request->user()?->can('reservations.view'), 403);
        abort_unless(CustomReservationRequest::query()->visibleTo($request->user())->whereKey($customRequest->getKey())->exists(), 403);
    }

    private function canTakeRequest(?User $user): bool
    {
        return $user !== null
            && (
                $user->canQuoteCustomRequests()
                || $user->can('custom_requests.view_all')
                || $user->can('reservations.update')
                || $user->isManager()
            );
    }
}
