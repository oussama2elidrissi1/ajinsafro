<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Models\CustomRequestDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomRequestDocumentController extends Controller
{
    public function store(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        abort_unless($request->user()?->can('custom_requests.documents'), 403);

        $data = $request->validate([
            'document_type' => ['required', Rule::in(['identity', 'payment_receipt', 'tickets', 'hotel_voucher', 'supplier_file', 'other'])],
            'title' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ]);

        $file = $data['document'];
        $path = $file->store('custom-requests/'.$customRequest->id.'/documents', 'public');

        $customRequest->documents()->create([
            'uploaded_by' => $request->user()->id,
            'document_type' => $data['document_type'],
            'title' => $data['title'] ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_auto_generated' => false,
        ]);

        return back()->with('success', 'Document ajouté.');
    }

    public function destroy(Request $request, CustomRequest $customRequest, CustomRequestDocument $document): RedirectResponse
    {
        abort_unless($request->user()?->can('custom_requests.documents'), 403);
        abort_unless((int) $document->custom_request_id === (int) $customRequest->id, 404);
        abort_if($document->is_auto_generated, 422, 'Un devis généré automatiquement ne peut pas être supprimé ici.');

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }
}
