<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\DevReclamation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDevAccess($request);

        $status = $request->query('status');
        $q = trim((string) $request->query('q', ''));

        $reclamations = DevReclamation::query()
            ->with(['user', 'handler'])
            ->when($status && array_key_exists($status, DevReclamation::statuses()), fn ($query) => $query->where('status', $status))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('subject', 'like', "%{$q}%")
                        ->orWhere('message', 'like', "%{$q}%")
                        ->orWhereHas('user', function ($userQuery) use ($q) {
                            $userQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'all' => DevReclamation::count(),
            DevReclamation::STATUS_OPEN => DevReclamation::where('status', DevReclamation::STATUS_OPEN)->count(),
            DevReclamation::STATUS_IN_PROGRESS => DevReclamation::where('status', DevReclamation::STATUS_IN_PROGRESS)->count(),
            DevReclamation::STATUS_RESOLVED => DevReclamation::where('status', DevReclamation::STATUS_RESOLVED)->count(),
        ];

        return view('dev.reclamations.index', compact('reclamations', 'counts', 'status', 'q'));
    }

    public function show(Request $request, DevReclamation $reclamation): View
    {
        $this->authorizeDevAccess($request);

        $reclamation->load(['user', 'handler']);

        return view('dev.reclamations.show', compact('reclamation'));
    }

    public function update(Request $request, DevReclamation $reclamation): RedirectResponse
    {
        $this->authorizeDevAccess($request);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ouverte,en_cours,traitee'],
            'dev_response' => ['nullable', 'string', 'max:5000'],
        ]);

        $reclamation->fill([
            'status' => $validated['status'],
            'dev_response' => $validated['dev_response'] ?? null,
            'handled_by' => $request->user()->id,
            'handled_at' => $validated['status'] === DevReclamation::STATUS_RESOLVED ? now() : $reclamation->handled_at,
        ])->save();

        return redirect()
            ->route('dev.reclamations.show', $reclamation)
            ->with('success', 'Reclamation mise a jour.');
    }

    private function authorizeDevAccess(Request $request): void
    {
        $user = $request->user();

        abort_unless($user && (
            ($user->is_admin ?? false)
            || (method_exists($user, 'canAccessAdmin') && $user->canAccessAdmin())
            || (method_exists($user, 'hasRole') && $user->hasRole(['Dev', 'Developer', 'Developpeur', 'Super Admin', 'Admin']))
        ), 403);
    }
}
