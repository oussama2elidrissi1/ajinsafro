<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileAgencyController extends Controller
{
    public function edit(Request $request): View
    {
        return view('partner_v2.profile-agency.edit', [
            'partner' => $request->user()->partner ?: $request->user()->ownedPartner,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner ?: $request->user()->ownedPartner;
        $data = $request->validate([
            'raison_sociale' => ['required', 'string', 'max:190'],
            'nom_commercial' => ['nullable', 'string', 'max:190'],
            'nom_responsable' => ['required', 'string', 'max:190'],
            'email' => [
                'required',
                'email',
                'max:190',
                Rule::unique(Partner::class, 'email')->ignore($partner->id),
                Rule::unique(User::class, 'email')->ignore($request->user()->id),
            ],
            'telephone' => ['required', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'ville' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($request->hasFile('logo')) {
            if ($partner->logo_path) {
                Storage::disk('public')->delete($partner->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('partner-logos', 'public');
        }

        $partner->update([
            'name' => $data['raison_sociale'],
            'raison_sociale' => $data['raison_sociale'],
            'nom_commercial' => $data['nom_commercial'] ?? null,
            'nom_responsable' => $data['nom_responsable'],
            'responsable_name' => $data['nom_responsable'],
            'email' => $data['email'],
            'telephone' => $data['telephone'],
            'phone' => $data['telephone'],
            'adresse' => $data['adresse'] ?? null,
            'address' => $data['adresse'] ?? null,
            'ville' => $data['ville'] ?? null,
            'city' => $data['ville'] ?? null,
            'logo_path' => $data['logo_path'] ?? $partner->logo_path,
        ]);

        $request->user()->forceFill([
            'name' => $data['nom_responsable'],
            'email' => $data['email'],
            'phone' => $data['telephone'],
        ])->save();

        return redirect()->route('partner.profile-agency.edit')->with('success', 'Profil agence mis a jour.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'current_password' => ['required', 'string', 'max:200'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $user->getAuthPassword())) {
            return back()->withErrors(['current_password' => 'Mot de passe actuel incorrect.']);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
        ])->save();

        return redirect()->route('partner.profile.show')->with('success', 'Mot de passe mis a jour.');
    }
}
