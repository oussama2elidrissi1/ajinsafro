<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Services\CustomRequestNotificationService;
use App\Services\View\AgentPortalLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
            ->where(fn (Builder $builder) => $this->scopeOwnedByAgent($builder, (int) $user->id));

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

        return view('agent.custom-reservations.create', [
            'travelTypeOptions' => CustomRequest::travelTypeOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.create'), 403);

        $data = $request->validate([
            'customer_full_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'desired_destination' => ['required', 'string', 'max:255'],
            'departure_city' => ['required', 'string', 'max:255'],
            'desired_departure_date' => ['required', 'date'],
            'desired_return_date' => ['nullable', 'date', 'after_or_equal:desired_departure_date'],
            'travel_type' => ['required', 'in:'.implode(',', array_keys(CustomRequest::travelTypeOptions()))],
            'travelers_count' => ['required', 'integer', 'min:1', 'max:50'],
            'adults_count' => ['required', 'integer', 'min:1', 'max:50'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'babies_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'client_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $childrenCount = (int) ($data['children_count'] ?? 0);
        $babiesCount = (int) ($data['babies_count'] ?? 0);
        if ((int) $data['travelers_count'] < ((int) $data['adults_count'] + $childrenCount + $babiesCount)) {
            return back()->withErrors(['travelers_count' => 'Le nombre total de voyageurs doit être cohérent.'])->withInput();
        }

        $customRequest = CustomRequest::query()->create([
            'created_by' => $user->id,
            'customer_full_name' => $data['customer_full_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_type' => 'new_customer',
            'customer_notes' => $data['client_notes'] ?? null,
            'desired_destination' => $data['desired_destination'],
            'departure_city' => $data['departure_city'],
            'desired_departure_date' => $data['desired_departure_date'],
            'desired_return_date' => $data['desired_return_date'] ?? null,
            'travel_type' => $data['travel_type'],
            'travelers_count' => $data['travelers_count'],
            'adults_count' => $data['adults_count'],
            'children_count' => $childrenCount,
            'babies_count' => $babiesCount,
            'status' => CustomRequest::STATUS_NEW,
            'priority' => 'normal',
            'payment_status' => 'unpaid',
            'currency' => 'MAD',
            'paid_amount' => 0,
        ]);

        $customRequest->statusLogs()->create([
            'user_id' => $user->id,
            'old_status' => null,
            'new_status' => CustomRequest::STATUS_NEW,
            'note' => 'Demande créée depuis l’espace agent.',
        ]);

        $this->notifications->notifyNewRequest($customRequest);

        return redirect()
            ->route('agent.custom-reservations.index')
            ->with('success', 'Demande à la carte créée.');
    }

    public function show(Request $request, CustomRequest $customRequest): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('custom_requests.view'), 403);
        abort_unless($this->agentOwnsRequest($customRequest, (int) $user->id), 403);

        return view('agent.custom-reservations.show', [
            'customRequest' => $customRequest->load(['latestQuote.generatedDocument', 'documents', 'comments.user:id,name', 'statusLogs.user:id,name']),
            'travelTypeOptions' => CustomRequest::travelTypeOptions(),
        ]);
    }

    private function scopeOwnedByAgent(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId)
            ->orWhere('assigned_to', $userId);
    }

    private function agentOwnsRequest(CustomRequest $customRequest, int $userId): bool
    {
        return in_array($userId, array_filter([
            (int) ($customRequest->created_by ?? 0),
            (int) ($customRequest->assigned_to ?? 0),
        ]), true);
    }
}
