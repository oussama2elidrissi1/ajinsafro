<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        $query = Client::query()->with(['assignedTo']);

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', '%' . $search . '%')
                    ->orWhere('full_name', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('whatsapp_number', 'like', '%' . $search . '%')
                    ->orWhere('nationality', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('company_name', 'like', '%' . $search . '%');
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
            $query->where('nationality', 'like', '%' . $request->query('nationality') . '%');
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->query('city') . '%');
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
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('admin.customers.clients.index', compact('clients', 'users'));
    }

    public function create(): View
    {
        $client = new Client();
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.customers.clients.create', compact('client', 'users'));
    }

    public function store(StoreClientRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['visa_required'] = $request->boolean('visa_required');
        $data['newsletter_opt_in'] = $request->boolean('newsletter_opt_in');
        $data['sms_opt_in'] = $request->boolean('sms_opt_in');
        $data['whatsapp_opt_in'] = $request->boolean('whatsapp_opt_in');
        Client::create($data);

        return redirect()
            ->route('admin.customers.clients.index')
            ->with('success', 'Client créé avec succès.');
    }

    public function show(Client $client): View
    {
        $client->load(['assignedTo', 'createdBy', 'updatedBy']);
        return view('admin.customers.clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.customers.clients.edit', compact('client', 'users'));
    }

    public function update(UpdateClientRequest $request, Client $client): RedirectResponse
    {
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
        $client->delete();
        return redirect()
            ->route('admin.customers.clients.index')
            ->with('success', 'Client supprimé (corbeille).');
    }

    public function trashed(Request $request): View
    {
        $query = Client::query()->onlyTrashed()->with(['assignedTo']);
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', '%' . $search . '%')
                    ->orWhere('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }
        $clients = $query->orderByDesc('deleted_at')->paginate(15)->withQueryString();
        $users = User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('admin.customers.clients.index', compact('clients', 'users'))->with('trashed', true);
    }

    public function restore(int $id): RedirectResponse
    {
        $client = Client::onlyTrashed()->findOrFail($id);
        $client->restore();
        return redirect()
            ->route('admin.customers.clients.index')
            ->with('success', 'Client restauré.');
    }

    public function forceDelete(int $id): RedirectResponse
    {
        $client = Client::onlyTrashed()->findOrFail($id);
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
}
