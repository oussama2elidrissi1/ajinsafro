<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PartnerAccountValidatedMail;
use App\Models\Partner;
use App\Models\Voyage;
use App\Services\AdminWpTourCatalogQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PartnerAccountController extends Controller
{
    public function index(Request $request): View
    {
        $query = Partner::query()->with(['user:id,name,email', 'validatedByUser:id,name']);
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
        // Only show the same reservable voyages as the Circuits/Voyages admin module.
        $voyages = AdminWpTourCatalogQuery::reservableVoyages()->map(function (Voyage $voyage) {
            return $voyage->only(['id', 'name', 'wp_post_id', 'status']);
        });
        return view('admin.partner-accounts.show', compact('partner', 'voyages'));
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
        try {
            Mail::to($partner->user->email)->send(new PartnerAccountValidatedMail($partner));
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
        $email = $partner->user?->email ?: $partner->email;
        if (! $email) {
            return redirect()->route('admin.partner-accounts.show', $partner)
                ->with('error', 'Email partenaire introuvable.');
        }

        $status = Password::broker()->sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return redirect()->route('admin.partner-accounts.show', $partner)
                ->with('error', 'Impossible d’envoyer le lien de réinitialisation.');
        }

        return redirect()->route('admin.partner-accounts.show', $partner)
            ->with('success', 'Lien de réinitialisation du mot de passe envoyé au partenaire.');
    }
}
