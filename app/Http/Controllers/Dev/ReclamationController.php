<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\ClientNotification;
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

        $previousStatus = $reclamation->status;
        $previousResponse = (string) ($reclamation->dev_response ?? '');

        $reclamation->fill([
            'status' => $validated['status'],
            'dev_response' => $validated['dev_response'] ?? null,
            'handled_by' => $request->user()->id,
            'handled_at' => $validated['status'] === DevReclamation::STATUS_RESOLVED ? now() : $reclamation->handled_at,
        ])->save();

        $this->notifyRequesterWhenTreatmentChanges($reclamation, $previousStatus, $previousResponse);

        return redirect()
            ->route('admin.dev.reclamations.show', $reclamation)
            ->with('success', 'Reclamation mise a jour.');
    }

    private function authorizeDevAccess(Request $request): void
    {
        abort_unless((bool) $request->user()?->isDevAdmin(), 403);
    }

    private function notifyRequesterWhenTreatmentChanges(DevReclamation $reclamation, ?string $previousStatus, string $previousResponse): void
    {
        if (! $reclamation->user_id) {
            return;
        }

        $response = trim((string) ($reclamation->dev_response ?? ''));
        $statusChanged = $previousStatus !== $reclamation->status;
        $responseChanged = trim($previousResponse) !== $response;

        if (! $statusChanged && ! $responseChanged) {
            return;
        }

        $statusLabel = $reclamation->status_label;
        $message = $response !== ''
            ? 'Le dev a ajoute une reponse a votre reclamation : '.$reclamation->subject
            : 'Le statut de votre reclamation est passe a '.$statusLabel.' : '.$reclamation->subject;

        ClientNotification::query()->create([
            'user_id' => $reclamation->user_id,
            'type' => 'dev_reclamation_response',
            'title' => $response !== '' ? 'Reponse dev disponible' : 'Statut reclamation mis a jour',
            'message' => $message,
            'link' => route('support.reclamations.show', $reclamation),
            'is_read' => false,
        ]);
    }
}
