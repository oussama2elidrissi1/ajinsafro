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
            $user = User::create([
                'name' => $data['nom_responsable'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['telephone'],
                'is_admin' => false,
                'is_active' => true,
            ]);
            $user->assignRole('Partenaire');

            Partner::create([
                'user_id' => $user->id,
                'raison_sociale' => $data['raison_sociale'],
                'nom_commercial' => $data['nom_commercial'] ?? null,
                'nom_responsable' => $data['nom_responsable'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'adresse' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'code_postal' => $data['code_postal'] ?? null,
                'pays' => $data['pays'] ?? null,
                'ice' => $data['ice'] ?? null,
                'if' => $data['if'] ?? null,
                'rc' => $data['rc'] ?? null,
                'document_path' => $documentPath,
                'status' => Partner::STATUS_PENDING,
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()->route('partner.registration.success');
    }
}
