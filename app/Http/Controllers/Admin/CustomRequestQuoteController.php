<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Services\CustomRequestNotificationService;
use App\Services\CustomRequestQuotationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomRequestQuoteController extends Controller
{
    public function __construct(
        private readonly CustomRequestNotificationService $notifications,
        private readonly CustomRequestQuotationService $quotationService
    ) {}

    public function quote(Request $request, CustomRequest $customRequest): View
    {
        $this->authorizeQuote($request, $customRequest);

        $quote = $this->currentEditableQuote($customRequest, $request);
        $this->quotationService->ensureProgramDays($quote);

        return view('admin.custom-requests.quote', [
            'customRequest' => $customRequest->load(['creator:id,name', 'assignedAgent:id,name', 'services', 'documents', 'comments.user:id,name', 'statusLogs.user:id,name']),
            'quote' => $quote->load('items', 'days.services', 'generatedDocument', 'offlineAgent:id,name'),
            'serviceTypeOptions' => CustomRequestQuote::itemServiceOptions(),
            'quoteStatusOptions' => CustomRequestQuote::statusOptions(),
            'quoteLayout' => $this->isAgentRoute($request) ? 'layouts.master-ajinsafro' : 'layouts.admin-v6',
            'quoteRoutes' => $this->quoteRoutes($request),
        ]);
    }

    public function store(Request $request, CustomRequest $customRequest): RedirectResponse
    {
        $this->authorizeQuote($request, $customRequest);
        $quote = $this->currentEditableQuote($customRequest, $request);
        $this->saveQuotePayload($request, $quote);
        $this->quotationService->ensureProgramDays($quote);

        return redirect()->route($this->quoteRoute($request), $customRequest)->with('success', 'Brouillon de cotation enregistré.');
    }

    public function update(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): RedirectResponse
    {
        $this->authorizeQuote($request, $customRequest);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);
        $this->saveQuotePayload($request, $quote);

        return redirect()->route($this->quoteRoute($request), $customRequest)->with('success', 'Cotation mise à jour.');
    }

    public function prepare(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): RedirectResponse
    {
        $this->authorizeQuote($request, $customRequest);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);

        if ($request->has('items') || $request->has('days')) {
            $this->saveQuotePayload($request, $quote);
        }

        abort_if($quote->items()->count() === 0, 422, 'Au moins un service est nécessaire pour générer le devis.');

        DB::transaction(function () use ($request, $customRequest, $quote): void {
            $quote->calculateTotals();
            $path = $quote->generatePdf();
            $pricePath = $quote->generatePricePdf();
            $quote->markAsPrepared();

            $customRequest->documents()->updateOrCreate(
                ['quote_id' => $quote->id, 'document_type' => 'quote'],
                [
                    'uploaded_by' => $request->user()->id,
                    'title' => 'Devis '.$quote->quote_number.' v'.$quote->version,
                    'file_path' => $path,
                    'original_name' => basename($path),
                    'mime_type' => 'application/pdf',
                    'size' => Storage::disk('public')->size($path),
                    'is_auto_generated' => true,
                ]
            );

            $customRequest->documents()->updateOrCreate(
                ['quote_id' => $quote->id, 'document_type' => 'supplier_file', 'title' => 'Fiche prix interne '.$quote->quote_number.' v'.$quote->version],
                [
                    'uploaded_by' => $request->user()->id,
                    'file_path' => $pricePath,
                    'original_name' => basename($pricePath),
                    'mime_type' => 'application/pdf',
                    'size' => Storage::disk('public')->size($pricePath),
                    'is_auto_generated' => true,
                ]
            );

            $customRequest->forceFill([
                'estimated_price' => $quote->total_sale,
                'requested_deposit' => $quote->requested_deposit,
                'paid_amount' => $quote->paid_amount,
                'remaining_amount' => $quote->remaining_amount,
                'currency' => $quote->currency,
            ])->save();

            $customRequest->changeStatus(CustomRequest::STATUS_QUOTE_PREPARED, $request->user()->id, 'Devis généré automatiquement.');
        });

        return redirect()->route($this->quoteRoute($request), $customRequest)->with('success', 'Devis généré automatiquement.');
    }

    public function send(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): RedirectResponse
    {
        $this->authorizeQuote($request, $customRequest);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);
        abort_unless($quote->pdf_path && Storage::disk('public')->exists($quote->pdf_path), 422, 'Le PDF doit être généré avant l’envoi.');

        DB::transaction(function () use ($request, $customRequest, $quote): void {
            $quote->markAsSent();
            $customRequest->changeStatus(CustomRequest::STATUS_QUOTE_SENT, $request->user()->id, 'Devis envoyé à l’agent créateur.');
        });

        $this->notifications->notifyQuoteSent($customRequest->fresh(['creator']));

        return back()->with('success', 'Devis envoyé à l’agent créateur.');
    }

    public function sendPriceSheet(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): RedirectResponse
    {
        $this->authorizeQuote($request, $customRequest);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);

        if (! $quote->price_pdf_path || ! Storage::disk('public')->exists($quote->price_pdf_path)) {
            $quote->generatePricePdf();
            $quote->refresh();
        }

        $quote->forceFill(['price_sent_at' => now()])->save();
        $this->notifications->notifyPriceSheetSent($customRequest->fresh(['creator']));

        return back()->with('success', 'Fiche prix envoyée à l’agent créateur.');
    }

    public function download(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): StreamedResponse
    {
        abort_unless($request->user()?->can('custom_requests.documents') || $request->user()?->can('custom_requests.view'), 403);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);
        abort_unless($quote->pdf_path && Storage::disk('public')->exists($quote->pdf_path), 404);

        return Storage::disk('public')->download($quote->pdf_path, basename($quote->pdf_path));
    }

    public function downloadPriceSheet(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): StreamedResponse
    {
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);
        $user = $request->user();
        $canDownloadInternalPrice = $customRequest->canBeQuotedBy($user)
            || $user?->can('custom_requests.view_all')
            || ($quote->price_sent_at && (int) $customRequest->created_by === (int) $user?->id);
        abort_unless($canDownloadInternalPrice, 403);

        if (! $quote->price_pdf_path || ! Storage::disk('public')->exists($quote->price_pdf_path)) {
            $quote->generatePricePdf();
            $quote->refresh();
        }

        return Storage::disk('public')->download($quote->price_pdf_path, basename($quote->price_pdf_path));
    }

    private function saveQuotePayload(Request $request, CustomRequestQuote $quote): void
    {
        $serviceTypes = array_keys(CustomRequestQuote::itemServiceOptions());

        $data = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'valid_until' => ['nullable', 'date'],
            'response_deadline' => ['nullable', 'date'],
            'currency' => ['required', Rule::in(['MAD', 'EUR', 'USD'])],
            'requested_deposit' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_conditions' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],

            'items' => ['nullable', 'array'],
            'items.*.service_type' => ['required_with:items', Rule::in($serviceTypes)],
            'items.*.title' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.supplier_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.margin_type' => ['nullable', Rule::in(['amount', 'percent'])],
            'items.*.margin_value' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_margin' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_sale_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.is_optional' => ['nullable', 'boolean'],
            'items.*.data_json' => ['nullable', 'array'],

            'days' => ['nullable', 'array'],
            'days.*.id' => ['nullable', 'integer'],
            'days.*.day_number' => ['required_with:days', 'integer', 'min:1'],
            'days.*.date' => ['nullable', 'date'],
            'days.*.title' => ['nullable', 'string', 'max:255'],
            'days.*.city' => ['nullable', 'string', 'max:255'],
            'days.*.client_description' => ['nullable', 'string'],
            'days.*.internal_notes' => ['nullable', 'string'],
            'days.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'days.*.services' => ['nullable', 'array'],
            'days.*.services.*.service_type' => ['required_with:days.*.services', Rule::in($serviceTypes)],
            'days.*.services.*.title' => ['nullable', 'string', 'max:255'],
            'days.*.services.*.description' => ['nullable', 'string'],
            'days.*.services.*.supplier_name' => ['nullable', 'string', 'max:255'],
            'days.*.services.*.quantity' => ['required_with:days.*.services', 'integer', 'min:1'],
            'days.*.services.*.unit_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'days.*.services.*.margin_type' => ['nullable', Rule::in(['amount', 'percent'])],
            'days.*.services.*.margin_value' => ['nullable', 'numeric', 'min:0'],
            'days.*.services.*.unit_margin' => ['nullable', 'numeric', 'min:0'],
            'days.*.services.*.unit_sale_price' => ['nullable', 'numeric', 'min:0'],
            'days.*.services.*.is_optional' => ['nullable', 'boolean'],
            'days.*.services.*.data_json' => ['nullable', 'array'],
        ]);

        $this->quotationService->save($quote, $data, $request->user());
    }

    private function currentEditableQuote(CustomRequest $customRequest, Request $request): CustomRequestQuote
    {
        $latest = $customRequest->quotes()->latest('version')->first();

        if (! $latest) {
            return $customRequest->quotes()->create([
                'created_by' => $request->user()->id,
                'offline_agent_id' => $customRequest->assigned_to ?: $request->user()->id,
                'version' => 1,
                'currency' => $customRequest->currency ?: 'MAD',
                'requested_deposit' => $customRequest->requested_deposit,
                'paid_amount' => $customRequest->paid_amount ?? 0,
            ]);
        }

        if (in_array($latest->status, [CustomRequestQuote::STATUS_SENT, CustomRequestQuote::STATUS_MODIFICATION_REQUESTED, CustomRequestQuote::STATUS_ACCEPTED, CustomRequestQuote::STATUS_REFUSED], true)
            && $customRequest->status === CustomRequest::STATUS_MODIFICATION_REQUESTED) {
            return $customRequest->quotes()->create([
                'created_by' => $request->user()->id,
                'offline_agent_id' => $customRequest->assigned_to ?: $request->user()->id,
                'version' => ((int) $latest->version) + 1,
                'currency' => $latest->currency,
                'supplier_name' => $latest->supplier_name,
                'valid_until' => $latest->valid_until,
                'response_deadline' => $latest->response_deadline,
                'requested_deposit' => $latest->requested_deposit,
                'paid_amount' => $latest->paid_amount,
                'customer_conditions' => $latest->customer_conditions,
                'internal_notes' => $latest->internal_notes,
            ]);
        }

        return $latest;
    }

    private function authorizeQuote(Request $request, CustomRequest $customRequest): void
    {
        abort_unless($request->user()?->can('custom_requests.quote'), 403);
        abort_unless($customRequest->canBeQuotedBy($request->user()), 403);
    }

    private function isAgentRoute(Request $request): bool
    {
        return $request->routeIs('agent.custom-reservations.*');
    }

    private function quoteRoute(Request $request): string
    {
        return $this->isAgentRoute($request)
            ? 'agent.custom-reservations.quote'
            : 'admin.custom-requests.quote';
    }

    private function quoteRoutes(Request $request): array
    {
        if ($this->isAgentRoute($request)) {
            return [
                'show' => 'agent.custom-reservations.show',
                'take' => 'agent.custom-reservations.take',
                'store' => 'agent.custom-reservations.quote.store',
                'prepare' => 'agent.custom-reservations.quote.prepare',
                'send' => 'agent.custom-reservations.quote.send',
                'download' => 'agent.custom-reservations.quote.download',
                'download_price' => 'agent.custom-reservations.quote.price.download',
                'send_price' => 'agent.custom-reservations.quote.price.send',
                'documents_store' => 'agent.custom-reservations.documents.store',
            ];
        }

        return [
            'show' => 'admin.custom-requests.show',
            'take' => 'admin.custom-requests.take',
            'store' => 'admin.custom-requests.quote.store',
            'prepare' => 'admin.custom-requests.quote.prepare',
            'send' => 'admin.custom-requests.quote.send',
            'download' => 'admin.custom-requests.quote.download',
            'download_price' => 'admin.custom-requests.quote.price.download',
            'send_price' => 'admin.custom-requests.quote.price.send',
            'documents_store' => 'admin.custom-requests.documents.store',
        ];
    }
}
