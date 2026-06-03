<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PartnerAccountValidatedMail;
use App\Models\Partner;
use App\Models\PartnerWalletTransaction;
use App\Models\User;
use App\Models\Voyage;
use App\Services\AdminWpTourCatalogQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PartnerAccountController extends Controller
{
    public function index(Request $request): View
    {
        $query = Partner::query()
            ->with(['user:id,name,email', 'validatedByUser:id,name'])
            ->withCount([
                'agents as partner_admins_count' => fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->whereIn('name', ['Partenaire', 'partner_admin'])),
            ]);
        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('raison_sociale', 'like', '%' . $search . '%')
                    ->orWhere('nom_commercial', 'like', '%' . $search . '%')
                    ->orWhere('nom_responsable', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('telephone', 'like', '%' . $search . '%');
            });
        }
        $partners = $query->orderByDesc('created_at')->paginate(15)->withQueryString();
        return view('admin.partner-accounts.index', compact('partners'));
    }

    public function show(Partner $partner): View
    {
        $partner->load(['user', 'validatedByUser', 'voyageAccess']);
        $partnerAdmins = $this->partnerAdminUsers($partner)->get();
        $agentsCount = $partner->agents()->whereHas('roles', fn ($query) => $query->where('name', 'partner_agent'))->count();
        $reservationsCount = $partner->reservations()->count();
        $walletPendingCount = $partner->walletTransactions()->where('status', PartnerWalletTransaction::STATUS_PENDING)->count();
        // Only show the same reservable voyages as the Circuits/Voyages admin module.
        $voyages = AdminWpTourCatalogQuery::reservableVoyages();
        return view('admin.partner-accounts.show', compact('partner', 'voyages', 'partnerAdmins', 'agentsCount', 'reservationsCount', 'walletPendingCount'));
    }

    public function create(): View
    {
        return view('admin.partner-accounts.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'raison_sociale' => ['required', 'string', 'max:190'],
            'nom_responsable' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique(Partner::class, 'email')],
            'telephone' => ['required', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'ville' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'status' => ['nullable', Rule::in([Partner::STATUS_PENDING, Partner::STATUS_VALIDATED, Partner::STATUS_SUSPENDED])],
            'admin_name' => ['required', 'string', 'max:190'],
            'admin_email' => ['required', 'email', 'max:190', Rule::unique(User::class, 'email')],
            'admin_phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $partner = DB::transaction(function () use ($request, $data): Partner {
            $this->ensurePartnerRoles();

            $adminUser = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'phone' => $data['admin_phone'] ?? null,
                'password' => Hash::make($data['password']),
                'created_by' => $request->user()->id,
                'user_type' => 'partner',
                'base_role' => 'partner_admin',
                'is_admin' => false,
                'is_active' => true,
            ]);
            $adminUser->assignRole(['Partenaire', 'partner_admin']);

            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('partner-logos', 'public');
            }

            $status = $data['status'] ?? Partner::STATUS_VALIDATED;
            $partner = Partner::create([
                'user_id' => $adminUser->id,
                'created_by' => $request->user()->id,
                'name' => $data['raison_sociale'],
                'raison_sociale' => $data['raison_sociale'],
                'nom_commercial' => $data['raison_sociale'],
                'nom_responsable' => $data['nom_responsable'],
                'responsable_name' => $data['nom_responsable'],
                'email' => $data['email'],
                'telephone' => $data['telephone'],
                'phone' => $data['telephone'],
                'adresse' => $data['adresse'] ?? null,
                'address' => $data['adresse'] ?? null,
                'ville' => $data['ville'] ?? null,
                'city' => $data['ville'] ?? null,
                'logo_path' => $logoPath,
                'status' => $status,
                'wallet_balance' => 0,
                'validated_at' => $status === Partner::STATUS_VALIDATED ? now() : null,
                'validated_by' => $status === Partner::STATUS_VALIDATED ? $request->user()->id : null,
            ]);

            $adminUser->forceFill(['partner_id' => $partner->id])->save();

            return $partner;
        });

        return redirect()->route('admin.partners.show', $partner)->with('success', 'Partenaire et admin partenaire crees.');
    }

    public function createAdmin(Partner $partner): View
    {
        return view('admin.partner-accounts.admin-create', compact('partner'));
    }

    public function storeAdmin(Request $request, Partner $partner): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', Rule::unique(User::class, 'email')],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::transaction(function () use ($request, $partner, $data): void {
            $this->ensurePartnerRoles();

            $adminUser = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'partner_id' => $partner->id,
                'created_by' => $request->user()->id,
                'user_type' => 'partner',
                'base_role' => 'partner_admin',
                'is_admin' => false,
                'is_active' => true,
            ]);
            $adminUser->assignRole(['Partenaire', 'partner_admin']);

            if (! $partner->user_id) {
                $partner->forceFill(['user_id' => $adminUser->id])->save();
            }
        });

        return redirect()->route('admin.partners.show', $partner)->with('success', 'Admin partenaire cree.');
    }

    public function updateVoyageAccess(Request $request, Partner $partner): RedirectResponse
    {
        $voyageIds = $request->validate(['voyage_ids' => ['nullable', 'array'], 'voyage_ids.*' => ['integer', 'exists:voyages,id']])['voyage_ids'] ?? [];
        $partner->voyageAccess()->sync($voyageIds);
        return redirect()->route('admin.partner-accounts.show', $partner)->with('success', 'Accès voyages mis à jour.');
    }

    public function validatePartner(Request $request, Partner $partner): RedirectResponse
    {
        if (!$partner->isPending()) {
            return redirect()->route('admin.partner-accounts.show', $partner)
                ->with('error', 'Ce partenaire n’est pas en attente de validation.');
        }
        $partner->update([
            'status' => Partner::STATUS_VALIDATED,
            'validated_at' => now(),
            'validated_by' => $request->user()->id,
            'rejected_at' => null,
            'rejected_reason' => null,
        ]);
        $partner->loadMissing('user');
        if ($partner->user) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            Role::findOrCreate('Partenaire', 'web');
            Role::findOrCreate('partner_admin', 'web');
            $partner->user->forceFill([
                'partner_id' => $partner->id,
                'user_type' => 'partner',
                'base_role' => 'partner_admin',
                'is_admin' => false,
                'is_active' => true,
            ])->save();
            $partner->user->syncRoles(['Partenaire', 'partner_admin']);
        }
        try {
            if ($partner->user?->email) {
                Mail::to($partner->user->email)->send(new PartnerAccountValidatedMail($partner));
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return redirect()->route('admin.partner-accounts.index')
            ->with('success', 'Compte partenaire validé. Un email a été envoyé au partenaire.');
    }

    public function rejectPartner(Request $request, Partner $partner): RedirectResponse
    {
        $reason = $request->validate(['rejected_reason' => ['nullable', 'string', 'max:500']])['rejected_reason'] ?? null;
        if (!$partner->isPending()) {
            return redirect()->route('admin.partner-accounts.show', $partner)
                ->with('error', 'Ce partenaire n’est pas en attente de validation.');
        }
        $partner->update([
            'status' => Partner::STATUS_REJECTED,
            'rejected_at' => now(),
            'rejected_reason' => $reason,
            'validated_at' => null,
            'validated_by' => null,
        ]);
        return redirect()->route('admin.partner-accounts.index')
            ->with('success', 'Demande partenaire refusée.');
    }

    public function suspendPartner(Partner $partner): RedirectResponse
    {
        if (! $partner->isValidated()) {
            return redirect()->route('admin.partner-accounts.show', $partner)
                ->with('error', 'Seuls les partenaires validés peuvent être désactivés.');
        }

        $partner->update([
            'status' => Partner::STATUS_SUSPENDED,
        ]);

        return redirect()->route('admin.partner-accounts.show', $partner)
            ->with('success', 'Partenaire désactivé.');
    }

    public function activatePartner(Partner $partner): RedirectResponse
    {
        if (! $partner->isSuspended()) {
            return redirect()->route('admin.partner-accounts.show', $partner)
                ->with('error', 'Ce partenaire n’est pas désactivé.');
        }

        $partner->update([
            'status' => Partner::STATUS_VALIDATED,
        ]);

        return redirect()->route('admin.partner-accounts.show', $partner)
            ->with('success', 'Partenaire activé.');
    }

    public function sendPasswordReset(Partner $partner): RedirectResponse
    {
        $partner->loadMissing('user');
        $user = $partner->user ?: $this->partnerAdminUsers($partner)->first();
        if (! $user) {
            return redirect()->route('admin.partner-accounts.show', $partner)
                ->with('error', 'Compte utilisateur partenaire introuvable.');
        }

        $temporaryPassword = 'Aj-' . Str::random(10) . random_int(10, 99);
        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'is_active' => true,
        ])->save();

        return redirect()->route('admin.partner-accounts.show', $partner)
            ->with('success', 'Mot de passe partenaire reinitialise.')
            ->with('temporary_partner_password', $temporaryPassword)
            ->with('temporary_partner_user_name', $user->name)
            ->with('temporary_partner_user_email', $user->email);
    }

    public function agents(Partner $partner): View
    {
        $agents = User::query()
            ->where('partner_id', $partner->id)
            ->whereHas('roles', fn ($query) => $query->where('name', 'partner_agent'))
            ->with('roles')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.partner-accounts.agents', compact('partner', 'agents'));
    }

    public function reservations(Partner $partner): View
    {
        $reservations = $partner->reservations()
            ->with(['offer:id,name', 'creator:id,name,email', 'createdBy:id,name,email', 'agent:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.partner-accounts.reservations', compact('partner', 'reservations'));
    }

    public function wallet(Partner $partner): View
    {
        $transactions = $partner->walletTransactions()
            ->with(['requester:id,name,email', 'validator:id,name,email', 'reservation:id,dossier_number'])
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.partner-accounts.wallet', compact('partner', 'transactions'));
    }

    public function walletRequests(Request $request): View
    {
        $query = PartnerWalletTransaction::query()
            ->where('type', PartnerWalletTransaction::TYPE_RECHARGE)
            ->with(['partner:id,raison_sociale,nom_commercial,name', 'requester:id,name,email', 'validator:id,name,email']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $transactions = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('admin.partner-accounts.wallet-requests', compact('transactions'));
    }

    public function approveWalletRequest(Request $request, PartnerWalletTransaction $transaction): RedirectResponse
    {
        if (! $transaction->isPending() || $transaction->type !== PartnerWalletTransaction::TYPE_RECHARGE) {
            return back()->with('error', 'Cette demande ne peut pas etre validee.');
        }

        DB::transaction(function () use ($request, $transaction): void {
            $transaction = PartnerWalletTransaction::query()->lockForUpdate()->findOrFail($transaction->id);
            if (! $transaction->isPending()) {
                return;
            }

            $partner = Partner::query()->lockForUpdate()->findOrFail($transaction->partner_id);
            $before = (float) ($partner->wallet_balance ?? 0);
            $after = $before + (float) $transaction->amount;

            $partner->forceFill(['wallet_balance' => $after])->save();
            $transaction->forceFill([
                'status' => PartnerWalletTransaction::STATUS_APPROVED,
                'validated_by' => $request->user()->id,
                'validated_at' => now(),
                'balance_before' => $before,
                'balance_after' => $after,
            ])->save();
        });

        return back()->with('success', 'Recharge wallet validee.');
    }

    public function rejectWalletRequest(Request $request, PartnerWalletTransaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! $transaction->isPending() || $transaction->type !== PartnerWalletTransaction::TYPE_RECHARGE) {
            return back()->with('error', 'Cette demande ne peut pas etre refusee.');
        }

        $transaction->forceFill([
            'status' => PartnerWalletTransaction::STATUS_REJECTED,
            'admin_note' => $data['admin_note'] ?? null,
            'validated_by' => $request->user()->id,
            'validated_at' => now(),
        ])->save();

        return back()->with('success', 'Recharge wallet refusee.');
    }

    private function ensurePartnerRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('Partenaire', 'web');
        Role::findOrCreate('partner_admin', 'web');
        Role::findOrCreate('partner_agent', 'web');
    }

    private function partnerAdminUsers(Partner $partner)
    {
        return User::query()
            ->where(function ($query) use ($partner) {
                $query->where('partner_id', $partner->id);
                if ($partner->user_id) {
                    $query->orWhere('id', $partner->user_id);
                }
            })
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Partenaire', 'partner_admin']))
            ->orderBy('name');
    }
}
