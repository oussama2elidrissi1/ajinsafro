<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AgentsController extends Controller
{
    public function index(Request $request): View
    {
        $partner = $request->user()->partner ?: $request->user()->ownedPartner;
        $agents = User::query()
            ->where('partner_id', $partner->id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'partner_agent'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('partner_v2.agents.index', compact('partner', 'agents'));
    }

    public function store(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner ?: $request->user()->ownedPartner;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique(User::class, 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('partner_agent', 'web');

        $agent = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'partner_id' => $partner->id,
            'created_by' => $request->user()->id,
            'user_type' => 'partner_agent',
            'base_role' => 'partner_agent',
            'is_admin' => false,
            'is_active' => true,
        ]);
        $agent->assignRole('partner_agent');

        return redirect()->route('partner.agents.index')->with('success', 'Agent partenaire cree.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->abortUnlessOwnAgent($request, $user);

        return view('partner_v2.agents.edit', ['agent' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->abortUnlessOwnAgent($request, $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique(User::class, 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'partner_id' => ($request->user()->partner ?: $request->user()->ownedPartner)?->id,
            'is_admin' => false,
            'user_type' => 'partner_agent',
            'base_role' => 'partner_agent',
        ]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        $user->syncRoles(['partner_agent']);

        return redirect()->route('partner.agents.index')->with('success', 'Agent partenaire mis a jour.');
    }

    public function disable(Request $request, User $user): RedirectResponse
    {
        $this->abortUnlessOwnAgent($request, $user);
        $user->forceFill(['is_active' => false])->save();

        return redirect()->route('partner.agents.index')->with('success', 'Agent partenaire desactive.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->abortUnlessOwnAgent($request, $user);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        return redirect()->route('partner.agents.edit', $user)->with('success', 'Mot de passe reinitialise.');
    }

    private function abortUnlessOwnAgent(Request $request, User $user): void
    {
        $partnerId = (int) (($request->user()->partner ?: $request->user()->ownedPartner)?->id ?? 0);
        if ((int) $user->partner_id !== $partnerId || ! $user->isPartnerAgent()) {
            abort(403);
        }
    }
}
