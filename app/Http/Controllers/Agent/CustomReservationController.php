<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\CustomReservationRequest;
use App\Services\View\AgentPortalLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomReservationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);

        $filters = [
            'client' => trim((string) $request->query('client', '')),
            'destination' => trim((string) $request->query('destination', '')),
            'status' => trim((string) $request->query('status', '')),
            'date' => trim((string) $request->query('date', '')),
        ];

        $query = CustomReservationRequest::query()
            ->where(fn (Builder $builder) => $this->scopeOwnedByAgent($builder, (int) $user->id));

        if ($filters['client'] !== '') {
            $like = '%'.$filters['client'].'%';
            $query->where(function (Builder $builder) use ($like): void {
                $builder->where('reference', 'like', $like)
                    ->orWhere('client_name', 'like', $like)
                    ->orWhere('client_phone', 'like', $like)
                    ->orWhere('client_email', 'like', $like);
            });
        }

        if ($filters['destination'] !== '') {
            $query->where('destination_text', 'like', '%'.$filters['destination'].'%');
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['date'] !== '') {
            $query->whereDate('departure_date', $filters['date']);
        }

        return view('agent.custom-reservations.index', [
            'requests' => $query->latest()->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statusOptions' => CustomReservationRequest::statusOptions(),
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);

        return view('agent.custom-reservations.create', [
            'statusOptions' => CustomReservationRequest::statusOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);

        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_phone' => ['required', 'string', 'max:50'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'destination_text' => ['nullable', 'string', 'max:255'],
            'departure_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'adults' => ['required', 'integer', 'min:1', 'max:50'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:50'],
            'client_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $childrenCount = (int) ($data['children_count'] ?? 0);
        unset($data['children_count']);

        $data['children'] = $childrenCount > 0 ? array_fill(0, $childrenCount, ['age' => null]) : [];
        $data['infants'] = [];
        $data['status'] = CustomReservationRequest::STATUS_NEW;
        $data['priority'] = CustomReservationRequest::PRIORITY_NORMAL;
        $data['source'] = 'agency';
        $data['client_type'] = 'particular';
        $data['currency'] = 'MAD';
        $data['created_by'] = $user->id;
        $data['whatsapp_same_as_phone'] = true;
        $data['client_whatsapp'] = $data['client_phone'];
        $data['flexible_dates'] = false;

        CustomReservationRequest::query()->create($data);

        return redirect()
            ->route('agent.custom-reservations.index')
            ->with('success', 'Demande à la carte créée.');
    }

    public function show(Request $request, CustomReservationRequest $customRequest): View
    {
        $user = $request->user();
        abort_unless($user && AgentPortalLayout::shouldUse($user) && $user->can('reservations.view'), 403);
        abort_unless($this->agentOwnsRequest($customRequest, (int) $user->id), 403);

        return view('agent.custom-reservations.show', [
            'customRequest' => $customRequest,
        ]);
    }

    private function scopeOwnedByAgent(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId)
            ->orWhere('assigned_to', $userId);
    }

    private function agentOwnsRequest(CustomReservationRequest $customRequest, int $userId): bool
    {
        return in_array($userId, array_filter([
            (int) ($customRequest->created_by ?? 0),
            (int) ($customRequest->assigned_to ?? 0),
        ]), true);
    }
}
