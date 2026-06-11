@php
    $invoiceHeaderPath = \App\Models\Setting::getValue('invoice_header_image');
    $invoiceFooterPath = \App\Models\Setting::getValue('invoice_footer_image');
    $invoiceHeaderLocal = $invoiceHeaderPath ? public_path('storage/' . $invoiceHeaderPath) : null;
    $invoiceFooterLocal = $invoiceFooterPath ? public_path('storage/' . $invoiceFooterPath) : null;
@endphp

@if($invoiceHeaderLocal && file_exists($invoiceHeaderLocal))
    <div class="invoice-header">
        <img src="{{ $invoiceHeaderLocal }}" alt="En-tete">
    </div>
@endif

@if($invoiceFooterLocal && file_exists($invoiceFooterLocal))
    <div class="invoice-footer">
        <img src="{{ $invoiceFooterLocal }}" alt="Pied de page">
    </div>
@endif
