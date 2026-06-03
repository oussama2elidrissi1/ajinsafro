<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartnerRegistrationRequest;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PartnerRegistrationController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function showRegistrationForm(): View
    {
        return view('auth.partner-register');
    }

    public function store(StorePartnerRegistrationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('partner-documents', 'public');
        }

        DB::beginTransaction();
        try {
            // Ensure Spatie role exists (some envs may not have seeded roles yet)
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            Role::findOrCreate('Partenaire', 'web');
            Role::findOrCreate('partner_admin', 'web');

            $user = User::create([
                'name' => $data['nom_responsable'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['telephone'],
                'is_admin' => false,
                'is_active' => true,
                'user_type' => 'partner',
                'base_role' => 'partner_admin',
            ]);
            $user->assignRole(['Partenaire', 'partner_admin']);

            $partner = Partner::create([
                'user_id' => $user->id,
                'created_by' => $user->id,
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
                'code_postal' => $data['code_postal'] ?? null,
                'pays' => $data['pays'] ?? null,
                'ice' => $data['ice'] ?? null,
                'if' => $data['if'] ?? null,
                'rc' => $data['rc'] ?? null,
                'document_path' => $documentPath,
                'status' => Partner::STATUS_PENDING,
            ]);

            $user->forceFill(['partner_id' => $partner->id])->save();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('partner.registration.success');
    }
}
