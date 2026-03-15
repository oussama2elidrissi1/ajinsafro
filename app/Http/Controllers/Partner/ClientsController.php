<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientsController extends Controller
{
    private function getPartner(Request $request): \App\Models\Partner
    {
        return $request->user()->partner;
    }

    public function index(Request $request): View
    {
        $partner = $this->getPartner($request);
        $query = Client::where('partner_id', $partner->id)->with('partner');
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('client_code', 'like', '%' . $search . '%')
                    ->orWhere('full_name', 'like', '%' . $search . '%')
                    ->orWhere('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }
        $clients = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        return view('partner.clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('partner.clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = $this->getPartner($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'nationality' => ['nullable', 'string', 'max:100'],
        ]);
        $data['partner_id'] = $partner->id;
        $data['status'] = 'active';
        $data['source'] = 'partner';
        Client::create($data);
        return redirect()->route('partner.clients.index')->with('success', 'Client créé.');
    }

    public function show(Request $request, Client $client): View|RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($client->partner_id !== $partner->id) {
            abort(403);
        }
        return view('partner.clients.show', compact('client'));
    }

    public function edit(Request $request, Client $client): View|RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($client->partner_id !== $partner->id) {
            abort(403);
        }
        return view('partner.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($client->partner_id !== $partner->id) {
            abort(403);
        }
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'nationality' => ['nullable', 'string', 'max:100'],
        ]);
        $client->update($data);
        return redirect()->route('partner.clients.index')->with('success', 'Client mis à jour.');
    }

    public function destroy(Request $request, Client $client): RedirectResponse
    {
        $partner = $this->getPartner($request);
        if ($client->partner_id !== $partner->id) {
            abort(403);
        }
        $client->delete();
        return redirect()->route('partner.clients.index')->with('success', 'Client supprimé.');
    }
}
