<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\User;
use App\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct(
        protected BranchScopeService $branchScope
    ) {}

    public function index(Request $request): View
    {
        $query = Client::query()->with(['assignedTo', 'branch']);
        $this->branchScope->scopeClients($query, $request->user());

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhere('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('whatsapp_number', 'like', '%'.$search.'%')
                    ->orWhere('nationality', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhere('company_name', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        if ($request->filled('client_type')) {
            $query->where('client_type', $request->query('client_type'));
        }
        if ($request->filled('source')) {
            $query->where('source', $request->query('source'));
        }
        if ($request->filled('nationality')) {
            $query->where('nationality', 'like', '%'.$request->query('nationality').'%');
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->query('city').'%');
        }
        if ($request->filled('traveler_category')) {
            $query->where('traveler_category', $request->query('traveler_category'));
        }
        if ($request->filled('visa_required')) {
            $query->where('visa_required', $request->query('visa_required') === '1');
        }
        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->query('assigned_to'));
        }

        $sortField = $request->query('sort', 'created_at');
        $sortDir = strtolower($request->query('dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['client_code', 'full_name', 'client_type', 'status', 'email', 'nationality', 'city', 'created_at', 'last_contacted_at'];
        if (in_array($sortField, $allowedSort, true)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderByDesc('created_at');
        }

        $clients = $query->paginate((int) $request->query('per_page', 15))->withQueryString();
        $usersQuery = User::query()->where('is_active', true)->orderBy('name');
        $this->branchScope->scopeUsers($usersQuery, $request->user());
        $users = $usersQuery->get(['id', 'name']);

        return view('admin.customers.clients.index', compact('clients', 'users'));
    }

    public function create(Request $request): View
    {
        $client = new Client;
        $usersQuery = User::query()->where('is_active', true)->orderBy('name');
        $this->branchScope->scopeUsers($usersQuery, $request->user());
        $users = $usersQuery->get(['id', 'name']);

        return view('admin.customers.clients.create', compact('client', 'users'));
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['visa_required'] = $request->boolean('visa_required');
        $data['newsletter_opt_in'] = $request->boolean('newsletter_opt_in');
        $data['sms_opt_in'] = $request->boolean('sms_opt_in');
        $data['whatsapp_opt_in'] = $request->boolean('whatsapp_opt_in');
        $data['branch_id'] = $request->user()->branch_id;
        Client::create($data);

        return redirect()
            ->route('admin.customers.clients.index')
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Request $request, Client $client): View
    {
        $branchIds = $this->branchScope->visibleBranchIds($request->user());
        if ($branchIds !== null && ! in_array((int) $client->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé à ce client.');
        }
        $client->load(['assignedTo', 'createdBy', 'updatedBy']);

        return view('admin.customers.clients.show', compact('client'));
    }

    public function edit(Request $request, Client $client): View
    {
        $branchIds = $this->branchScope->visibleBranchIds($request->user());
        if ($branchIds !== null && ! in_array((int) $client->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé à ce client.');
        }
        $usersQuery = User::query()->where('is_active', true)->orderBy('name');
        $this->branchScope->scopeUsers($usersQuery, $request->user());
        $users = $usersQuery->get(['id', 'name']);

        return view('admin.customers.clients.edit', compact('client', 'users'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
        $branchIds = $this->branchScope->visibleBranchIds($request->user());
        if ($branchIds !== null && ! in_array((int) $client->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé à ce client.');
        }
        $data = $request->validated();
        $data['visa_required'] = $request->boolean('visa_required');
        $data['newsletter_opt_in'] = $request->boolean('newsletter_opt_in');
        $data['sms_opt_in'] = $request->boolean('sms_opt_in');
        $data['whatsapp_opt_in'] = $request->boolean('whatsapp_opt_in');
        $client->update($data);

        return redirect()
            ->route('admin.customers.clients.show', $client)
            ->with('success', 'Client mis à jour.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $branchIds = $this->branchScope->visibleBranchIds($request->user());
        if ($branchIds !== null && ! in_array((int) $client->branch_id, $branchIds, true)) {
            abort(403, 'Accès non autorisé à ce client.');
        }
        $client->delete();

        return redirect()
            ->route('admin.customers.clients.index')
            ->with('success', 'Client supprimé (corbeille).');
    }

    public function trashed(Request $request): View
    {
        $query = Client::query()->onlyTrashed()->with(['assignedTo']);
        $this->branchScope->scopeClients($query, $request->user());
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', '%'.$search.'%')
                    ->orWhere('full_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }
        $clients = $query->orderByDesc('deleted_at')->paginate(15)->withQueryString();
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.customers.clients.index', compact('clients', 'users'))->with('trashed', true);
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $query = Client::onlyTrashed()->where('id', $id);
        $this->branchScope->scopeClients($query, $request->user());
        $client = $query->firstOrFail();
        $client->restore();

        return redirect()
            ->route('admin.customers.clients.index')
            ->with('success', 'Client restauré.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse
    {
        $query = Client::onlyTrashed()->where('id', $id);
        $this->branchScope->scopeClients($query, $request->user());
        $client = $query->firstOrFail();
        $client->forceDelete();

        return redirect()
            ->route('admin.customers.clients.index')
            ->with('success', 'Client supprimé définitivement.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,block,vip,delete,restore,force_delete',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');
        if (is_string($ids)) {
            $ids = json_decode($ids, true) ?: [];
        }
        $ids = array_filter(array_map('intval', (array) $ids));

        $allowedQuery = Client::query();
        $this->branchScope->scopeClients($allowedQuery, $request->user());
        $allowedIds = $allowedQuery->pluck('id')->toArray();
        $ids = array_intersect($ids, $allowedIds);

        if ($action === 'restore') {
            Client::onlyTrashed()->whereIn('id', $ids)->restore();

            return redirect()->route('admin.customers.clients.index')->with('success', 'Clients restaurés.');
        }

        if ($action === 'force_delete') {
            Client::onlyTrashed()->whereIn('id', $ids)->forceDelete();

            return redirect()->route('admin.customers.clients.index')->with('success', 'Clients supprimés définitivement.');
        }

        if ($action === 'delete') {
            Client::whereIn('id', $ids)->delete();

            return redirect()->route('admin.customers.clients.index')->with('success', 'Clients supprimés (corbeille).');
        }

        Client::whereIn('id', $ids)->update([
            'status' => match ($action) {
                'activate' => 'active',
                'deactivate' => 'inactive',
                'block' => 'blocked',
                'vip' => 'vip',
                default => 'active',
            },
        ]);
        $message = match ($action) {
            'activate' => 'Statut mis à jour (actif).',
            'deactivate' => 'Statut mis à jour (inactif).',
            'block' => 'Statut mis à jour (bloqué).',
            'vip' => 'Statut mis à jour (VIP).',
            default => 'Action effectuée.',
        };

        return redirect()->route('admin.customers.clients.index')->with('success', $message);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', $request->query('search', '')));
        if (mb_strlen($q) < 2) {
            return response()->json([
                'q' => $q,
                'count' => 0,
                'items' => [],
            ]);
        }

        $query = Client::query();
        $this->branchScope->scopeClients($query, $request->user());

        $normalized = str_replace([' ', '-', '.', '/', '\\'], '', $q);
        $hasNormalized = mb_strlen($normalized) >= 2;

        $query->where(function ($qq) use ($q, $normalized, $hasNormalized) {
            $qq->where('client_code', 'like', '%'.$q.'%')
                ->orWhere('full_name', 'like', '%'.$q.'%')
                ->orWhere('first_name', 'like', '%'.$q.'%')
                ->orWhere('last_name', 'like', '%'.$q.'%')
                ->orWhere('email', 'like', '%'.$q.'%')
                ->orWhere('phone', 'like', '%'.$q.'%')
                ->orWhere('whatsapp_number', 'like', '%'.$q.'%')
                ->orWhere('national_id_number', 'like', '%'.$q.'%')
                ->orWhere('passport_number', 'like', '%'.$q.'%');

            if ($hasNormalized) {
                $qq->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%'])
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(whatsapp_number, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%'])
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(national_id_number, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%'])
                    ->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(passport_number, ' ', ''), '-', ''), '.', ''), '/', ''), '\\\\', '') LIKE ?", ['%'.$normalized.'%']);
            }
        });

        $items = $query
            ->orderByDesc('id')
            ->limit(15)
            ->get([
                'id',
                'client_code',
                'full_name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'whatsapp_number',
                'city',
                'national_id_number',
                'passport_number',
                'status',
            ])
            ->map(function (Client $c) {
                $doc = $c->national_id_number ?: ($c->passport_number ?: null);

                return [
                    'id' => $c->id,
                    'client_code' => $c->client_code,
                    'full_name' => $c->full_name ?: trim(($c->first_name ?? '').' '.($c->last_name ?? '')),
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'whatsapp_number' => $c->whatsapp_number,
                    'city' => $c->city,
                    'document' => $doc,
                    'status' => $c->status,
                ];
            })
            ->values();

        return response()->json([
            'q' => $q,
            'count' => $items->count(),
            'items' => $items,
        ]);
    }

    public function quickStore(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'phone' => ['required', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255', Rule::unique(Client::class, 'email')],
                'gender' => ['nullable', 'in:male,female'],
                'date_of_birth' => ['nullable', 'date'],
                'nationality' => ['nullable', 'string', 'max:100'],
                'national_id_number' => ['nullable', 'string', 'max:50'],
                'passport_number' => ['nullable', 'string', 'max:50'],
            ]);

            // Duplicate check by phone, email, or document numbers
            $dupQuery = Client::query();
            $this->branchScope->scopeClients($dupQuery, $request->user());
            $dupQuery->where(function ($q) use ($validated) {
                if (!empty($validated['phone'])) {
                    $q->orWhere('phone', $validated['phone']);
                }
                if (!empty($validated['email'])) {
                    $q->orWhere('email', $validated['email']);
                }
                if (!empty($validated['national_id_number'])) {
                    $q->orWhere('national_id_number', $validated['national_id_number']);
                }
                if (!empty($validated['passport_number'])) {
                    $q->orWhere('passport_number', $validated['passport_number']);
                }
            });

            $duplicate = $dupQuery->first([
                'id',
                'client_code',
                'full_name',
                'first_name',
                'last_name',
                'phone',
                'email',
            ]);

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'duplicate' => [
                        'id' => $duplicate->id,
                        'client_code' => $duplicate->client_code,
                        'full_name' => $duplicate->full_name ?: trim(($duplicate->first_name ?? '').' '.($duplicate->last_name ?? '')),
                        'phone' => $duplicate->phone,
                        'email' => $duplicate->email,
                    ],
                ]);
            }

            $data = array_merge($validated, [
                'client_type' => 'individual',
                'status' => 'active',
                'source' => 'admin',
                'branch_id' => $request->user()->branch_id,
            ]);

            $client = Client::create($data);

            return response()->json([
                'success' => true,
                'client' => [
                    'id' => $client->id,
                    'client_code' => $client->client_code,
                    'full_name' => $client->full_name ?: trim(($client->first_name ?? '').' '.($client->last_name ?? '')),
                    'phone' => $client->phone,
                    'email' => $client->email,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur : ' . $e->getMessage(),
            ], 500);
        }
    }
}
