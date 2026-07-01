<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\DevReclamation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReclamationController extends Controller
{
    public function index(Request $request): View
    {
        $reclamations = DevReclamation::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(12);

        return view('support.reclamations.index', compact('reclamations'));
    }

    public function show(Request $request, DevReclamation $reclamation): View
    {
        abort_unless((int) $reclamation->user_id === (int) $request->user()->id, 403);

        return view('support.reclamations.show', compact('reclamation'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:160'],
            'message' => ['required', 'string', 'min:5', 'max:5000'],
            'page_url' => ['nullable', 'string', 'max:1000'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('dev-reclamations', 'public');
        }

        $reclamation = DevReclamation::create([
            'user_id' => $request->user()->id,
            'subject' => $validated['subject'] ?? null,
            'message' => $validated['message'],
            'page_url' => $validated['page_url'] ?? url()->previous(),
            'attachment_path' => $attachmentPath,
            'status' => DevReclamation::STATUS_OPEN,
        ]);

        return redirect()
            ->route('support.reclamations.show', $reclamation)
            ->with('success', 'Votre reclamation a ete envoyee au dev.');
    }
}
