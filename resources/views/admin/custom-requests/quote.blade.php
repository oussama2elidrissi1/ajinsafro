@extends($quoteLayout ?? 'layouts.admin-v6')

@section('title', 'Cotation '.$customRequest->request_number)
@section('page_title', 'Cotation demande à la carte')

@push('styles')
@if(($quoteLayout ?? '') === 'layouts.master-ajinsafro')
    <link href="{{ URL::asset('css/agent-dashboard.css') }}" rel="stylesheet" type="text/css" />
@endif
<style>
    .partner-v2 .aj-topbar__brand-logo-wrap { width:96px !important; height:30px !important; max-width:96px !important; padding:4px 7px !important; overflow:hidden !important; }
    .partner-v2 .aj-topbar__brand-logo { display:block !important; width:auto !important; height:auto !important; max-width:100% !important; max-height:22px !important; object-fit:contain !important; }
    .partner-v2 .aj-topbar__brand { display:flex !important; align-items:center !important; gap:10px !important; min-width:0 !important; }
    .partner-v2 .aj-topbar__brand-name { color:#fff !important; font-size:16px !important; font-weight:700 !important; white-space:nowrap !important; }
    .partner-v2 .agent-portal-main { padding:0 18px 32px !important; }
    .partner-v2 .agent-portal-main > .quote-page { max-width:1680px; margin:0 auto; }
    .partner-v2 aside.w-full { width:18rem !important; max-width:18rem !important; flex:0 0 18rem !important; }
    .partner-v2 aside .sticky { top:72px !important; }
    .partner-v2 .agent-sidebar-menu { display:grid !important; gap:6px !important; padding:12px !important; }
    .partner-v2 .agent-sidebar-link { display:flex !important; align-items:center !important; gap:10px !important; min-height:42px !important; padding:10px 13px !important; border-radius:12px !important; color:#40516a !important; text-decoration:none !important; }
    .partner-v2 .agent-sidebar-link.active { background:#e6f3fa !important; color:#0083c4 !important; }
    .partner-v2 .agent-sidebar-logout { color:#ef4444 !important; }
    .quote-page { display:grid; gap:14px; }
    .quote-head,.quote-card { background:#fff; border:1px solid #dde7f0; border-radius:12px; padding:16px; box-shadow:0 8px 20px rgba(15,23,42,.04); }
    .quote-head { display:flex; justify-content:space-between; align-items:flex-start; gap:14px; }
    .quote-head h2 { margin:0; font-size:20px; font-weight:600; color:#10233f; }
    .quote-meta { display:flex; gap:8px; flex-wrap:wrap; margin-top:8px; }
    .quote-layout { display:grid; grid-template-columns:minmax(0,1fr) minmax(260px,300px); gap:14px; align-items:start; }
    .quote-card h3 { margin:0 0 12px; font-size:15px; font-weight:600; color:#10233f; }
    .quote-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; }
    .quote-field label { display:block; font-size:12px; color:#536276; font-weight:600; margin-bottom:5px; }
    .quote-field input,.quote-field select,.quote-field textarea { width:100%; min-height:38px; border:1px solid #d8e2ec; border-radius:8px; padding:8px 9px; background:#fff; }
    .quote-field textarea { min-height:74px; resize:vertical; }
    .quote-field.full { grid-column:1 / -1; }
    .quote-btn { border:0; border-radius:8px; padding:8px 11px; display:inline-flex; align-items:center; justify-content:center; gap:6px; font-weight:600; text-decoration:none; line-height:1.2; }
    .quote-btn-primary { background:#1f6feb; color:#fff; }
    .quote-btn-soft { background:#eef3f8; color:#20324d; border:1px solid #d8e2ec; }
    .quote-btn-danger { background:#dc3545; color:#fff; }
    .quote-table-wrap { overflow:auto; border:1px solid #e7edf3; border-radius:8px; }
    .quote-table { width:100%; min-width:930px; border-collapse:collapse; }
    .quote-table th { background:#f7fafc; color:#536276; font-size:12px; font-weight:600; padding:9px; }
    .quote-table td { padding:8px; border-top:1px solid #edf2f7; vertical-align:top; }
    .quote-table input,.quote-table select,.quote-table textarea { width:100%; min-height:36px; border:1px solid #d8e2ec; border-radius:8px; padding:7px; }
    .quote-table textarea { min-height:50px; }
    .quote-summary { display:grid; gap:8px; }
    .quote-summary-row { display:flex; justify-content:space-between; gap:12px; border-bottom:1px solid #edf2f7; padding-bottom:8px; color:#20324d; }
    .quote-summary-row strong { font-weight:600; }
    .quote-history { display:grid; gap:8px; max-height:360px; overflow:auto; }
    .quote-log { border-left:3px solid #d8e2ec; padding-left:10px; color:#20324d; }
    .partner-v2 .quote-page .badge { display:inline-flex; align-items:center; border-radius:999px; padding:5px 9px; font-size:11px; line-height:1; }
    @media(max-width:1400px){ .quote-layout{grid-template-columns:1fr}.quote-grid{grid-template-columns:repeat(2,1fr)} }
    @media(max-width:1200px){ .quote-layout{grid-template-columns:1fr}.quote-grid{grid-template-columns:repeat(2,1fr)} }
    @media(max-width:1024px){ .partner-v2 aside.w-full{width:100% !important;max-width:100% !important;flex:0 0 auto !important}.partner-v2 .agent-portal-main{padding:0 12px 28px !important} }
    @media(max-width:720px){ .quote-head{display:grid}.quote-grid{grid-template-columns:1fr}.quote-card{padding:14px}.quote-table{min-width:820px} }
</style>
@endpush

@section('content')
<div class="quote-page">
    <div class="quote-head">
        <div>
            <h2>Cotation demande à la carte</h2>
            <div class="text-muted">{{ $customRequest->request_number }} - {{ $customRequest->customer_full_name }}</div>
            <div class="quote-meta">
                <span class="badge bg-primary">{{ $customRequest->statusLabel() }}</span>
                <span class="badge bg-secondary">{{ $customRequest->priorityLabel() }}</span>
                <span class="badge bg-light text-dark">Agent créateur: {{ $customRequest->creator?->name ?: '-' }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap justify-content-end">
            <a href="{{ route($quoteRoutes['show'] ?? 'admin.custom-requests.show', $customRequest) }}" class="quote-btn quote-btn-soft"><i class="bx bx-arrow-back"></i> Retour</a>
            @if(!$customRequest->assigned_to)
                <form method="POST" action="{{ route($quoteRoutes['take'] ?? 'admin.custom-requests.take', $customRequest) }}">@csrf<button class="quote-btn quote-btn-soft">Prendre en charge</button></form>
            @endif
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success mb-0">{{ session('success') }}</div> @endif

    <form method="POST" action="{{ route($quoteRoutes['store'] ?? 'admin.custom-requests.quote.store', $customRequest) }}" id="quoteForm" class="quote-layout">
        @csrf
        <div class="quote-page">
            <section class="quote-card">
                <h3>Informations cotation</h3>
                <div class="quote-grid">
                    <div class="quote-field"><label>Agent offline responsable</label><input value="{{ $customRequest->assignedAgent?->name ?: auth()->user()->name }}" readonly></div>
                    <div class="quote-field"><label>Fournisseur principal</label><input name="supplier_name" value="{{ old('supplier_name', $quote->supplier_name) }}"></div>
                    <div class="quote-field"><label>Validité devis</label><input type="date" name="valid_until" value="{{ old('valid_until', optional($quote->valid_until)->toDateString()) }}"></div>
                    <div class="quote-field"><label>Devise</label><select name="currency">@foreach(['MAD','EUR','USD'] as $currency)<option value="{{ $currency }}" @selected(old('currency', $quote->currency) === $currency)>{{ $currency }}</option>@endforeach</select></div>
                    <div class="quote-field"><label>Statut cotation</label><input value="{{ $quote->statusLabel() }}" readonly></div>
                    <div class="quote-field"><label>Date limite réponse</label><input value="{{ $customRequest->response_deadline?->format('d/m/Y') ?: '-' }}" readonly></div>
                    <div class="quote-field"><label>Acompte demandé</label><input type="number" step="0.01" min="0" name="requested_deposit" data-money value="{{ old('requested_deposit', $quote->requested_deposit) }}"></div>
                    <div class="quote-field"><label>Montant payé</label><input type="number" step="0.01" min="0" name="paid_amount" data-money value="{{ old('paid_amount', $quote->paid_amount ?? 0) }}"></div>
                </div>
            </section>

            <section class="quote-card">
                <div class="d-flex justify-content-between gap-2 align-items-center mb-2">
                    <h3 class="mb-0">Lignes cotation</h3>
                    <button type="button" class="quote-btn quote-btn-soft" id="addQuoteLine"><i class="bx bx-plus"></i> Ajouter ligne</button>
                </div>
                <div class="quote-table-wrap">
                    <table class="quote-table" id="quoteItemsTable">
                        <thead>
                            <tr>
                                <th>Service</th><th>Description</th><th>Fournisseur</th><th>Qté</th><th>Achat</th><th>Marge</th><th>Vente U.</th><th>Total vente</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse(old('items', $quote->items->map(fn($item) => $item->toArray())->all()) as $index => $item)
                            <tr data-line>
                                <td><select name="items[{{ $index }}][service_type]">@foreach($serviceTypeOptions as $key=>$label)<option value="{{ $key }}" @selected(($item['service_type'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></td>
                                <td><textarea name="items[{{ $index }}][description]" required>{{ $item['description'] ?? '' }}</textarea></td>
                                <td><input name="items[{{ $index }}][supplier_name]" value="{{ $item['supplier_name'] ?? '' }}"></td>
                                <td><input type="number" min="1" name="items[{{ $index }}][quantity]" data-qty value="{{ $item['quantity'] ?? 1 }}"></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_purchase_price]" data-purchase value="{{ $item['unit_purchase_price'] ?? 0 }}"></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_margin]" data-margin value="{{ $item['unit_margin'] ?? 0 }}"></td>
                                <td><input type="number" step="0.01" min="0" name="items[{{ $index }}][unit_sale_price]" data-sale value="{{ $item['unit_sale_price'] ?? 0 }}"></td>
                                <td><input data-line-total readonly value="0.00"></td>
                                <td><button type="button" class="quote-btn quote-btn-danger" data-remove-line><i class="bx bx-trash"></i></button></td>
                            </tr>
                        @empty
                            <tr data-line>
                                <td><select name="items[0][service_type]">@foreach($serviceTypeOptions as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></td>
                                <td><textarea name="items[0][description]" required></textarea></td>
                                <td><input name="items[0][supplier_name]"></td>
                                <td><input type="number" min="1" name="items[0][quantity]" data-qty value="1"></td>
                                <td><input type="number" step="0.01" min="0" name="items[0][unit_purchase_price]" data-purchase value="0"></td>
                                <td><input type="number" step="0.01" min="0" name="items[0][unit_margin]" data-margin value="0"></td>
                                <td><input type="number" step="0.01" min="0" name="items[0][unit_sale_price]" data-sale value="0"></td>
                                <td><input data-line-total readonly value="0.00"></td>
                                <td><button type="button" class="quote-btn quote-btn-danger" data-remove-line><i class="bx bx-trash"></i></button></td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="quote-card">
                <h3>Conditions et notes</h3>
                <div class="quote-grid">
                    <div class="quote-field full"><label>Conditions client</label><textarea name="customer_conditions">{{ old('customer_conditions', $quote->customer_conditions) }}</textarea></div>
                    <div class="quote-field full"><label>Notes internes</label><textarea name="internal_notes">{{ old('internal_notes', $quote->internal_notes) }}</textarea></div>
                </div>
            </section>

            <section class="quote-card">
                <h3>Documents</h3>
                <div class="d-flex gap-2 flex-wrap">
                    @if($quote->pdf_path)
                        <a href="{{ route($quoteRoutes['download'] ?? 'admin.custom-requests.quote.download', [$customRequest, $quote]) }}" class="quote-btn quote-btn-soft"><i class="bx bx-download"></i> Télécharger devis</a>
                    @endif
                    <button type="submit" class="quote-btn quote-btn-soft"><i class="bx bx-save"></i> Sauvegarder brouillon cotation</button>
                    <button type="submit" formaction="{{ route($quoteRoutes['prepare'] ?? 'admin.custom-requests.quote.prepare', [$customRequest, $quote]) }}" class="quote-btn quote-btn-primary"><i class="bx bx-file"></i> Générer devis automatique</button>
                </div>
            </section>
        </div>

        <aside class="quote-page">
            <section class="quote-card">
                <h3>Résumé financier</h3>
                <div class="quote-summary">
                    <div class="quote-summary-row"><span>Total achat</span><strong id="totalPurchase">0.00</strong></div>
                    <div class="quote-summary-row"><span>Total marge</span><strong id="totalMargin">0.00</strong></div>
                    <div class="quote-summary-row"><span>Total vente</span><strong id="totalSale">0.00</strong></div>
                    <div class="quote-summary-row"><span>Acompte demandé</span><strong id="deposit">{{ number_format((float)($quote->requested_deposit ?? 0), 2, '.', '') }}</strong></div>
                    <div class="quote-summary-row"><span>Montant payé</span><strong id="paid">{{ number_format((float)($quote->paid_amount ?? 0), 2, '.', '') }}</strong></div>
                    <div class="quote-summary-row"><span>Reste à payer</span><strong id="remaining">0.00</strong></div>
                </div>
                @if($quote->pdf_path)
                    <button type="submit" formaction="{{ route($quoteRoutes['send'] ?? 'admin.custom-requests.quote.send', [$customRequest, $quote]) }}" class="quote-btn quote-btn-primary w-100 justify-content-center mt-3">Envoyer devis à l’agent</button>
                @endif
            </section>

            <section class="quote-card">
                <h3>Résumé demande</h3>
                <div class="quote-summary">
                    <div class="quote-summary-row"><span>Client</span><strong>{{ $customRequest->customer_full_name }}</strong></div>
                    <div class="quote-summary-row"><span>Destination</span><strong>{{ $customRequest->desired_destination }}</strong></div>
                    <div class="quote-summary-row"><span>Date départ</span><strong>{{ $customRequest->desired_departure_date?->format('d/m/Y') }}</strong></div>
                    <div class="quote-summary-row"><span>Voyageurs</span><strong>{{ $customRequest->travelers_count }}</strong></div>
                    <div class="quote-summary-row"><span>Services</span><strong>{{ $customRequest->services->pluck('service_label')->implode(', ') ?: '-' }}</strong></div>
                </div>
            </section>

            <section class="quote-card">
                <h3>Historique</h3>
                <div class="quote-history">
                    @foreach($customRequest->statusLogs as $log)
                        <div class="quote-log">{{ $log->old_status ?: '-' }} → {{ $log->new_status }}<div class="text-muted small">{{ $log->user?->name ?: 'Système' }} - {{ $log->created_at?->format('d/m/Y H:i') }}</div>{{ $log->note }}</div>
                    @endforeach
                    @foreach($customRequest->comments as $comment)
                        <div class="quote-log"><strong>{{ $comment->comment_type }}</strong><div class="text-muted small">{{ $comment->user?->name }} - {{ $comment->created_at?->format('d/m/Y H:i') }}</div>{{ $comment->message }}</div>
                    @endforeach
                </div>
            </section>
        </aside>
    </form>

    @can('custom_requests.documents')
        <section class="quote-card">
            <h3>Upload fichiers fournisseur / documents complémentaires</h3>
            <form method="POST" action="{{ route($quoteRoutes['documents_store'] ?? 'admin.custom-requests.documents.store', $customRequest) }}" enctype="multipart/form-data" class="row g-2">
                @csrf
                <div class="col-md-3"><select name="document_type" class="form-select"><option value="supplier_file">Fichier fournisseur</option><option value="other">Autre</option></select></div>
                <div class="col-md-4"><input name="title" class="form-control" placeholder="Titre"></div>
                <div class="col-md-3"><input type="file" name="document" class="form-control" required></div>
                <div class="col-md-2"><button class="quote-btn quote-btn-soft w-100">Uploader</button></div>
            </form>
        </section>
    @endcan
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tbody = document.querySelector('#quoteItemsTable tbody');
    const serviceOptions = @json($serviceTypeOptions);

    function money(value) {
        return (Number(value || 0)).toFixed(2);
    }

    function recalc() {
        let purchase = 0;
        let margin = 0;
        let sale = 0;
        document.querySelectorAll('[data-line]').forEach(function (line) {
            const qty = Number(line.querySelector('[data-qty]')?.value || 1);
            const unitPurchase = Number(line.querySelector('[data-purchase]')?.value || 0);
            const unitMargin = Number(line.querySelector('[data-margin]')?.value || 0);
            const unitSale = Number(line.querySelector('[data-sale]')?.value || 0);
            purchase += qty * unitPurchase;
            margin += qty * unitMargin;
            sale += qty * unitSale;
            const lineTotal = line.querySelector('[data-line-total]');
            if (lineTotal) lineTotal.value = money(qty * unitSale);
        });
        const paid = Number(document.querySelector('[name="paid_amount"]')?.value || 0);
        const deposit = Number(document.querySelector('[name="requested_deposit"]')?.value || 0);
        document.getElementById('totalPurchase').textContent = money(purchase);
        document.getElementById('totalMargin').textContent = money(margin);
        document.getElementById('totalSale').textContent = money(sale);
        document.getElementById('paid').textContent = money(paid);
        document.getElementById('deposit').textContent = money(deposit);
        document.getElementById('remaining').textContent = money(Math.max(0, sale - paid));
    }

    function reindex() {
        document.querySelectorAll('[data-line]').forEach(function (line, index) {
            line.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
        });
    }

    document.getElementById('addQuoteLine')?.addEventListener('click', function () {
        const index = document.querySelectorAll('[data-line]').length;
        const options = Object.entries(serviceOptions).map(([key, label]) => `<option value="${key}">${label}</option>`).join('');
        const row = document.createElement('tr');
        row.setAttribute('data-line', '');
        row.innerHTML = `<td><select name="items[${index}][service_type]">${options}</select></td><td><textarea name="items[${index}][description]" required></textarea></td><td><input name="items[${index}][supplier_name]"></td><td><input type="number" min="1" name="items[${index}][quantity]" data-qty value="1"></td><td><input type="number" step="0.01" min="0" name="items[${index}][unit_purchase_price]" data-purchase value="0"></td><td><input type="number" step="0.01" min="0" name="items[${index}][unit_margin]" data-margin value="0"></td><td><input type="number" step="0.01" min="0" name="items[${index}][unit_sale_price]" data-sale value="0"></td><td><input data-line-total readonly value="0.00"></td><td><button type="button" class="quote-btn quote-btn-danger" data-remove-line><i class="bx bx-trash"></i></button></td>`;
        tbody.appendChild(row);
        recalc();
    });

    document.addEventListener('click', function (event) {
        const remove = event.target.closest('[data-remove-line]');
        if (!remove) return;
        if (document.querySelectorAll('[data-line]').length > 1) {
            remove.closest('[data-line]').remove();
            reindex();
            recalc();
        }
    });

    document.addEventListener('input', function (event) {
        if (event.target.closest('#quoteForm')) recalc();
    });

    recalc();
});
</script>
@endpush
