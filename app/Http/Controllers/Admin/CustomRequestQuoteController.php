<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomRequest;
use App\Models\CustomRequestQuote;
use App\Services\CustomRequestNotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomRequestQuoteController extends Controller
{
    public function __construct(private readonly CustomRequestNotificationService $notifications) {}

    public function quote(Request $request, CustomRequest $customRequest): View
    {
        $this->authorizeQuote($request, $customRequest);

        $quote = $this->currentEditableQuote($customRequest, $request);

        return view('admin.custom-requests.quote', [
            'customRequest' => $customRequest->load(['creator:id,name', 'assignedAgent:id,name', 'services', 'documents', 'comments.user:id,name', 'statusLogs.user:id,name']),
            'quote' => $quote->load('items', 'generatedDocument'),
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

        return redirect()->route($this->quoteRoute($request), $customRequest)->with('success', 'Brouillon cotation enregistré.');
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

        if ($request->has('items')) {
            $this->saveQuotePayload($request, $quote);
        }

        abort_if($quote->items()->count() === 0, 422, 'Au moins une ligne est nécessaire pour générer le devis.');

        DB::transaction(function () use ($request, $customRequest, $quote): void {
            $quote->calculateTotals();
            $path = $quote->generatePdf();
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
            $customRequest->changeStatus(CustomRequest::STATUS_QUOTE_SENT, $request->user()->id, 'Devis envoyé à l’agent commercial.');
        });

        $this->notifications->notifyQuoteSent($customRequest->fresh(['creator']));

        return back()->with('success', 'Devis envoyé à l’agent commercial.');
    }

    public function download(Request $request, CustomRequest $customRequest, CustomRequestQuote $quote): StreamedResponse
    {
        abort_unless($request->user()?->can('custom_requests.documents') || $request->user()?->can('custom_requests.view'), 403);
        abort_unless((int) $quote->custom_request_id === (int) $customRequest->id, 404);
        abort_unless($quote->pdf_path && Storage::disk('public')->exists($quote->pdf_path), 404);

        return Storage::disk('public')->download($quote->pdf_path, basename($quote->pdf_path));
    }

    private function saveQuotePayload(Request $request, CustomRequestQuote $quote): void
    {
        $data = $request->validate([
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'valid_until' => ['nullable', 'date'],
            'currency' => ['required', Rule::in(['MAD', 'EUR', 'USD'])],
            'requested_deposit' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_conditions' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.service_type' => ['required_with:items', Rule::in(array_keys(CustomRequestQuote::itemServiceOptions()))],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.supplier_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_margin' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit_sale_price' => ['required_with:items', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($quote, $data): void {
            $quote->update([
                'supplier_name' => $data['supplier_name'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'currency' => $data['currency'],
                'requested_deposit' => $data['requested_deposit'] ?? null,
                'paid_amount' => $data['paid_amount'] ?? 0,
                'customer_conditions' => $data['customer_conditions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
            ]);

            if (array_key_exists('items', $data)) {
                $quote->items()->delete();
                foreach (array_values($data['items'] ?? []) as $index => $item) {
                    $quantity = (int) ($item['quantity'] ?? 1);
                    $purchase = (float) ($item['unit_purchase_price'] ?? 0);
                    $margin = (float) ($item['unit_margin'] ?? 0);
                    $sale = (float) ($item['unit_sale_price'] ?? 0);

                    $quote->items()->create([
                        'service_type' => $item['service_type'],
                        'description' => $item['description'],
                        'supplier_name' => $item['supplier_name'] ?? null,
                        'quantity' => $quantity,
                        'unit_purchase_price' => $purchase,
                        'unit_margin' => $margin,
                        'unit_sale_price' => $sale,
                        'total_purchase' => $quantity * $purchase,
                        'total_margin' => $quantity * $margin,
                        'total_sale' => $quantity * $sale,
                        'sort_order' => $index,
                    ]);
                }
            }

            $quote->calculateTotals();

            if ((float) $quote->paid_amount > (float) $quote->total_sale) {
                abort(422, 'Le montant payé ne peut pas dépasser le total vente.');
            }
        });
    }

    private function currentEditableQuote(CustomRequest $customRequest, Request $request): CustomRequestQuote
    {
        $latest = $customRequest->quotes()->latest('version')->first();

        if (! $latest) {
            return $customRequest->quotes()->create([
                'created_by' => $request->user()->id,
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
                'version' => ((int) $latest->version) + 1,
                'currency' => $latest->currency,
                'supplier_name' => $latest->supplier_name,
                'valid_until' => $latest->valid_until,
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
            'documents_store' => 'admin.custom-requests.documents.store',
        ];
    }
}
